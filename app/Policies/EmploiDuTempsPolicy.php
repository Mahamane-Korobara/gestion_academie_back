<?php

namespace App\Policies;

use App\Models\User;
use App\Models\EmploiDuTemps;

class EmploiDuTempsPolicy
{
    /**
     * Seul un admin peut gérer les emplois du temps
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Un professeur peut consulter son emploi du temps
     */
    public function viewProfesseur(User $user): bool
    {
        return $user->isProfesseur();
    }

    /**
     * Un professeur peut consulter un emploi du temps spécifique uniquement s'il lui appartient
     */
    public function view(User $user, EmploiDuTemps $emploi): bool
    {
        return $user->isProfesseur() && $user->professeur->id === $emploi->professeur_id;
    }

    /**
     * Un étudiant peut consulter les emplois du temps
     */
    public function viewEtudiant(User $user): bool
    {
        return $user->isEtudiant();
    }
}