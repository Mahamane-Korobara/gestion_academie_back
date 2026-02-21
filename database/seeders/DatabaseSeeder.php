<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            TypeEvaluationSeeder::class,
            AdminSeeder::class,
            FilieresMasterSeeder::class,
            NiveauxMasterSeeder::class,
            ProfesseursEtudiantsSeeder::class,
            CoursMasterSeeder::class,
            InscriptionsMasterSeeder::class,
            EmploiDuTempsMasterSeeder::class,
        ]);
    }
}
