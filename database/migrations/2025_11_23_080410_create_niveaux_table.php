<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('niveaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filiere_id')->constrained('filieres')->onDelete('cascade');
            $table->string('nom'); // L1, L2, L3, M1, M2
            $table->integer('ordre'); // 1, 2, 3, 4, 5
            $table->integer('nombre_semestres')->default(2);
            $table->timestamps();

            $table->unique(['filiere_id', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('niveaux');
    }
};
