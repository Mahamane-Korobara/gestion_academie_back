<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cours', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('coefficient', 4, 2); // Ex: 2.00, 1.50
            $table->integer('nombre_heures')->nullable();
            $table->foreignId('niveau_id')->constrained('niveaux')->onDelete('cascade');
            $table->enum('semestre', ['S1', 'S2']);
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['niveau_id', 'semestre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cours');
    }
};
