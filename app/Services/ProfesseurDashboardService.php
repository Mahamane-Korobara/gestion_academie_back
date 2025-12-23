<?php

namespace App\Services;

use App\Models\User;
use App\Models\Cours;
use App\Models\Etudiant;
use App\Models\Note;
use App\Models\Evaluation;
use App\Models\EmploiDuTemps;
use App\Models\LogActivite;

class ProfesseurDashboardService
{
    public function getStats(User $professeur)
    {
        // Charger la relation etudiants
        $cours = $professeur->professeur->cours()->with('etudiants')->get();
        $coursIds = $cours->pluck('id');

        // Étudiants suivis
        $etudiantsCount = $cours->flatMap->etudiants->count();

        // Notes en attente (non validées)
        $notesEnAttente = Note::whereIn('evaluation_id', 
            Evaluation::whereIn('cours_id', $coursIds)->pluck('id')
        )->whereNull('date_validation')->count();

        // Prochains cours (aujourd'hui + demain)
        $prochainsCours = EmploiDuTemps::whereIn('cours_id', $coursIds)
            ->where('jour', now()->locale('fr')->dayName)
            ->orWhere('jour', now()->addDay()->locale('fr')->dayName)
            ->with(['cours', 'niveau'])
            ->orderBy('jour')
            ->orderBy('heure_debut')
            ->limit(5)
            ->get()
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
}