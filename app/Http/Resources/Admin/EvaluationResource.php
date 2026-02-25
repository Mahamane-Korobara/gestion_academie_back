<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray($request): array
    {
        $nbNotesSaisies = isset($this->notes_count) ? (int) $this->notes_count : null;
        $nbNotesValidees = isset($this->notes_validees_count) ? (int) $this->notes_validees_count : null;
        $nbNotesTotales = isset($this->inscriptions_count) ? (int) $this->inscriptions_count : null;

        $etatNotes = null;
        if ($nbNotesTotales !== null) {
            if ($nbNotesTotales > 0 && $nbNotesValidees !== null && $nbNotesValidees >= $nbNotesTotales) {
                $etatNotes = 'validee';
            } else {
                $etatNotes = 'en_cours';
            }
        }

        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'coefficient' => (float) $this->coefficient,
            'date_evaluation' => $this->date_evaluation,
            'heure_debut' => $this->heure_debut,
            'heure_fin' => $this->heure_fin,
            'statut' => $this->statut,
            'instructions' => $this->instructions,
            'nb_notes_saisies' => $nbNotesSaisies,
            'nb_notes_validees' => $nbNotesValidees,
            'nb_notes_totales' => $nbNotesTotales,
            'etat_notes' => $etatNotes,
            'cours' => $this->whenLoaded('cours', function () {
                return [
                    'id' => $this->cours->id,
                    'titre' => $this->cours->titre,
                    'code' => $this->cours->code,
                ];
            }),
            'type_evaluation' => $this->whenLoaded('typeEvaluation', function () {
                return [
                    'id' => $this->typeEvaluation->id,
                    'nom' => $this->typeEvaluation->nom,
                    'coefficient_defaut' => (float) $this->typeEvaluation->coefficient_defaut,
                ];
            }),
            'semestre' => $this->whenLoaded('semestre', function () {
                return [
                    'id' => $this->semestre->id,
                    'numero' => $this->semestre->numero,
                    'annee' => $this->semestre->anneeAcademique->annee ?? null,
                ];
            }),
            'salle' => $this->whenLoaded('salle', function () {
                return $this->salle ? [
                    'id' => $this->salle->id,
                    'nom' => $this->salle->nom,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
