<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'note' => $this->note,
            'is_absent' => $this->is_absent,
            'statut' => $this->statut,
            'commentaire' => $this->commentaire,
            'date_saisie' => $this->date_saisie,
            'etudiant' => [
                'id' => $this->etudiant->user->id,
                'nom' => $this->etudiant->nom_complet,
                'email' => $this->etudiant->user->email,
            ],
            'evaluation' => [
                'id' => $this->evaluation->id,
                'titre' => $this->evaluation->titre,
                'type' => $this->evaluation->typeEvaluation->nom,
                'cours' => $this->evaluation->cours->code . ' - ' . $this->evaluation->cours->titre,
                'semestre' => [
                    'id' => $this->evaluation->semestre?->id,
                    'numero' => $this->evaluation->semestre?->numero,
                    'annee' => $this->evaluation->semestre?->anneeAcademique?->annee,
                ],
            ],
            'saisi_par' => [
                'id' => $this->saisiPar->id,
                'nom' => $this->saisiPar->name,
            ],
        ];
    }
}
