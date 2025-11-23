<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->onDelete('cascade');
            $table->foreignId('semestre_id')->constrained('semestres')->onDelete('cascade');
            $table->decimal('moyenne_generale', 5, 2); // Ex: 14.50
            $table->integer('rang')->nullable();
            $table->integer('total_etudiants')->nullable();
            $table->text('observations')->nullable();
            $table->enum('decision', [
                'admis', 
                'rattrapage', 
                'redoublant', 
                'diplome',
                'passe_classe_superieure'
            ])->nullable();
            $table->string('fichier_pdf')->nullable(); // Chemin du PDF généré
            $table->boolean('est_genere')->default(false);
            $table->timestamp('date_generation')->nullable();
            $table->foreignId('genere_par')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['etudiant_id', 'semestre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletins');
    }
};
