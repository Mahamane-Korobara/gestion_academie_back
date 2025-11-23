<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salles', function (Blueprint $table) {
            $table->id();
            $table->string('nom'); // Ex: "Salle A101"
            $table->string('batiment')->nullable();
            $table->integer('capacite');
            $table->text('equipements')->nullable(); // JSON ou texte
            $table->boolean('is_disponible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salles');
    }
};
