<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cette migration ajuste l'unicité du code pour permettre la re-création 
     * annuelle des cours demandée dans CreateCoursRequest.
     */
    public function up(): void
    {
        Schema::table('cours', function (Blueprint $table) {
            // On supprime l'ancienne contrainte d'unicité sur le code seul
            // Note : Laravel nomme par défaut cet index 'cours_code_unique'
            $table->dropUnique(['code']);

            // On crée la nouvelle contrainte d'unicité combinée
            // Cela permet d'avoir MATH101 en 2024 ET MATH101 en 2025
            $table->unique(['code', 'annee_academique_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cours', function (Blueprint $table) {
            // On supprime la contrainte composée
            $table->dropUnique(['code', 'annee_academique_id']);
            
            // On remet l'unicité simple (Attention : échouera s'il y a des doublons de code)
            $table->string('code')->unique()->change();
        });
    }
};