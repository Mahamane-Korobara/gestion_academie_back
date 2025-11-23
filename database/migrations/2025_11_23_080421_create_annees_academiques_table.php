<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annees_academiques', function (Blueprint $table) {
            $table->id();
            $table->string('annee'); // "2025-2026"
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('is_active')->default(false); // Une seule année active à la fois
            $table->boolean('is_cloturee')->default(false);
            $table->timestamps();

            $table->unique('annee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annees_academiques');
    }
};
