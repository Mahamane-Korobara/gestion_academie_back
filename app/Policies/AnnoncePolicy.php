<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Annonce;
use App\Enums\TypeAnnonce;

class AnnoncePolicy
{
    /**
     * Seul un admin peut gérer les annonces
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Un utilisateur peut voir les annonces qui le concernent
     */
    public function view(User $user, Annonce $annonce): bool
    {
        // Annonce globale
        if ($annonce->type === TypeAnnonce::GLOBALE) {
            return true;
        }

        // Annonce par filière
        if ($annonce->type === TypeAnnonce::FILIERE) {
            return optional($user->etudiant)->filiere_id === $annonce->filiere_id ||
                   optional($user->professeur)->cours->pluck('filiere_id')->contains($annonce->filiere_id);
        }

        // Annonce par niveau
        if ($annonce->type === TypeAnnonce::NIVEAU) {
            return optional($user->etudiant)->niveau_id === $annonce->niveau_id ||
                   optional($user->professeur)->cours->pluck('niveau_id')->contains($annonce->niveau_id);
        }

        // Annonce par cours
        if ($annonce->type === TypeAnnonce::COURS) {
            return optional($user->etudiant)->inscriptions->pluck('cours_id')->contains($annonce->cours_id) ||
                   optional($user->professeur)->cours->pluck('id')->contains($annonce->cours_id);
        }

        // Annonce individuelle
        if ($annonce->type === TypeAnnonce::INDIVIDUELLE) {
            return $user->id === $annonce->destinataire_id;
        }

        return false;
    }
}