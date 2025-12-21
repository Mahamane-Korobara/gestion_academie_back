<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\JourSemaine;
use App\Enums\TypeSeance;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emploi_du_temps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cours_id')->constrained('cours')->onDelete('cascade');
            $table->foreignId('niveau_id')->constrained('niveaux')->onDelete('cascade'); // ← Ajouter ici
            $table->foreignId('professeur_id')->constrained('professeurs')->onDelete('cascade');
            $table->foreignId('salle_id')->nullable()->constrained('salles')->onDelete('set null');
            $table->foreignId('semestre_id')->constrained('semestres')->onDelete('cascade');
            
            // les valeurs de l'Enum
            $joursValues = array_column(JourSemaine::cases(), 'value');
            $typeSeanceValues = array_column(TypeSeance::cases(), 'value');
            
            $table->enum('jour', $joursValues);
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->enum('type_seance', $typeSeanceValues)->default('cours');
            $table->timestamps();

            $table->index(['semestre_id', 'jour']);
            
            // Contraintes d'unicité pour éviter les conflits
            $table->unique(['jour', 'heure_debut', 'heure_fin', 'niveau_id', 'semestre_id'], 'unique_niveau_creneau');
            $table->unique(['jour', 'heure_debut', 'heure_fin', 'professeur_id', 'semestre_id'], 'unique_prof_creneau');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emploi_du_temps');
    }
};
