<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'titre' => $this->titre,
            'description' => $this->description,
            'type' => $this->type,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'url' => $this->url,
            'download_url' => $this->url,
            'preview_url' => $this->preview_url,
            'taille' => $this->taille_formatee,
            'original_name' => $this->fichier_original,
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
            'expediteur_id' => $this->expediteur_id,
        ];
    }
}
