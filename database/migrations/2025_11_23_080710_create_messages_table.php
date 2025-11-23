<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediteur_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('destinataire_id')->constrained('users')->onDelete('cascade');
            $table->string('sujet');
            $table->text('contenu');
            $table->boolean('is_lu')->default(false);
            $table->timestamp('date_lecture')->nullable();
            $table->foreignId('message_parent_id')->nullable()->constrained('messages')->onDelete('cascade');
            $table->timestamps();

            $table->index(['destinataire_id', 'is_lu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};