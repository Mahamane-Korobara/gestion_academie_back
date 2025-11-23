<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // note_disponible, bulletin_genere, etc.
            $table->string('titre');
            $table->text('message');
            $table->json('data')->nullable(); // Données supplémentaires
            $table->boolean('is_lu')->default(false);
            $table->timestamp('date_lecture')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_lu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
