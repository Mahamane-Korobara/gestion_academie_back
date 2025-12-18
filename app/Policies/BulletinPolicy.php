<?php

namespace App\Policies;

use App\Models\Bulletin;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BulletinPolicy
{
    public function manage(User $user)
    {
        return $user->isAdmin();
    }

}
