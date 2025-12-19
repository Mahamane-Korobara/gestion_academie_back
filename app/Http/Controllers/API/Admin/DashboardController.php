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
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. On récupère les statistiques (mises en cache car calculs lourds)
        $stats = Cache::remember(CacheService::KEYS['stats_dashboard'], CacheService::SHORT_TTL, function () {
            
            // Résumé numérique
            $resume = [
                'total_etudiants'   => Etudiant::count(),
                'total_professeurs' => Professeur::count(),
                'total_cours'       => Cours::count(),
                'total_filieres'    => Filiere::count(),
                'total_niveaux'     => Niveau::count(),
            ];

            // Statistiques par filière
            $etudiantsParFiliere = FiliereStatResource::collection(
                Filiere::select('id', 'nom', 'code')
                    ->withCount('etudiants')
                    ->get()
            );

            // Répartition par sexe
            $etudiantsParSexe = Etudiant::pluck('sexe')
                ->groupBy(fn($s) => $s)
                ->map->count();

            return [
                'resume' => $resume,
                'charts' => [
                    'etudiants_par_filiere'     => $etudiantsParFiliere,
                    'etudiants_par_sexe'        => $etudiantsParSexe,
                    'taux_reussite_par_filiere' => [], // à implémenter plus tard
                ]
            ];
        });

        // 2. On récupère les activités récentes SANS cache 
        // pour que l'admin voie ses actions immédiatement
        $recentActivities = LogActivite::select('id', 'action', 'description', 'user_id', 'created_at')
            ->with(['user' => function($query) {
                $query->select('id', 'name', 'role_id')->with('role');
            }])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($log) => [
                'id'          => $log->id,
                'action'      => $log->action,
                'description' => $log->description,
                'user_name'   => $log->user->name ?? 'Système',
                // Utilisation de tes méthodes de modèle User si nécessaire
                'user_role'   => $log->user ? $log->user->role->name : null,
                'created_at'  => $log->created_at->diffForHumans(), // Plus lisible pour un dashboard
            ]);

        // 3. Fusion des données
        return response()->json([
            'resume'            => $stats['resume'],
            'charts'            => $stats['charts'],
            'recent_activities' => $recentActivities,
        ]);
    }
}