<?php

namespace App\Providers;

use App\Models\Note;
use App\Observers\NoteObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // On lie l'Observer au modèle Note
        Note::observe(NoteObserver::class);
    }
}
