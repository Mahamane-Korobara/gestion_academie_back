<?php

namespace App\Policies;

use App\Models\Bulletin;
use App\Models\User;

class BulletinPolicy
{
    public function manage(User $user)
    {
        return $user->isAdmin();
    }
    
    public function telechargerBulletin(User $user, Bulletin $bulletin): bool
    {
        return $user->isEtudiant() && $user->etudiant->id === $bulletin->etudiant_id;
    }

}
