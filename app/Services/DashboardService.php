<?php

namespace App\Services;

use App\Models\Etudiant;
use App\Models\Professeur;
use App\Models\Cours;
use App\Models\Filiere;
use App\Models\Niveau;
use App\Models\LogActivite;
use App\Models\Note;
use App\Models\AnneeAcademique;
use App\Models\Semestre;
use App\Http\Resources\Admin\FiliereStatResource;
use Illuminate\Support\Facades\DB;
use App\Enums\StudentStatus;

class DashboardService
{
    /**
     * Calculer toutes les statistiques du dashboard
     */
    public function getStats($anneeId, $semestreId, $filiereId, $niveauId)
    {
        // Base queries avec filtres
        $etudiantsQuery = Etudiant::query();
        $coursQuery = Cours::query();
        $notesQuery = Note::query();

        // Appliquer les filtres
        if ($semestreId) {
            $notesQuery->whereHas('evaluation', fn($q) => $q->where('semestre_id', $semestreId));
        }
        if ($filiereId) {
            $etudiantsQuery->where('filiere_id', $filiereId);
            $coursQuery->whereHas('niveau', fn($q) => $q->where('filiere_id', $filiereId));
        }
        if ($niveauId) {
            $etudiantsQuery->where('niveau_id', $niveauId);
            $coursQuery->where('niveau_id', $niveauId);
        }

        // Résumé numérique
        $resume = $this->getResume($etudiantsQuery, $coursQuery);
        
        // Graphiques
        $charts = [
            'etudiants_par_filiere' => $this->getEtudiantsParFiliere($anneeId, $semestreId, $filiereId, $niveauId),
            'etudiants_par_sexe' => $this->getEtudiantsParSexe($etudiantsQuery),
            'etudiants_par_niveau' => $this->getEtudiantsParNiveau($anneeId, $semestreId, $filiereId, $niveauId),
            'professeurs_plus_charges' => $this->getProfesseursCharges($anneeId, $semestreId, $filiereId, $niveauId),
        ];

        // Statistiques académiques
        $academique = [
            'moyenne_generale' => round($notesQuery->avg('note') ?? 0, 2),
            'taux_reussite_global' => 0,
            'notes_saisies' => $notesQuery->count(),
        ];

        return compact('resume', 'charts', 'academique');
    }

