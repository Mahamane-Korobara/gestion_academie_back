<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Document;
use App\Models\Etudiant;

class DocumentPolicy
{
    /**
     * Seul un professeur peut créer des documents
     */
    public function create(User $user): bool
    {
        return $user->isProfesseur();
    }

    /**
     * Un professeur peut gérer ses propres documents
     */
    public function manage(User $user, Document $document): bool
    {
        return $user->id === $document->expediteur_id;
    }

    /**
     * Un étudiant peut consulter les documents de ses cours
     */
    public function view(User $user, Document $document): bool
    {
        if (!$user->isEtudiant() || !$user->etudiant) {
            return false;
        }

        return $user->etudiant->filiere_id === $document->filiere_id &&
               $user->etudiant->niveau_id === $document->niveau_id &&
               $user->etudiant->inscriptions->pluck('cours_id')->contains($document->cours_id);
    }

    /**
     * Un professeur peut consulter ses propres documents
     */
    public function viewProfesseur(User $user, Document $document): bool
    {
        return $user->id === $document->expediteur_id;
    }
}