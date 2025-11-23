<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annonces', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('contenu');
            $table->enum('type', ['globale', 'filiere', 'niveau', 'cours', 'individuelle'])->default('globale');
            $table->foreignId('filiere_id')->nullable()->constrained('filieres')->onDelete('cascade');
            $table->foreignId('niveau_id')->nullable()->constrained('niveaux')->onDelete('cascade');
            $table->foreignId('cours_id')->nullable()->constrained('cours')->onDelete('cascade');
            $table->foreignId('destinataire_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('auteur_id')->constrained('users')->onDelete('cascade');
            $table->enum('priorite', ['normale', 'importante', 'urgente'])->default('normale');
            $table->date('date_expiration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('date_expiration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annonces');
    }
};
