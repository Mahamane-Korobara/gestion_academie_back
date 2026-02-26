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
        $roleAdmin = DB::table('roles')->where('name', 'admin')->first();

        if (!$roleAdmin) {
            $this->command->error('Le rôle "admin" n\'existe pas dans la table roles !');
            return;
        }

        $roleAdminId = $roleAdmin->id;
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@gestion-academique.ml'],
            [
                'role_id' => $roleAdminId,
                'name' => 'Administrateur Systeme',
                'phone' => '0550000000',
                'password' => Hash::make('admin123456'),
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('users')->updateOrInsert(
            ['email' => 'moudiallo1@gmail.com'], // Email différent
            [
                'role_id' => $roleAdminId,
                'name' => 'Admin MEQC', // Nom différent
                'phone' => '0550000001',
                'password' => Hash::make('meqc2026'), // Mot de passe spécifique
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->command->info('Administrateurs seedés avec succès');
        $this->command->info('');
        $this->command->warn('═══════════════════════════════════════════════════════');
        $this->command->warn('   INFORMATIONS DE CONNEXION ADMINISTRATEURS');
        $this->command->warn('═══════════════════════════════════════════════════════');
        $this->command->info('   1. Email    : admin@gestion-academique.ml');
        $this->command->info('      Password : admin123456');
        $this->command->info('   -----------------------------------------------------');
        $this->command->info('   2. Email    : moudiallo1@gmail.com');
        $this->command->info('      Password : meqc2026');
        $this->command->warn('═══════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->error(' IMPORTANT : Change ces mots de passe en production !');
    }
}