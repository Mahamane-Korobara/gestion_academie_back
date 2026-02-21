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

        // Creer ou mettre a jour l utilisateur administrateur
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

        $this->command->info('Administrateur seede avec succes');
        $this->command->info('');
        $this->command->warn('═══════════════════════════════════════════════════════');
        $this->command->warn('   INFORMATIONS DE CONNEXION ADMINISTRATEUR');
        $this->command->warn('═══════════════════════════════════════════════════════');
        $this->command->info('   Email    : admin@gestion-academique.ml');
        $this->command->info('   Password : admin123456');
        $this->command->warn('═══════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->error(' IMPORTANT : Change ce mot de passe en production !');
    }
}
