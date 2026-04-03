<?php

namespace App\Http\Controllers\API\Etudiant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Etudiant\NoteEtudiantResource;
use App\Models\Cours;
use App\Models\Inscription;
use App\Models\Note;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EtudiantController extends Controller
{
    use AuthorizesRequests;

    /**
     * Dashboard étudiant
     * Transaction pour cohérence + Cache avec CacheService
     */
    public function dashboard(Request $request)
    {
        $etudiant = $request->user()->etudiant;

        if (!$etudiant) {
            return response()->json(['message' => 'Profil étudiant introuvable'], 404);
        }

        $cacheKey = "etudiant:dashboard:{$etudiant->id}";

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($etudiant) {
            return DB::transaction(function () use ($etudiant) {
                // Cours actuels (via inscriptions)
                $coursActuels = Cours::whereHas('inscriptions', fn($q) => $q->where('etudiant_id', $etudiant->id))
                    ->with(['niveau.filiere'])
                    ->get();

                // Nouvelles notes validées (Top 3)
                $dernieresNotes = Note::where('etudiant_id', $etudiant->id)
                    ->where('statut', 'validee')
                    ->with(['evaluation.typeEvaluation', 'evaluation.cours'])
                    ->orderByDesc('date_validation')
                    ->limit(3)
                    ->get();

                return response()->json([
                    'etudiant' => [
                        'id' => $etudiant->id,
                        'nom_complet' => $etudiant->nom_complet,
                        'matricule' => $etudiant->matricule,
                        'filiere' => $etudiant->niveau->filiere->nom ?? null,
                        'niveau' => $etudiant->niveau->nom ?? null,
                        'statut_academique' => $etudiant->statut?->label() ?? 'actif',
                    ],
                    'cours' => $coursActuels->map(fn($c) => [
                        'id' => $c->id,
                        'titre' => $c->titre,
                        'code' => $c->code,
                        'coefficient' => (float) $c->coefficient,
                    ]),
                    'activites_recentes' => [
                        'nouvelles_notes' => NoteEtudiantResource::collection($dernieresNotes),
                    ],
                ]);
            }, 3); // Tentatives en cas de deadlock
        });
    }

    /**
     * Liste des notes de l'étudiant
     */
    public function notes(Request $request)
    {
        $etudiant = $request->user()->etudiant;
        $this->authorize('consulterNotes', $etudiant);

        $page = $request->get('page', 1);
        $cacheKey = "etudiant:{$etudiant->id}:notes:page:{$page}";

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($etudiant) {
            return DB::transaction(function () use ($etudiant) {
                $notes = Note::where('etudiant_id', $etudiant->id)
                    ->where('statut', 'validee')
                    ->with(['evaluation.typeEvaluation', 'evaluation.cours.niveau.filiere'])
                    ->orderByDesc('date_validation')
                    ->paginate(20);

                return NoteEtudiantResource::collection($notes);
            });
        });
    }

    /**
     * Liste complète des cours de l'étudiant
     */
    public function cours(Request $request)
    {
        $etudiant = $request->user()->etudiant;
        $this->authorize('view', $etudiant);

        $page = $request->get('page', 1);
        $cacheKey = "etudiant:{$etudiant->id}:cours:page:{$page}";

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($etudiant) {
            return DB::transaction(function () use ($etudiant) {
                $inscriptions = Inscription::where('etudiant_id', $etudiant->id)
                    ->with(['cours.niveau.filiere', 'cours.professeurs.user'])
                    ->orderByDesc('date_inscription')
                    ->paginate(15);

                return response()->json([
                    'data' => $inscriptions->map(fn($i) => [
                        'id' => $i->cours->id,
                        'titre' => $i->cours->titre,
                        'code' => $i->cours->code,
                        'coefficient' => (float) $i->cours->coefficient,
                        'filiere' => $i->cours->niveau->filiere->nom ?? 'N/A',
                        'niveau' => $i->cours->niveau->nom ?? 'N/A',
                        'professeurs' => $i->cours->professeurs->map(fn($p) => [
                            'nom_complet' => $p->user?->name ?? 'Professeur inconnu',
                            'specialite' => $p->specialite,
                        ]),
                    ]),
                    'meta' => [
                        'current_page' => $inscriptions->currentPage(),
                        'total' => $inscriptions->total(),
                        'per_page' => $inscriptions->perPage(),
                    ],
                ]);
            });
        });
    }
}