    /**
     * Obtenir les activités récentes
     */
    public function getRecentActivities()
    {
        return LogActivite::with('user.role')
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'action' => $log->action->value ?? $log->action,
                'description' => $log->description,
                'user_name' => $log->user?->name ?? 'Système',
                'user_role' => $log->user?->role?->display_name ?? 'Système',
                'created_at' => $log->created_at->format('d/m H:i'),
                'icon' => $this->getActionIcon($log->action->value ?? $log->action),
            ]);
    }

    /**
     * Obtenir les alertes
     */
    public function getAlertes()
    {
        $alerts = [];
        $coursSansEvaluations = Cours::whereDoesntHave('evaluations')->count();
        if ($coursSansEvaluations > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Cours sans évaluations',
                'message' => "{$coursSansEvaluations} cours n'ont aucune évaluation créée",
                'action' => '/admin/evaluations'
            ];
        }

        $totalNiveaux = Niveau::count();
        $niveauxAvecEmploi = DB::table('emploi_du_temps')
            ->distinct('niveau_id')
            ->count('niveau_id');
        
        $niveauxSansEmploi = $totalNiveaux - $niveauxAvecEmploi;
        if ($niveauxSansEmploi > 0) {
            $alerts[] = [
                'type' => 'error',
                'title' => 'Niveaux sans emploi du temps',
                'message' => "{$niveauxSansEmploi} niveaux n'ont pas d'emploi du temps",
                'action' => '/admin/emplois-du-temps'
            ];
        }

        return $alerts;
    }

    /**
     * Obtenir les options de filtre disponibles
     */
    public function getFiltresDisponibles()
    {
        return [
            'annees' => AnneeAcademique::select('id', 'annee')
                ->orderByDesc('annee')
                ->get(),
            'semestres' => Semestre::select('id', 'numero', 'annee_academique_id')
                ->with('anneeAcademique:id,annee')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'label' => $s->numero->label() . ($s->anneeAcademique ? ' - ' . $s->anneeAcademique->annee : ''),
                    'annee_id' => $s->annee_academique_id,
                ]),
            'filieres' => Filiere::select('id', 'nom', 'code')->get(),
            'niveaux' => Niveau::select('id', 'nom')->get(),
        ];
    }

    // ============= MÉTHODES PRIVÉES =============

    private function getResume($etudiantsQuery, $coursQuery)
    {
        return [
            'total_etudiants' => $etudiantsQuery->count(),
            'total_professeurs' => Professeur::count(),
            'total_cours' => $coursQuery->count(),
            'total_filieres' => Filiere::count(),
            'total_niveaux' => Niveau::count(),
            'etudiants_actifs' => (clone $etudiantsQuery)->where('statut', StudentStatus::ACTIF)->count(),
            'etudiants_redoublants' => (clone $etudiantsQuery)->where('statut', StudentStatus::REDOUBLANT)->count(),
            'etudiants_rattrapage' => (clone $etudiantsQuery)->where('statut', StudentStatus::RATTRAPAGE)->count(),
            'etudiants_diplomes' => (clone $etudiantsQuery)->where('statut', StudentStatus::DIPLOME)->count(),
        ];
    }

    private function getEtudiantsParFiliere($anneeId, $semestreId, $filiereId, $niveauId)
    {
        $query = Filiere::withCount(['etudiants' => function($q) use ($anneeId, $niveauId) {
            if ($anneeId) $q->where('etudiants.annee_academique_id', $anneeId);
            if ($niveauId) $q->where('niveau_id', $niveauId);
        }]);

        if ($filiereId) {
            $query->where('id', $filiereId);
        }

        return FiliereStatResource::collection(
            $query->orderByDesc('etudiants_count')
                ->limit(10)
                ->get()
        );
    }

    private function getEtudiantsParSexe($etudiantsQuery)
    {
        return (clone $etudiantsQuery)
            ->selectRaw('sexe, COUNT(*) as count')
            ->groupBy('sexe')
            ->pluck('count', 'sexe');
    }

    private function getEtudiantsParNiveau($anneeId, $semestreId, $filiereId, $niveauId)
    {
        $query = Niveau::query()
            ->select('id', 'nom', 'filiere_id')
            ->withCount(['etudiants as etudiants_count' => function ($q) use ($anneeId) {
                if ($anneeId) {
                    $q->where('etudiants.annee_academique_id', $anneeId);
                }
            }]);

        if ($filiereId) {
            $query->where('filiere_id', $filiereId);
        }

        if ($niveauId) {
            $query->where('id', $niveauId);
        }

        $rows = $query->get();

        // Agréger par nom de niveau (M1/M2) au lieu d’écraser
        return $rows
            ->groupBy('nom')
            ->map(fn ($group) => (int) $group->sum('etudiants_count'))
            ->sortKeys()
            ->toArray();
    }

    private function getProfesseursCharges($anneeId, $semestreId, $filiereId, $niveauId)
    {
        $query = Professeur::withCount(['cours as cours_count' => function($q) use ($anneeId, $filiereId, $niveauId) {
            if ($anneeId) {
                $q->where('cours.annee_academique_id', $anneeId);
            }
            
            if ($filiereId || $niveauId) {
                $q->whereHas('niveau', function($nq) use ($filiereId, $niveauId) {
                    if ($filiereId) $nq->where('filiere_id', $filiereId);
                    if ($niveauId) $nq->where('id', $niveauId);
                });
            }
        }]);

        return $query->orderByDesc('cours_count')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'nom' => $p->nom_complet,
                'cours' => $p->cours_count ?? 0,
                'etudiants' => $p->cours->sum(fn($c) => ($c->etudiants ?? collect())->count())
            ]);
    }

    private function getActionIcon(string $action): string
    {
        return match($action) {
            'CREATE' => 'plus-circle',
            'UPDATE' => 'edit',
            'DELETE' => 'trash',
            'LOGIN' => 'log-in',
            default => 'activity'
        };
    }
}