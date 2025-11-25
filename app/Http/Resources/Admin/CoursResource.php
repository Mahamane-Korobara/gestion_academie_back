<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class CoursResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'code' => $this->code,
            'description' => $this->description,
            'coefficient' => (float) $this->coefficient,
            'nombre_heures' => $this->nombre_heures,
            'semestre' => $this->semestre->value,
            'is_active' => $this->is_active,
            'niveau' => [
                'id' => $this->niveau->id,
                'nom' => $this->niveau->nom,
                'filiere' => $this->niveau->filiere->nom,
            ],
            'professeurs' => $this->whenLoaded('professeurs', function () {
                return $this->professeurs->map(fn($prof) => [
                    'id' => $prof->id,
                    'nom_complet' => $prof->nom_complet,
                    'specialite' => $prof->specialite,
                ]);
            }),
            'inscriptions_count' => $this->whenCounted('inscriptions'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}