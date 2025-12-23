<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sujet' => $this->sujet,
            'contenu' => $this->contenu,
            'is_lu' => $this->is_lu,
            'date_lecture' => $this->date_lecture?->format('d/m/Y H:i'),
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'expediteur' => [
                'id' => $this->expediteur->id,
                'nom' => $this->expediteur->name,
                'role' => $this->expediteur->role,
            ],
            'destinataire' => [
                'id' => $this->destinataire->id,
                'nom' => $this->destinataire->name,
                'role' => $this->destinataire->role,
            ],
            'reponses_count' => $this->reponses_count ?? 0,
            'est_reponse' => $this->message_parent_id !== null,
        ];
    }
}