<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrateur',
                'description' => 'Accès complet au système',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'professeur',
                'display_name' => 'Professeur',
                'description' => 'Gestion des cours et notes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'etudiant',
                'display_name' => 'Étudiant',
                'description' => 'Consultation des notes et bulletins',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('roles')->insert($roles);

        $this->command->info('3 rôles créés avec succès');
    }
}
