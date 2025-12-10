<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AnneeAcademiqueResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'annee' => $this->annee,
            'date_debut' => $this->date_debut->format('Y-m-d'),
            'date_fin' => $this->date_fin->format('Y-m-d'),
            'is_active' => (bool) $this->is_active,
            'is_cloturee' => (bool) $this->is_cloturee,
            'duree_jours' => $this->date_debut->diffInDays($this->date_fin),
            'semestres_count' => $this->whenCounted('semestres'),
            'etudiants_count' => $this->whenCounted('etudiants'),
            'cours_count' => $this->whenCounted('cours'),
            'semestres' => SemestreResource::collection($this->whenLoaded('semestres')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}