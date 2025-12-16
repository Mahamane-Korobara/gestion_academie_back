<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Note;

class NotePolicy
{
    // Pour la liste de toutes les notes en attente
    public function toutVoir(User $user): bool
    {
        return $user->isAdmin();
    }

    // Pour valider une note
    public function validerNotes(User $user, Note $note): bool
    {
        return $user->isAdmin();
    }
}
