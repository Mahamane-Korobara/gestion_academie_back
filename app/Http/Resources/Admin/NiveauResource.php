<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class NiveauResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'ordre' => $this->ordre,
            'nombre_semestres' => $this->nombre_semestres,
            'filiere' => $this->when($this->relationLoaded('filiere'), [
                'id' => $this->filiere->id,
                'nom' => $this->filiere->nom,
            ]),
            'cours_count' => $this->whenCounted('cours'),
            'etudiants_count' => $this->whenCounted('etudiants'),
        ];
    }
}
