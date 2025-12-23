<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('deleted_at_expediteur')->nullable()->after('date_lecture');
            $table->timestamp('deleted_at_destinataire')->nullable()->after('deleted_at_expediteur');

            $table->index('deleted_at_expediteur');
            $table->index('deleted_at_destinataire');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['deleted_at_expediteur']);
            $table->dropIndex(['deleted_at_destinataire']);

            $table->dropColumn([
                'deleted_at_expediteur',
                'deleted_at_destinataire',
            ]);
        });
    }
};
