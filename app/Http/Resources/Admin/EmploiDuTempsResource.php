<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Admin\CoursResource;
use App\Http\Resources\Admin\NiveauResource;

class EmploiDuTempsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jour' => $this->jour->value, 
            'creneau' => [
                'debut' => $this->heure_debut->format('H:i'),
                'fin' => $this->heure_fin->format('H:i'),
            ],
            'type' => [
                'code' => $this->type_seance->value,
                'label' => $this->type_seance->label(),
                'color' => $this->type_seance->color(),
            ],

            // 1. Relations obligatoires (Cours & Niveau)
            // On utilise whenLoaded pour éviter les erreurs si la relation n'est pas eager-loaded
            'cours' => new CoursResource($this->whenLoaded('cours')),
            'niveau' => new NiveauResource($this->whenLoaded('niveau')),

            // 2. Professeur (Formaté pour le Front-end)
            'professeur' => $this->whenLoaded('professeur', function () {
                return [
                    'id'          => $this->professeur->id,
                    'nom' => $this->professeur->nom,
                    'prenom' => $this->professeur->prenom,
                    'nom_complet' => $this->professeur->user->nom . ' ' . $this->professeur->user->prenom,
                    'specialite'  => $this->professeur->specialite,
                ];
            }),

            // 3. Salle (Avec vérification car nullable)
            'salle' => $this->whenLoaded('salle', function () {
                return [
                    'id' => $this->salle->id,
                    'nom' => $this->salle->nom, // Corrigé : utilise 'nom' ou 'code' selon ta migration
                    'batiment' => $this->salle->batiment,
                    'capacite' => $this->salle->capacite
                ];
            }),

            // 4. Semestre (Décommenté et corrigé pour ton Enum NumeroSemestre)
            'semestre' => $this->whenLoaded('semestre', function () {
                return [
                    'id' => $this->semestre->id,
                    'numero' => $this->semestre->numero->value,
                    'label' => $this->semestre->numero->label(),
                ];
            }),

            // 5. Année Académique (Crucial pour ton nouveau système)
            'annee_academique' => $this->whenLoaded('anneeAcademique', function () {
                return [
                    'id' => $this->anneeAcademique->id,
                    'nom' => $this->anneeAcademique->nom,
                    'is_active' => $this->anneeAcademique->is_active,
                ];
            }),

            'created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }
}