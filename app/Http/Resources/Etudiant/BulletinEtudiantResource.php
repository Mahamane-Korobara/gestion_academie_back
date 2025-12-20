<?php

namespace App\Http\Resources\Etudiant;

use Illuminate\Http\Resources\Json\JsonResource;

class BulletinEtudiantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->semestre_id ? 'Semestriel' : 'Annuel',
            
            // Informations sur la période
            'periode' => $this->semestre_id ? "Semestre {$this->semestre->value}" : "Annuel",

            'annee_academique' => $this->semestre 
                ? $this->semestre->anneeAcademique->annee 
                : ($this->etudiant->anneeAcademique->annee ?? 'N/A'),

            // Résultats
            'moyenne_generale' => number_format((float)$this->moyenne_generale, 2, '.', ''),
            'decision' => $this->decision, // Utilise la valeur de l'Enum DecisionBulletin
            
            // Métadonnées
            'est_valide' => (bool)$this->est_genere,
            'date_edition' => $this->date_generation ? $this->date_generation->format('d/m/Y H:i') : null,

            // Mention calculée dynamiquement pour l'affichage étudiant
            'mention' => $this->calculerMention((float)$this->moyenne_generale),
            
            // Lien vers le futur PDF
            'url_download' => null, 
        ];
    }

    /**
     * Logique de mention pour le rendu visuel
     */
    private function calculerMention(float $moyenne): string
    {
        if ($moyenne >= 16) return 'Très Bien';
        if ($moyenne >= 14) return 'Bien';
        if ($moyenne >= 12) return 'Assez Bien';
        if ($moyenne >= 10) return 'Passable';
        return 'Ajourné';
    }
}