<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class InscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'etudiant' => $this->whenLoaded('etudiant', function () {
                return [
                    'id' => $this->etudiant->id,
                    'nom_complet' => $this->etudiant->nom_complet,
                    'matricule' => $this->etudiant->matricule,
                ];
            }),
            'cours' => $this->whenLoaded('cours', function () {
                return [
                    'id' => $this->cours->id,
                    'titre' => $this->cours->titre,
                    'code' => $this->cours->code,
                    'coefficient' => $this->cours->coefficient,
                ];
            }),
            'semestre' => $this->whenLoaded('semestre', function () {
                return [
                    'id' => $this->semestre->id,
                    'numero' => $this->semestre->numero,
                    'annee' => $this->whenLoaded('semestre', function () {
                        return $this->semestre->anneeAcademique?->annee;
                    }),
                ];
            }),
            'date_inscription' => $this->date_inscription,
            'created_at' => $this->created_at,
        ];
    }
}