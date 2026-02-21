<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emploi_du_temps', function (Blueprint $table) {
            // On ajoute l'année académique (nullable au début pour ne pas bloquer si données présentes)
            $table->foreignId('annee_academique_id')
                  ->after('semestre_id')
                  ->nullable()
                  ->constrained('annees_academiques')
                  ->onDelete('cascade');

            // On supprime les anciennes contraintes qui ignorent l'année
            $table->dropUnique('unique_niveau_creneau');
            $table->dropUnique('unique_prof_creneau');
        });

        // Remplissage automatique 
        // On lie chaque séance à l'année du cours auquel elle appartient
        DB::statement("UPDATE emploi_du_temps SET annee_academique_id = (SELECT annee_academique_id FROM cours WHERE cours.id = emploi_du_temps.cours_id)");

        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->foreignId('annee_academique_id')->nullable(false)->change();

            $table->unique(
                ['jour', 'heure_debut', 'heure_fin', 'niveau_id', 'semestre_id', 'annee_academique_id'], 
                'unique_niv_sem_annee_creneau'
            );
            
            $table->unique(
                ['jour', 'heure_debut', 'heure_fin', 'professeur_id', 'annee_academique_id'], 
                'unique_prof_annee_creneau'
            );
        });
    }

    public function down(): void
    {
        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->dropUnique('unique_niv_sem_annee_creneau');
            $table->dropUnique('unique_prof_annee_creneau');
            $table->dropForeign(['annee_academique_id']);
            $table->dropColumn('annee_academique_id');
            
            // On remet les anciennes contraintes incohérentes
            $table->unique(['jour', 'heure_debut', 'heure_fin', 'niveau_id', 'semestre_id'], 'unique_niveau_creneau');
            $table->unique(['jour', 'heure_debut', 'heure_fin', 'professeur_id', 'semestre_id'], 'unique_prof_creneau');
        });
    }
};