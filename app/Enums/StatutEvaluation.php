<?php 

namespace App\Enums;

enum StatutEvaluation: string
{
    case PLANIFIEE = 'planifiee';
    case EN_COURS = 'en_cours';
    case TERMINEE = 'terminee';
    case ANNULEE = 'annulee';

    public function label(): string
    {
        return match($this) {
            self::PLANIFIEE => 'Planifiée',
            self::EN_COURS => 'En cours',
            self::TERMINEE => 'Terminée',
            self::ANNULEE => 'Annulée',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PLANIFIEE => 'blue',
            self::EN_COURS => 'orange',
            self::TERMINEE => 'green',
            self::ANNULEE => 'red',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}