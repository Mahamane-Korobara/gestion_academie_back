<?php

namespace App\Providers;

use App\Models\Evaluation;
use App\Models\Note;
use App\Policies\NotePolicy;
use App\Policies\EvaluationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Evaluation::class => EvaluationPolicy::class,
        Note::class => NotePolicy::class,
    ];
}
