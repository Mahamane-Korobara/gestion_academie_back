<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // On redéfinit la colonne avec la nouvelle liste incluant 'ajourne'
        // Note : On garde le NULL à la fin car ma colonne est nullable
        DB::statement("ALTER TABLE bulletins MODIFY COLUMN decision ENUM('admis', 'rattrapage', 'redoublant', 'diplome', 'passe_classe_superieure', 'ajourne') NULL");
    }

    public function down(): void
    {
        // En cas de rollback, on revient à l'ancienne liste
        // Attention : Si tu as des lignes avec 'ajourne', le rollback échouera ou tronquera les données !
        DB::statement("ALTER TABLE bulletins MODIFY COLUMN decision ENUM('admis', 'rattrapage', 'redoublant', 'diplome', 'passe_classe_superieure') NULL");
    }
};