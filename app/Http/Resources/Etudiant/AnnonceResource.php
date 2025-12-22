<?php

namespace App\Http\Resources\Etudiant;

use Illuminate\Http\Resources\Json\JsonResource;

class AnnonceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'contenu' => $this->contenu,
            'type' => $this->type->label(),
            'priorite' => [
                'code' => $this->priorite->value,
                'label' => $this->priorite->label(),
                'color' => $this->priorite->color(),
            ],
            'auteur' => $this->auteur->name,
            'date_expiration' => $this->date_expiration?->format('d/m/Y'),
            'created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }
}