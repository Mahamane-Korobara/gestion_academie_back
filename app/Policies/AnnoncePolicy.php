<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Annonce;
use App\Enums\TypeAnnonce;

class AnnoncePolicy
{
    /**
     * Les admins peuvent tout gérer
     * Les professeurs peuvent gérer leurs propres annonces (sauf globales)
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin() || $user->isProfesseur();
    }

    /**
     * Vérifier si l'utilisateur peut voir toutes les annonces (liste complète)
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isProfesseur();
    }

    /**
     * Un utilisateur peut voir les annonces qui le concernent
     */
    public function view(User $user, Annonce $annonce): bool
    {
        // Les admins peuvent tout voir
        if ($user->isAdmin()) {
            return true;
        }

        // Le créateur peut voir sa propre annonce
        if ($annonce->auteur_id === $user->id) {
            return true;
        }

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

    /**
     * Vérifier si l'utilisateur peut créer une annonce
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isProfesseur();
    }

    /**
     * Vérifier si l'utilisateur peut modifier une annonce
     */
    public function update(User $user, Annonce $annonce): bool
    {
        // Les admins peuvent tout modifier
        if ($user->isAdmin()) {
            return true;
        }

        // Les professeurs peuvent modifier uniquement leurs propres annonces
        if ($user->isProfesseur() && $annonce->auteur_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier si l'utilisateur peut supprimer une annonce
     */
    public function delete(User $user, Annonce $annonce): bool
    {
        // Les admins peuvent tout supprimer
        if ($user->isAdmin()) {
            return true;
        }

        // Les professeurs peuvent supprimer uniquement leurs propres annonces
        if ($user->isProfesseur() && $annonce->auteur_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier si l'utilisateur peut créer une annonce globale
     */
    public function createGlobale(User $user): bool
    {
        // Seuls les admins peuvent créer des annonces globales
        return $user->isAdmin();
    }

    /**
     * Vérifier si l'utilisateur peut activer/désactiver une annonce
     */
    public function toggleActive(User $user, Annonce $annonce): bool
    {
        // Les admins peuvent activer/désactiver toutes les annonces
        if ($user->isAdmin()) {
            return true;
        }

        // Les professeurs peuvent activer/désactiver uniquement leurs propres annonces
        if ($user->isProfesseur() && $annonce->auteur_id === $user->id) {
            return true;
        }

        return false;
    }
}