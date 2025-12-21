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
            'jour' => $this->jour->value, // Récupère la string de l'Enum
            'creneau' => [
                'debut' => $this->heure_debut->format('H:i'),
                'fin' => $this->heure_fin->format('H:i'),
            ],
            'type' => [
                'code' => $this->type_seance->value,
                'label' => $this->type_seance->label(),
                'color' => $this->type_seance->color(),
            ],
            // Relations conditionnelles
            'cours' => new CoursResource($this->whenLoaded('cours')),
            'niveau' => new NiveauResource($this->whenLoaded('niveau')),
            'professeur' => $this->whenLoaded('professeur', function () {
                return [
                    'id' => $this->professeur->id,
                    'nom' => $this->professeur->nom,
                    'prenom' => $this->professeur->prenom,
                    'specialite' => $this->professeur->specialite,
                    'email' => $this->professeur->email_professionnel,
                ];
            }),
            'salle' => $this->whenLoaded('salle', function () {
                return [
                    'id' => $this->salle->id,
                    'nom' => $this->salle->batiment,
                    'capacite' => $this->salle->capacite
                ];
            }),
            // 'semestre' => $this->whenLoaded('semestre', [
            //     'id' => $this->semestre->id,
            //     'numero' => $this->semestre->numero->value,
            //     'numero_label' => $this->semestre->numero->label(),
            // ]),

        ];
    }
}