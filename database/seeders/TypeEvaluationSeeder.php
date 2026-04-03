<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'nom' => 'Examen',
                'code' => 'EF',
                'coefficient_defaut' => 1.00,
                'description' => 'Examen de fin de semestre',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];


        DB::table('types_evaluations')->upsert(
            $types,
            ['code'],
            ['nom', 'coefficient_defaut', 'description', 'updated_at']
        );

        $this->command->info('Types d evaluation seedes avec succes');
    }
}
