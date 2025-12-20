<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Bulletin;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    /**
     * Génère et sauvegarde un bulletin au format PDF
     */
    public function genererBulletinPDF(Bulletin $bulletin): string
    {
        // Charger toutes les relations nécessaires en une seule requête
        $bulletin = Bulletin::with([
            'etudiant.niveau.filiere',
            'semestre.anneeAcademique',
            'etudiant.user'
        ])->findOrFail($bulletin->id);

        $etudiant = $bulletin->etudiant;
        $mention = $this->getMention((float) $bulletin->moyenne_generale);

        // Préparer les données
        $data = compact('bulletin', 'etudiant', 'mention');

        // Générer le PDF
        $pdf = Pdf::loadView('pdf.bulletin', $data)
            ->setPaper('a4')
            ->setOption('dpi', 150)
            ->setOption('isRemoteEnabled', true); // Pour les images externes

        // Nom du fichier
        $filename = "bulletins/{$etudiant->matricule}_{$bulletin->id}.pdf";

        // Sauvegarder sur le disque
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    /**
    * Retourne le PDF en téléchargement direct
    */
    public function telechargerBulletinPDF(Bulletin $bulletin)
    {
        // Charger les données nécessaires pour les notes détaillées
        $bulletin = Bulletin::with([
            'etudiant.niveau.filiere',
            'semestre.anneeAcademique',
            'etudiant.inscriptions.cours.evaluations.typeEvaluation',
            'etudiant.notes.evaluation.typeEvaluation'
        ])->findOrFail($bulletin->id);

        $etudiant = $bulletin->etudiant;
        $mention = $this->getMention((float) $bulletin->moyenne_generale);
        
        // Grouper les notes par cours
        $notesParCours = $etudiant->notes
            ->where('statut', 'validee')
            ->groupBy(fn($note) => $note->evaluation->cours_id)
            ->map(fn($notes) => [
                'cours' => $notes->first()->evaluation->cours,
                'notes' => $notes
            ]);

        $data = compact('bulletin', 'etudiant', 'mention', 'notesParCours');

        $pdf = Pdf::loadView('pdf.bulletin', $data)
            ->setPaper('a4')
            ->setOption('dpi', 150);

        return $pdf->download("Bulletin_{$etudiant->matricule}_{$bulletin->id}.pdf");
    }

    private function getMention(float $moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Très Bien',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez Bien',
            $moyenne >= 10 => 'Passable',
            default => 'Ajourné',
        };
    }
}