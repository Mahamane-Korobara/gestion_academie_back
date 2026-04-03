<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Etudiant;

class EtudiantPolicy
{
    /**
     * Vérifie si l'utilisateur est bien l'étudiant concerné
     */
    private function isOwner(User $user, Etudiant $etudiant): bool
    {
        // On vérifie si l'user est un étudiant ET si son profil lié correspond
        return $user->isEtudiant() && $user->etudiant?->id === $etudiant->id;
    }

    public function view(User $user, Etudiant $etudiant): bool
    {
        return $this->isOwner($user, $etudiant);
    }
    public function consulterNotes(User $user, Etudiant $etudiant): bool
    {
        return $this->isOwner($user, $etudiant);
    }
}