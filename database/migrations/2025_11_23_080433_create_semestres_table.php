<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semestres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->onDelete('cascade');
            $table->enum('numero', ['S1', 'S2']);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->date('date_debut_examens')->nullable();
            $table->date('date_fin_examens')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['annee_academique_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semestres');
    }
};
