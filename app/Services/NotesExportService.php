<?php

namespace App\Services;

use App\Exports\ArrayExport;
use App\Models\Note;
use App\Models\NotesExport;
use App\Models\Semestre;
use App\Models\User;
use App\Enums\StatutNote;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class NotesExportService
{
    public function export(array $filters, User $user): array
    {
        $semestre = Semestre::with('anneeAcademique')->findOrFail($filters['semestre_id']);

        $notesQuery = Note::query()
            ->where('statut', StatutNote::SOUMISE->value)
            ->whereHas('evaluation', fn ($q) => $q->where('semestre_id', $semestre->id))
            ->whereHas('evaluation.typeEvaluation', fn ($q) => $q->where('code', 'EF'))
            ->with([
                'etudiant.user',
                'etudiant.filiere',
                'etudiant.niveau',
                'evaluation.cours',
                'evaluation.semestre.anneeAcademique',
            ]);

        if (!empty($filters['filiere_id'])) {
            $notesQuery->whereHas('etudiant', fn ($q) => $q->where('filiere_id', $filters['filiere_id']));
        }

        if (!empty($filters['niveau_id'])) {
            $notesQuery->whereHas('etudiant', fn ($q) => $q->where('niveau_id', $filters['niveau_id']));
        }

        $notes = $notesQuery->get();

        $notesByEtudiant = $notes->groupBy('etudiant_id');

        $timestamp = now()->format('Ymd_His');
        $semestreLabel = $semestre->numero?->value ?? $semestre->numero ?? 'S';
        $anneeLabel = $semestre->anneeAcademique?->annee ?? 'NA';
        $batchId = "semestre_{$semestreLabel}_{$anneeLabel}_{$timestamp}";

        $baseDir = "exports/notes/{$batchId}";
        Storage::disk('local')->makeDirectory($baseDir);

        $manifestRows = [];
        $totalNotes = 0;

        $headings = [
            'Matricule',
            'Nom',
            'Prénom',
            'Filière',
            'Niveau',
            'Semestre',
            'Année académique',
            'Cours',
            'Coefficient',
            'Date examen',
            'Note',
            'Absent (oui/non)',
        ];

        foreach ($notesByEtudiant as $etudiantId => $rows) {
            $etudiant = $rows->first()->etudiant;

            $excelRows = $rows->map(function ($note) use ($semestre, $anneeLabel) {
                $etudiant = $note->etudiant;
                $cours = $note->evaluation?->cours;
                $dateExam = $note->evaluation?->date_evaluation;

                return [
                    $etudiant?->matricule ?? '',
                    $etudiant?->nom ?? '',
                    $etudiant?->prenom ?? '',
                    $etudiant?->filiere?->nom ?? '',
                    $etudiant?->niveau?->nom ?? '',
                    $semestre->numero?->label() ?? (string) $semestre->numero,
                    $anneeLabel,
                    $cours?->titre ?? '',
                    $cours?->coefficient ?? '',
                    $dateExam ? $dateExam->format('Y-m-d') : '',
                    $note->is_absent ? 0 : (string) $note->note,
                    $note->is_absent ? 'oui' : 'non',
                ];
            })->toArray();

            $totalNotes += count($excelRows);

            $matricule = $etudiant?->matricule ?? "etudiant_{$etudiantId}";
            $nom = $etudiant?->nom ?? '';
            $prenom = $etudiant?->prenom ?? '';
            $safeName = Str::slug("{$matricule}_{$nom}_{$prenom}", '_');
            $fileName = "{$safeName}.xlsx";
            $relativePath = "{$baseDir}/{$fileName}";

            Excel::store(new ArrayExport($excelRows, $headings), $relativePath, 'local');

            $manifestRows[] = [
                'Matricule' => $matricule,
                'Nom' => $nom,
                'Prénom' => $prenom,
                'Fichier' => $fileName,
                'Notes' => count($excelRows),
                'Semestre' => $semestre->numero?->label() ?? (string) $semestre->numero,
                'Année académique' => $anneeLabel,
                'Exporté le' => now()->format('Y-m-d H:i'),
            ];
        }

        // Manifest
        $manifestHeadings = [
            'Matricule',
            'Nom',
            'Prénom',
            'Fichier',
            'Notes',
            'Semestre',
            'Année académique',
            'Exporté le',
        ];
        $manifestPath = "{$baseDir}/manifest.xlsx";
        Excel::store(new ArrayExport($manifestRows, $manifestHeadings), $manifestPath, 'local');

        // ZIP
        $zipRoot = "notes_{$semestreLabel}_{$anneeLabel}";
        $zipRelativePath = "exports/notes/{$batchId}.zip";
        $zipFullPath = Storage::disk('local')->path($zipRelativePath);

        $zip = new \ZipArchive();
        $zip->open($zipFullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach (Storage::disk('local')->files($baseDir) as $file) {
            $zip->addFile(Storage::disk('local')->path($file), "{$zipRoot}/" . basename($file));
        }
        $zip->close();

        // Marquer anciens exports comme obsolètes pour ce scope
        NotesExport::query()
            ->where('semestre_id', $semestre->id)
            ->where('filiere_id', $filters['filiere_id'] ?? null)
            ->where('niveau_id', $filters['niveau_id'] ?? null)
            ->where('status', 'active')
            ->update([
                'status' => 'obsolete',
                'obsolete_at' => now(),
            ]);

        NotesExport::create([
            'semestre_id' => $semestre->id,
            'filiere_id' => $filters['filiere_id'] ?? null,
            'niveau_id' => $filters['niveau_id'] ?? null,
            'exported_by' => $user->id,
            'exported_at' => now(),
            'status' => 'active',
            'notes_count' => $totalNotes,
            'file_path' => $zipRelativePath,
        ]);

        return [
            'path' => $zipFullPath,
            'filename' => "{$batchId}.zip",
        ];
    }
}
