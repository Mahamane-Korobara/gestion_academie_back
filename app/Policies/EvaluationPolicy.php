<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Note;
use App\Models\Evaluation;

class EvaluationPolicy
{
    /**
     * Un professeur peut saisir les notes
     * uniquement pour les évaluations de ses cours
     */
    public function saisirNotes(User $user, Evaluation $evaluation): bool
    {
        // Admin = autorisé
        if ($user->isAdmin()) {
            return true;
        }

        // Pas professeur = refusé
        if (!$user->isProfesseur()) {
            return false;
        }

        // Le professeur doit enseigner le cours de l'évaluation
        return $evaluation->cours
            ->professeurs
            ->contains('user_id', $user->id);
    }

    /**
     * Un admin peut valider n'importe quelle note
     */
    // public function validerNotes(User $user, Note $note): bool
    // {
    //     return $user->isAdmin();
    // }

    // public function toutVoir(User $user): bool
    // {
    //     return $user->isAdmin();
    // }
}
