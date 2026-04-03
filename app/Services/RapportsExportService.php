<?php

namespace App\Services;

use App\Exports\ArrayExport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class RapportsExportService
{
    public function export(array $payload, User $user): array
    {
        $anneeId = (int) $payload['annee_academique_id'];
        $format = $payload['format'] ?? 'excel';
        $include = $payload['include'] ?? [];

        $annee = DB::table('annees_academiques')->where('id', $anneeId)->first();
        $anneeLabel = $annee?->annee ?? ('annee_' . $anneeId);

        $context = $this->buildContext($anneeId);
        $context['anneeLabel'] = $anneeLabel;
        $context['include'] = is_array($include) ? $include : [];

        $timestamp = now()->format('Ymd_His');
        $batchId = 'rapports_' . $anneeId . '_' . $timestamp;
        $baseDir = "exports/rapports/{$batchId}";

        Storage::disk('local')->makeDirectory($baseDir);

        $tables = $this->listTables();

        if ($format === 'sql') {
            return $this->exportSql($tables, $baseDir, $context);
        }

        return $this->exportExcel($tables, $baseDir, $context);
    }

    private function buildContext(int $anneeId): array
    {
        $semestreIds = DB::table('semestres')
            ->where('annee_academique_id', $anneeId)
            ->pluck('id')
            ->all();

        $evaluationIds = empty($semestreIds)
            ? []
            : DB::table('evaluations')->whereIn('semestre_id', $semestreIds)->pluck('id')->all();

        $coursIds = DB::table('cours')
            ->where('annee_academique_id', $anneeId)
            ->pluck('id')
            ->all();

        return [
            'anneeId' => $anneeId,
            'semestreIds' => $semestreIds,
            'evaluationIds' => $evaluationIds,
            'coursIds' => $coursIds,
        ];
    }

    private function listTables(): array
    {
        $rows = DB::select('SHOW TABLES');
        $tables = [];
        foreach ($rows as $row) {
            $tables[] = array_values((array) $row)[0];
        }
        return $tables;
    }

    private function excelExcludedTables(): array
    {
        return [
            'migrations',
            'jobs',
            'failed_jobs',
            'job_batches',
            'cache',
            'cache_locks',
            'sessions',
            'password_reset_tokens',
            'personal_access_tokens',
            'notifications',
            'roles',
            'log_activites',
        ];
    }

    private function shouldIncludeTable(string $table, array $include): bool
    {
        if (empty($include)) {
            return true;
        }

        $map = [
            'users' => 'users',
            'roles' => 'roles',
            'etudiants' => 'etudiants',
            'professeurs' => 'professeurs',
            'filieres' => 'filieres',
            'niveaux' => 'niveaux',
            'cours' => 'cours',
            'cours_professeur' => 'affectations',
            'inscriptions' => 'inscriptions',
            'evaluations' => 'evaluations',
            'notes' => 'notes',
            'types_evaluations' => 'types_evaluations',
            'salles' => 'salles',
            'semestres' => 'semestres',
            'annees_academiques' => 'annees_academiques',
            'emploi_du_temps' => 'emplois_du_temps',
            'annonces' => 'annonces',
            'messages' => 'messages',
            'documents' => 'documents',
            'log_activites' => 'log_activites',
            'notifications' => 'notifications',
            'notes_exports' => 'notes_exports',
        ];

        if (!array_key_exists($table, $map)) {
            return true;
        }

        $key = $map[$table];

        if (!array_key_exists($key, $include)) {
            return true;
        }

        return (bool) $include[$key];
    }

    private function buildQuery(string $table, array $context)
    {
        $query = DB::table($table);

        if ($table === 'annees_academiques') {
            return $query->where('id', $context['anneeId']);
        }

        if ($table === 'notes') {
            if (empty($context['evaluationIds'])) {
                return $query->whereRaw('1 = 0');
            }
            return $query->whereIn('evaluation_id', $context['evaluationIds']);
        }

        if ($table === 'documents') {
            if (empty($context['coursIds'])) {
                return $query->whereRaw('1 = 0');
            }
            return $query->whereIn('cours_id', $context['coursIds']);
        }

        if ($table === 'annonces') {
            if (!empty($context['coursIds'])) {
                return $query->where(function ($q) use ($context) {
                    $q->whereIn('cours_id', $context['coursIds'])
                        ->orWhereNull('cours_id');
                });
            }
            return $query;
        }

        if (Schema::hasColumn($table, 'annee_academique_id')) {
            return $query->where('annee_academique_id', $context['anneeId']);
        }

        if (Schema::hasColumn($table, 'semestre_id')) {
            if (empty($context['semestreIds'])) {
                return $query->whereRaw('1 = 0');
            }
            return $query->whereIn('semestre_id', $context['semestreIds']);
        }

        return $query;
    }

    private function sanitizeRow(string $table, array $row): array
    {
        if ($table === 'users') {
            if (array_key_exists('password', $row)) {
                $row['password'] = '';
            }
            if (array_key_exists('remember_token', $row)) {
                $row['remember_token'] = null;
            }
        }

        if (in_array($table, ['password_reset_tokens', 'personal_access_tokens'], true)) {
            if (array_key_exists('token', $row)) {
                $row['token'] = '';
            }
        }

        return $row;
    }

    private function normalizeValue($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $value;
    }

    private function normalizeRow(array $row, array $columns): array
    {
        $normalized = [];
        foreach ($columns as $column) {
            $value = $row[$column] ?? null;
            $normalized[$column] = $this->normalizeValue($value);
        }
        return $normalized;
    }

    private function exportExcel(array $tables, string $baseDir, array $context): array
    {
        foreach ($tables as $table) {
            if (in_array($table, $this->excelExcludedTables(), true)) {
                continue;
            }

            if (!$this->shouldIncludeTable($table, $context['include'])) {
                continue;
            }

            if (!Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $rows = $this->buildQuery($table, $context)
                ->get($columns)
                ->map(fn ($row) => (array) $row)
                ->map(fn ($row) => $this->sanitizeRow($table, $row))
                ->map(fn ($row) => $this->normalizeRow($row, $columns))
                ->toArray();

            $excelRows = array_map(function ($row) use ($columns) {
                return array_map(fn ($col) => $row[$col] ?? null, $columns);
            }, $rows);

            $safeName = Str::slug($table, '_');
            $relativePath = "{$baseDir}/{$safeName}.xlsx";

            Excel::store(new ArrayExport($excelRows, $columns), $relativePath, 'local');
        }

        $zipRoot = "rapports_{$context['anneeLabel']}";
        $zipRelativePath = "{$baseDir}.zip";
        $zipFullPath = Storage::disk('local')->path($zipRelativePath);

        $zip = new \ZipArchive();
        $zip->open($zipFullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach (Storage::disk('local')->files($baseDir) as $file) {
            $zip->addFile(Storage::disk('local')->path($file), "{$zipRoot}/" . basename($file));
        }
        $zip->close();

        return [
            'path' => $zipFullPath,
            'filename' => basename($zipRelativePath),
            'content_type' => 'application/zip',
        ];
    }

    private function exportSql(array $tables, string $baseDir, array $context): array
    {
        $safeLabel = Str::slug($context['anneeLabel'], '_');
        $sqlRelativePath = "{$baseDir}/rapports_{$safeLabel}.sql";
        $sqlFullPath = Storage::disk('local')->path($sqlRelativePath);

        $handle = fopen($sqlFullPath, 'w');
        fwrite($handle, "-- Export données admin\n");
        fwrite($handle, "-- Année académique: {$context['anneeLabel']}\n");
        fwrite($handle, "-- Généré le: " . now()->format('Y-m-d H:i:s') . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $rows = $this->buildQuery($table, $context)
                ->get($columns)
                ->map(fn ($row) => (array) $row)
                ->map(fn ($row) => $this->sanitizeRow($table, $row))
                ->map(fn ($row) => $this->normalizeRow($row, $columns))
                ->toArray();

            fwrite($handle, "-- Table {$table}\n");

            if (empty($rows)) {
                fwrite($handle, "-- (0 ligne)\n\n");
                continue;
            }

            $escapedColumns = array_map(fn ($col) => "`{$col}`", $columns);
            $columnsSql = implode(', ', $escapedColumns);

            foreach ($rows as $row) {
                $values = [];
                foreach ($columns as $column) {
                    $values[] = $this->sqlValue($row[$column] ?? null);
                }
                $valuesSql = implode(', ', $values);
                fwrite($handle, "INSERT INTO `{$table}` ({$columnsSql}) VALUES ({$valuesSql});\n");
            }

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return [
            'path' => $sqlFullPath,
            'filename' => basename($sqlRelativePath),
            'content_type' => 'text/plain',
        ];
    }

    private function sqlValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        $escaped = str_replace("'", "''", (string) $value);
        return "'{$escaped}'";
    }
}
