<?php

namespace App\Providers;

use App\Models\Evaluation;
use App\Models\Note;
use App\Models\Bulletin;
use App\Models\Etudiant;
use App\Policies\EtudiantPolicy;
use App\Policies\BulletinPolicy;
use App\Policies\NotePolicy;
use App\Policies\EvaluationPolicy;
use App\Models\EmploiDuTemps;
use App\Policies\EmploiDuTempsPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Evaluation::class => EvaluationPolicy::class,
        Note::class => NotePolicy::class,
        Bulletin::class => BulletinPolicy::class,
        Etudiant::class => EtudiantPolicy::class,
        EmploiDuTemps::class => EmploiDuTempsPolicy::class
    ];
}
