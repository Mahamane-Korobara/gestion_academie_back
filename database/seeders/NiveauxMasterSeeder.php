<?php

namespace Database\Seeders;

use App\Models\Filiere;
use App\Models\Niveau;
use Illuminate\Database\Seeder;

class NiveauxMasterSeeder extends Seeder
{
    public function run(): void
    {
        $codes = ['MIG', 'MRT', 'MFCA', 'MDA'];

        foreach ($codes as $code) {
            $filiere = Filiere::where('code', $code)->first();
            if (!$filiere) {
                continue;
            }

            Niveau::updateOrCreate(
                ['filiere_id' => $filiere->id, 'nom' => 'M1'],
                ['ordre' => 4, 'nombre_semestres' => 2]
            );

            Niveau::updateOrCreate(
                ['filiere_id' => $filiere->id, 'nom' => 'M2'],
                ['ordre' => 5, 'nombre_semestres' => 2]
            );
        }

        $this->command?->info('Niveaux master M1/M2 seedees par filiere.');
    }
}
