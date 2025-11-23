<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_evaluations', function (Blueprint $table) {
            $table->id();
            $table->string('nom'); // Contrôle Continu, Examen Final, TP, Projet, Rattrapage
            $table->string('code')->unique(); // CC, EF, TP, PROJ, RATT
            $table->decimal('coefficient_defaut', 4, 2)->default(1.00);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_evaluations');
    }
};
