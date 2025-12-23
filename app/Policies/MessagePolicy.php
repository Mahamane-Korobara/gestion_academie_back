<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Message;

class MessagePolicy
{
    /**
     * Un utilisateur peut envoyer un message s'il est connecté
     */
    public function create(User $user): bool
    {
        return $user->isEtudiant() || $user->isProfesseur() || $user->isAdmin();
    }

    /**
     * Un utilisateur peut voir un message s'il est l'expéditeur ou le destinataire
     */
    public function view(User $user, Message $message): bool
    {
        return $user->id === $message->expediteur_id 
            || $user->id === $message->destinataire_id;
    }

    /**
     * Un utilisateur peut supprimer un message s'il est l'expéditeur ou le destinataire
     */
    public function delete(User $user, Message $message): bool
    {
        return $user->id === $message->expediteur_id 
            || $user->id === $message->destinataire_id;
    }

    /**
     * Un utilisateur peut marquer comme lu s'il est le destinataire
     */
    public function markAsRead(User $user, Message $message): bool
    {
        return $user->id === $message->destinataire_id;
    }
}