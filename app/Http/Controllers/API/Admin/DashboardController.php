<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeAcademique;
use App\Models\Semestre;
use App\Services\DashboardService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(Request $request)
    {
        // Récupération des filtres
        $anneeId = $request->get('annee_id');
        $semestreId = $request->get('semestre_id');
        $filiereId = $request->get('filiere_id');
        $niveauId = $request->get('niveau_id');

        // Utiliser les valeurs actives par défaut
        if (!$anneeId) {
            $anneeActive = AnneeAcademique::where('is_active', true)->first();
            $anneeId = $anneeActive?->id;
        }
        if (!$semestreId) {
            $semestreActif = Semestre::where('is_active', true)->first();
            $semestreId = $semestreActif?->id;
        }

        // Clé de cache unique
        $cacheKey = CacheService::key(
            'stats_dashboard', 
            $anneeId ?? 'all', 
            $semestreId ?? 'all', 
            $filiereId ?? 'all', 
            $niveauId ?? 'all'
        );

        // Récupérer les stats via le service
        $stats = Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($anneeId, $semestreId, $filiereId, $niveauId) {
            return $this->dashboardService->getStats($anneeId, $semestreId, $filiereId, $niveauId);
        });

        // Données complémentaires
        $recentActivities = $this->dashboardService->getRecentActivities();
        $alerts = $this->dashboardService->getAlertes();
        $filtresDisponibles = $this->dashboardService->getFiltresDisponibles();

        // Données de l'année académique
        $anneeActive = AnneeAcademique::where('is_active', true)->first();
        $semestreActif = Semestre::where('is_active', true)->first();

        return response()->json([
            'filtres_appliques' => compact('anneeId', 'semestreId', 'filiereId', 'niveauId'),
            'filtres_disponibles' => $filtresDisponibles,
            'annee_academique' => $anneeActive ? [
                'id' => $anneeActive->id,
                'annee' => $anneeActive->annee,
                'semestre_actif' => $semestreActif ? $semestreActif->numero->label() : null,
            ] : null,
            'resume' => $stats['resume'],
            'charts' => $stats['charts'],
            'academique' => $stats['academique'],
            'recent_activities' => $recentActivities,
            'alerts' => $alerts,
            'last_updated' => now()->format('d/m/Y H:i'),
        ]);
    }
}