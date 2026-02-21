<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cette migration sert à définir proprement le comportement de retour
     * en arrière pour la gestion des semestres dans la table cours.
     */
    public function up(): void
    {
        // On ne fait rien ici car ta table est déjà dans l'état souhaité
        // (semestre_id existe déjà et l'index est posé)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cours', function (Blueprint $table) {
            $table->dropForeign(['semestre_id']);
            $table->dropColumn('semestre_id');
            $table->enum('semestre', ['S1', 'S2'])->nullable()->after('niveau_id');
        });
    }
};