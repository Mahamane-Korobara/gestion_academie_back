<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class BulletinResource extends JsonResource
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
            'semestre' => $this->whenLoaded('semestre', function () {
                return [
                    'id' => $this->semestre->id,
                    'numero' => $this->semestre->numero,
                    'annee' => $this->semestre->anneeAcademique->annee ?? null,
                ];
            }),
            'moyenne_generale' => $this->moyenne_generale,
            'decision' => $this->decision,
            'est_genere' => $this->est_genere,
            'est_valide' => $this->est_valide,
            'date_generation' => $this->date_generation,
            'genere_par' => $this->whenLoaded('generePar', function () {
                return [
                    'id' => $this->generePar->id,
                    'nom' => $this->generePar->nom,
                ];
            }),
            'valide_par' => $this->whenLoaded('valide_par', function () {
                return [
                    'id' => $this->validePar->id,
                    'nom' => $this->validePar->nom,
                ];
            }),
            'date_validation' => $this->date_validation,
        ];
    }
}