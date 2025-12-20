<?php

namespace App\Http\Resources\Etudiant;

use Illuminate\Http\Resources\Json\JsonResource;

class NoteEtudiantResource extends JsonResource
{
    public function toArray($request): array
    {
        // Détection si c'est une note de rattrapage via le code de l'évaluation
        $isRattrapage = $this->evaluation->typeEvaluation->code === 'RATT';

        return [
            'id' => $this->id,
            'cours' => $this->evaluation->cours->titre,
            'type_evaluation' => $this->evaluation->typeEvaluation->nom,
            'note' => $this->is_absent ? null : (float)$this->note,
            'is_absent' => $this->is_absent,
            'coefficient' => $this->evaluation->coefficient,
            'date' => $this->date_validation->format('d/m/Y'),
            'is_rattrapage' => $isRattrapage,
            'commentaire' => $this->commentaire,
            'mention' => $this->getMention((float)$this->note)
        ];
    }

    private function getMention(float $note): string {
        if ($note >= 16) return 'Très Bien';
        if ($note >= 14) return 'Bien';
        if ($note >= 12) return 'Assez Bien';
        if ($note >= 10) return 'Passable';
        return 'Insuffisant';
    }
}