<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // On oublie pas cet import

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- Planification du système universitaire ---

// Vérifie chaque jour à minuit si on doit changer de semestre
Schedule::command('semestre:auto-update')
    ->daily()
    ->at('00:00') // Optionnel, daily() le fait déjà, mais c'est explicite
    ->appendOutputTo(storage_path('logs/scheduler.log')); // Utile pour debugger