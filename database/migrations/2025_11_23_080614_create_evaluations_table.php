<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cours_id')->constrained('cours')->onDelete('cascade');
            $table->foreignId('type_evaluation_id')->constrained('types_evaluations')->onDelete('restrict');
            $table->foreignId('semestre_id')->constrained('semestres')->onDelete('cascade');
            $table->string('titre'); // Ex: "Contrôle 1", "Examen Final"
            $table->decimal('coefficient', 4, 2); // Coefficient spécifique à cette évaluation
            $table->date('date_evaluation')->nullable();
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
            $table->foreignId('salle_id')->nullable()->constrained('salles')->onDelete('set null');
            $table->text('instructions')->nullable();
            $table->enum('statut', ['planifiee', 'en_cours', 'terminee', 'annulee'])->default('planifiee');
            $table->timestamps();

            $table->index(['cours_id', 'semestre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
