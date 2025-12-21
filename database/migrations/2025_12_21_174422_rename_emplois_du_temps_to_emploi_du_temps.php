<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::rename('emplois_du_temps', 'emploi_du_temps');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::rename('emploi_du_temps', 'emplois_du_temps');

        Schema::enableForeignKeyConstraints();
    }
};
