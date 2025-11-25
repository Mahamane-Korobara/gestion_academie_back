<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class FiliereResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'code' => $this->code,
            'duree_annees' => $this->duree_annees,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'niveaux_count' => $this->whenCounted('niveaux'),
            'etudiants_count' => $this->whenCounted('etudiants'),
            'niveaux' => NiveauResource::collection($this->whenLoaded('niveaux')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
