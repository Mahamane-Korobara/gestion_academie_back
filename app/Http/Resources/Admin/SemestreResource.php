<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class SemestreResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero->value,
            'numero_label' => $this->numero->label(),
            'date_debut' => $this->date_debut->format('Y-m-d'),
            'date_fin' => $this->date_fin->format('Y-m-d'),
            'date_debut_examens' => $this->date_debut_examens?->format('Y-m-d'),
            'date_fin_examens' => $this->date_fin_examens?->format('Y-m-d'),
            'is_active' => $this->is_active,
            'duree_semaines' => $this->date_debut && $this->date_fin
                ? round($this->date_debut->diffInDays($this->date_fin) / 7)
                : 0,
            'annee_academique' => $this->when($this->relationLoaded('anneeAcademique'), [
                'id' => $this->anneeAcademique->id,
                'annee' => $this->anneeAcademique->annee,
            ]),
            'inscriptions_count' => $this->whenCounted('inscriptions'),
            'evaluations_count' => $this->whenCounted('evaluations'),
            'bulletins_count' => $this->whenCounted('bulletins'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
