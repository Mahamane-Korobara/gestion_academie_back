<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulletins', function (Blueprint $table) {
            // Rendre semestre_id nullable
            $table->foreignId('semestre_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bulletins', function (Blueprint $table) {
            // Remettre semestre_id non nullable si rollback
            $table->foreignId('semestre_id')->nullable(false)->change();
        });
    }
};
