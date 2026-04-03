<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semestre_id')->constrained('semestres');
            $table->foreignId('filiere_id')->nullable()->constrained('filieres');
            $table->foreignId('niveau_id')->nullable()->constrained('niveaux');
            $table->foreignId('exported_by')->constrained('users');
            $table->timestamp('exported_at');
            $table->string('status')->default('active'); // active | obsolete
            $table->timestamp('obsolete_at')->nullable();
            $table->unsignedInteger('notes_count')->default(0);
            $table->string('file_path');
            $table->timestamps();

            $table->index(['semestre_id', 'filiere_id', 'niveau_id', 'status'], 'notes_exports_scope_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes_exports');
    }
};
