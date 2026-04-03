<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediteur_id')->constrained('users')->onDelete('cascade');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('type', 50)->nullable(); // Catégorie libre
            $table->string('fichier_path'); // Chemin du fichier stocké
            $table->string('fichier_original'); // Nom original du fichier
            $table->unsignedBigInteger('taille'); // Taille en bytes

            // Ciblage
            $table->foreignId('filiere_id')->constrained('filieres')->onDelete('cascade');
            $table->foreignId('niveau_id')->constrained('niveaux')->onDelete('cascade');
            $table->foreignId('cours_id')->constrained('cours')->onDelete('cascade');
            
            // Métadonnées
            $table->timestamp('date_expiration')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
            
            // Index pour les performances
            $table->index(['cours_id', 'type']);
            $table->index(['filiere_id', 'niveau_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
