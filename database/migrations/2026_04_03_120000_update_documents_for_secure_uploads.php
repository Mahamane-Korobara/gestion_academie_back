<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->string('extension', 20)->nullable()->after('fichier_original');
            $table->string('mime_type', 128)->nullable()->after('extension');
        });

        // Convertir "type" enum -> string nullable (pour accepter tous types)
        DB::statement("ALTER TABLE documents MODIFY type VARCHAR(50) NULL");

        // Backfill uuid + extension pour les documents existants
        $documents = DB::table('documents')->select('id', 'fichier_original')->get();
        foreach ($documents as $document) {
            $ext = pathinfo($document->fichier_original ?? '', PATHINFO_EXTENSION);
            DB::table('documents')->where('id', $document->id)->update([
                'uuid' => (string) Str::uuid(),
                'extension' => $ext ? Str::lower($ext) : null,
            ]);
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn(['uuid', 'extension', 'mime_type']);
        });
    }
};
