<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etudiants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('matricule')->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->date('date_naissance');
            $table->enum('sexe', ['M', 'F']);
            $table->string('lieu_naissance')->nullable();
            $table->string('adresse')->nullable();
            $table->string('email_personnel')->nullable();
            $table->string('telephone')->nullable();
            $table->string('telephone_urgence')->nullable();
            $table->foreignId('filiere_id')->constrained('filieres')->onDelete('restrict');
            $table->foreignId('niveau_id')->constrained('niveaux')->onDelete('restrict');
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->onDelete('restrict');
            $table->enum('statut', [
                'actif', 
                'redoublant', 
                'rattrapage', 
                'diplome', 
                'passe_classe_superieure',
                'abandonne',
                'suspendu'
            ])->default('actif');
            $table->date('date_inscription');
            $table->timestamps();

            $table->index(['filiere_id', 'niveau_id']);
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etudiants');
    }
};
