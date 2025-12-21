<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->index(
                ['professeur_id', 'semestre_id', 'jour'],
                'edt_professeur_semestre_jour_idx'
            );

            $table->index(
                ['niveau_id', 'semestre_id', 'jour'],
                'edt_niveau_semestre_jour_idx'
            );

            $table->index(
                ['salle_id', 'semestre_id', 'jour'],
                'edt_salle_semestre_jour_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('emploi_du_temps', function (Blueprint $table) {
            $table->dropIndex('edt_professeur_semestre_jour_idx');
            $table->dropIndex('edt_niveau_semestre_jour_idx');
            $table->dropIndex('edt_salle_semestre_jour_idx');
        });
    }
};
