<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\TypeDocument;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Supprime les données existantes (car incompatibles)
            DB::table('documents')->truncate();
            
            // Supprime l'ancienne colonne
            $table->dropColumn('type');
        });

        Schema::table('documents', function (Blueprint $table) {
            // Recrée avec le bon enum
            $table->enum('type', TypeDocument::values())
                  ->charset('utf8mb4')
                  ->collation('utf8mb4_unicode_ci')
                  ->after('description')
                  ->nullable(false);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};