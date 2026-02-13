<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Semestre;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Support\Facades\DB;

class UpdateActiveSemestre extends Command
{
    protected $signature = 'semestre:auto-update';
    protected $description = 'Vérifie les dates et met à jour le semestre actif';

    public function handle()
    {
        $today = now()->format('Y-m-d');

        // Trouver le semestre qui devrait être actif selon les dates
        $semestreReel = Semestre::where('date_debut', '<=', $today)
            ->where('date_fin', '>=', $today)
            ->first();

        if (!$semestreReel) {
            $this->info('Aucun semestre ne correspond à la date d\'aujourd\'hui.');
            return;
        }

        // Si ce semestre n'est pas déjà marqué actif on fait la bascule
        if (!$semestreReel->is_active) {
            DB::transaction(function () use ($semestreReel) {
                // Désactiver tout le monde
                Semestre::query()->update(['is_active' => false]);
                
                // Activer le bon
                $semestreReel->update(['is_active' => true]);

                // Logger l'action système
                LogService::write(
                    ActionLog::UPDATE,
                    "Bascule automatique vers le semestre : {$semestreReel->numero->value}",
                    $semestreReel,
                    null, 
                    ['trigger' => 'scheduler']
                );

                // Vider le cache (crucial car le controller utilise activement le cache)
                CacheService::forget([
                    'semestre:actif',
                    "semestres:annee:{$semestreReel->annee_academique_id}",
                    CacheService::KEYS['stats_dashboard'] ?? 'stats_dashboard' 
                ]);
            });

            $this->info("Bascule effectuée vers {$semestreReel->numero->value}");
        } else {
            $this->info("Le semestre actif est déjà à jour.");
        }
    }
}