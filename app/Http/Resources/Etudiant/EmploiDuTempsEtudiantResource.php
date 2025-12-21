<?php

namespace App\Http\Resources\Etudiant;

use Illuminate\Http\Resources\Json\JsonResource;

class EmploiDuTempsEtudiantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'jour' => [
                'code' => $this->jour->value,
                'numero' => $this->jour->numero(),
            ],
            'creneau' => [
                'debut' => $this->heure_debut->format('H:i'),
                'fin' => $this->heure_fin->format('H:i'),
                'duree_minutes' => $this->heure_debut->diffInMinutes($this->heure_fin),
            ],
            'type' => [
                'code' => $this->type_seance->value,
                'label' => $this->type_seance->label(),
                'color' => $this->type_seance->color(),
            ],
            'cours' => [
                'id' => $this->cours->id,
                'titre' => $this->cours->titre,
                'code' => $this->cours->code,
                'coefficient' => (float) $this->cours->coefficient,
            ],
            'professeur' => [
                'id' => $this->professeur->id,
                'nom_complet' => $this->professeur->nom_complet,
                'specialite' => $this->professeur->specialite,
                'email' => $this->professeur->email_professionnel,
            ],
            'salle' => $this->when($this->salle, [
                'id' => $this->salle?->id,
                'nom' => $this->salle?->nom,
                'batiment' => $this->salle?->batiment,
                'capacite' => $this->salle?->capacite,
            ]),
        ];
    }
}
