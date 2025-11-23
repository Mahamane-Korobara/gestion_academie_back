<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants')->onDelete('cascade');
            $table->foreignId('evaluation_id')->constrained('evaluations')->onDelete('cascade');
            $table->decimal('note', 5, 2); // Ex: 15.75
            $table->boolean('is_absent')->default(false);
            $table->text('commentaire')->nullable();
            $table->enum('statut', ['brouillon', 'soumise', 'validee'])->default('brouillon');
            $table->foreignId('saisi_par')->constrained('users'); // Professeur qui a saisi
            $table->foreignId('valide_par')->nullable()->constrained('users'); // Admin qui a validé
            $table->timestamp('date_saisie');
            $table->timestamp('date_validation')->nullable();
            $table->timestamps();

            $table->unique(['etudiant_id', 'evaluation_id']);
            $table->index(['evaluation_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
