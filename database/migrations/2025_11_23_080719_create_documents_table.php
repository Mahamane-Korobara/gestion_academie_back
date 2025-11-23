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
            $table->foreignId('etudiant_id')->constrained('etudiants')->onDelete('cascade');
            $table->enum('type', [
                'certificat_scolarite',
                'releve_notes',
                'attestation_reussite',
                'certificat_inscription',
                'diplome'
            ]);
            $table->string('titre');
            $table->string('fichier_path');
            $table->enum('statut', ['en_attente', 'en_cours', 'pret', 'delivre'])->default('en_attente');
            $table->date('date_demande');
            $table->date('date_delivrance')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['etudiant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

