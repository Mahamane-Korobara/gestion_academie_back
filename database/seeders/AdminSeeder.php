<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer l'ID du rôle admin
        $roleAdminId = DB::table('roles')->where('name', 'admin')->first()->id;

        // Créer l'utilisateur administrateur
        $userId = DB::table('users')->insertGetId([
            'role_id' => $roleAdminId,
            'name' => 'Administrateur Système',
            'email' => 'admin@gestion-academique.ml',
            'phone' => '0550000000',
            'password' => Hash::make('admin123456'), // Mot de passe par défaut
            'is_active' => true,
            'must_change_password' => false, // L'admin n'a pas besoin de changer son mot de passe
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info(' Administrateur créé avec succès !');
        $this->command->info('');
        $this->command->warn('═══════════════════════════════════════════════════════');
        $this->command->warn('   INFORMATIONS DE CONNEXION ADMINISTRATEUR');
        $this->command->warn('═══════════════════════════════════════════════════════');
        $this->command->info('   Email    : admin@gestion-academique.dz');
        $this->command->info('   Password : admin123456');
        $this->command->warn('═══════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->error(' IMPORTANT : Change ce mot de passe en production !');
    }
}

