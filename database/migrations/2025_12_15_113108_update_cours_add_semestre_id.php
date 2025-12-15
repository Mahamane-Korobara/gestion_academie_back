<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use \App\Models\AnneeAcademique;
use \App\Models\Semestre;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cours', function (Blueprint $table) {
            $table->foreignId('semestre_id')->nullable()->after('niveau_id');
        });

        // Associer chaque cours à un semestre existant
        $annees = AnneeAcademique::all();
        foreach ($annees as $annee) {
            $s1 = Semestre::where('annee_academique_id', $annee->id)
                ->where('numero', 'S1')->first();
            $s2 = Semestre::where('annee_academique_id', $annee->id)
                ->where('numero', 'S2')->first();

            if ($s1) {
                DB::table('cours')
                    ->where('annee_academique_id', $annee->id)
                    ->where('semestre', 'S1')
                    ->update(['semestre_id' => $s1->id]);
            }
            if ($s2) {
                DB::table('cours')
                    ->where('annee_academique_id', $annee->id)
                    ->where('semestre', 'S2')
                    ->update(['semestre_id' => $s2->id]);
            }
        }

        Schema::table('cours', function (Blueprint $table) {
            $table->dropColumn('semestre');
            $table->foreign('semestre_id')->references('id')->on('semestres')->onDelete('cascade');
            $table->index(['niveau_id', 'semestre_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cours', function (Blueprint $table) {
            //
        });
    }
};
