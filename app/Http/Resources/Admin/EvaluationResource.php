<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'coefficient' => (float) $this->coefficient,
            'date_evaluation' => $this->date_evaluation,
            'heure_debut' => $this->heure_debut,
            'heure_fin' => $this->heure_fin,
            'statut' => $this->statut,
            'instructions' => $this->instructions,
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