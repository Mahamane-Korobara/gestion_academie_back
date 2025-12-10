<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Etudiant;
use App\Models\Professeur;
use App\Models\Cours;
use App\Models\Filiere;
use App\Models\Niveau;
use App\Models\LogActivite;
use App\Http\Resources\Admin\FiliereStatResource;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $resume = [
            'total_etudiants'   => Etudiant::count(),
            'total_professeurs' => Professeur::count(),
            'total_cours'       => Cours::count(),
            'total_filieres'    => Filiere::count(),
            'total_niveaux'     => Niveau::count(),
        ];

        $etudiantsParFiliere = FiliereStatResource::collection(
            Filiere::select('id', 'nom', 'code')
                ->withCount('etudiants')
                ->get()
        );

        $etudiantsParSexe = Etudiant::pluck('sexe')
            ->groupBy(fn($s) => $s)
            ->map->count();

        // Pour le taux de réussite vide pour l'instant
        $tauxReussiteParFiliere = [];

        $recentActivities = LogActivite::select('id', 'action', 'description', 'user_id', 'created_at')
            ->with('user:id,name')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($log) => [
                'id'          => $log->id,
                'action'      => $log->action,
                'description' => $log->description,
                'user_name'   => $log->user->name ?? 'Utilisateur supprimé',
                'created_at'  => $log->formatted_date,
            ]);

        return response()->json([
            'resume' => $resume,
            'charts' => [
                'etudiants_par_filiere'   => $etudiantsParFiliere,
                'etudiants_par_sexe'      => $etudiantsParSexe,
                'taux_reussite_par_filiere' => $tauxReussiteParFiliere, // à implémenter plus tard
            ],
            'recent_activities' => $recentActivities,
        ]);
    }

}