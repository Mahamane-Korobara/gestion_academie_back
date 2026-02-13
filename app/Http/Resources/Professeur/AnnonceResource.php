<?php

namespace App\Http\Resources\Professeur;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\TypeAnnonce;

class AnnonceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'contenu' => $this->contenu,
            'type' => [
                'code' => $this->type->value,
                'label' => $this->type->label(),
            ],
            'priorite' => [
                'code' => $this->priorite->value,
                'label' => $this->priorite->label(),
                'color' => $this->priorite->color(),
            ],
            'cible' => $this->getCibleDetails(),
            'auteur' => $this->auteur->only('id', 'name', 'email', 'role'),
            'date_expiration' => $this->date_expiration,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }

    private function getCibleDetails(): array
    {
        return match($this->type) {
            TypeAnnonce::GLOBALE => ['type' => 'globale'],
            TypeAnnonce::FILIERE => [
                'type' => 'filiere',
                'filiere' => $this->filiere?->only('id', 'nom', 'code')
            ],
            TypeAnnonce::NIVEAU => [
                'type' => 'niveau',
                'niveau' => $this->niveau?->only('id', 'nom'),
                'filiere' => $this->niveau?->filiere?->only('id', 'nom')
            ],
            TypeAnnonce::COURS => [
                'type' => 'cours',
                'cours' => $this->cours?->only('id', 'titre', 'code'),
                'niveau' => $this->cours?->niveau?->only('id', 'nom')
            ],
            TypeAnnonce::INDIVIDUELLE => [
                'type' => 'individuelle',
                'destinataire' => $this->destinataire?->only('id', 'name', 'email')
            ],
        };
    }
}