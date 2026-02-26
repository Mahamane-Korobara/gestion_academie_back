<?php

namespace App\Services;

use App\Enums\JourSemaine;
use App\Models\User;
use App\Models\Note;
use App\Models\Evaluation;
use App\Models\EmploiDuTemps;
use App\Models\LogActivite;

class ProfesseurDashboardService
{
    public function getStats(User $professeur)
    {
        if (!$professeur->professeur) {
            return [
                'resume' => [
                    'cours_enseignes' => 0,
                    'etudiants_suivis' => 0,
                    'notes_en_attente' => 0,
                ],
                'prochains_cours' => [],
                'recent_activities' => [],
                'stats_cours' => [],
            ];
        }

        // Charger la relation etudiants
        $cours = $professeur->professeur->cours()->with('etudiants')->get();
        $coursIds = $cours->pluck('id');

        // Étudiants suivis
        $etudiantsCount = $cours->flatMap->etudiants->count();

        // Notes en attente (non validées)
        $notesEnAttente = Note::whereIn('evaluation_id', 
            Evaluation::whereIn('cours_id', $coursIds)->pluck('id')
        )->whereNull('date_validation')->count();

        // Prochains cours (aujourd'hui + demain) du professeur connecté uniquement.
        $jourAujourdhui = $this->jourSemaineFromDate(now());
        $jourDemain = $this->jourSemaineFromDate(now()->copy()->addDay());
        $ordreJours = array_flip(JourSemaine::values());

        $prochainsCours = EmploiDuTemps::query()
            ->where('professeur_id', $professeur->professeur->id)
            ->where(function ($query) use ($jourAujourdhui, $jourDemain) {
                $query->where('jour', $jourAujourdhui)
                    ->orWhere('jour', $jourDemain);
            })
            ->with(['cours', 'niveau'])
            ->get()
            ->sortBy(function ($emploi) use ($ordreJours) {
                $jourValue = $emploi->jour?->value;
                $jourRank = $ordreJours[$jourValue] ?? 99;
                $heureRank = (int) $emploi->heure_debut?->format('Hi');
                return ($jourRank * 10000) + $heureRank;
            })
            ->take(5)
            ->values()
            ->map(fn($emploi) => [
                'id' => $emploi->id,
                'jour' => $emploi->jour->value,
                'heure' => $emploi->heure_debut->format('H:i') . ' - ' . $emploi->heure_fin->format('H:i'),
                'cours' => $emploi->cours->titre,
                'niveau' => $emploi->niveau->nom,
            ]);

        // Activités récentes
        $recentActivities = LogActivite::where('user_id', $professeur->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($log) => [
                'action' => $log->action,
                'description' => $log->description,
                'created_at' => $log->created_at->diffForHumans(),
            ]);

        // Statistiques de cours
        $statsCours = $cours->map(fn($c) => [
            'id' => $c->id,
            'titre' => $c->titre,
            'etudiants' => $c->etudiants->count(), // Maintenant c'est une Collection
            'notes_saisies' => Note::whereIn('evaluation_id', 
                Evaluation::where('cours_id', $c->id)->pluck('id')
            )->count(),
            'moyenne' => Note::whereIn('evaluation_id', 
                Evaluation::where('cours_id', $c->id)->pluck('id')
            )->avg('note') ?? 0,
        ]);

        return [
            'resume' => [
                'cours_enseignes' => $cours->count(),
                'etudiants_suivis' => $etudiantsCount,
                'notes_en_attente' => $notesEnAttente,
            ],
            'prochains_cours' => $prochainsCours,
            'recent_activities' => $recentActivities,
            'stats_cours' => $statsCours,
        ];
    }

    private function jourSemaineFromDate(\Illuminate\Support\Carbon $date): string
    {
        return JourSemaine::cases()[$date->dayOfWeekIso - 1]->value;
    }
}
