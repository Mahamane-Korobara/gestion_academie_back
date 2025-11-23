<?php 

namespace App\Enums;

enum JourSemaine: string
{
    case LUNDI = 'Lundi';
    case MARDI = 'Mardi';
    case MERCREDI = 'Mercredi';
    case JEUDI = 'Jeudi';
    case VENDREDI = 'Vendredi';
    case SAMEDI = 'Samedi';
    case DIMANCHE = 'Dimanche';

    public function numero(): int
    {
        return match($this) {
            self::LUNDI => 1,
            self::MARDI => 2,
            self::MERCREDI => 3,
            self::JEUDI => 4,
            self::VENDREDI => 5,
            self::SAMEDI => 6,
            self::DIMANCHE => 7,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}