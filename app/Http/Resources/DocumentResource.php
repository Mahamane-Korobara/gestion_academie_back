<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description,
            'type' => $this->type->value,
            'url' => $this->url,
            'taille' => $this->taille_formatee,
            'fichier_original' => $this->fichier_original,
            'date_expiration' => $this->date_expiration?->format('d/m/Y'),
            'created_at' => $this->created_at->format('d/m/Y'),
            'cours' => [
                'id' => $this->cours->id,
                'titre' => $this->cours->titre,
                'code' => $this->cours->code,
            ],
            'niveau' => $this->niveau->nom,
            'filiere' => $this->filiere->nom,
            'expediteur' => $this->expediteur->name,
        ];
    }
}