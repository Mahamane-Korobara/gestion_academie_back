<?php 

namespace App\Enums;

enum StatutDocument: string
{
    case EN_ATTENTE = 'en_attente';
    case EN_COURS = 'en_cours';
    case PRET = 'pret';
    case DELIVRE = 'delivre';

    public function label(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::EN_COURS => 'En cours de traitement',
            self::PRET => 'Prêt',
            self::DELIVRE => 'Délivré',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'gray',
            self::EN_COURS => 'yellow',
            self::PRET => 'green',
            self::DELIVRE => 'blue',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}