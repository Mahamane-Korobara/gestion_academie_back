<?php 

namespace App\Enums;

enum PrioriteAnnonce: string
{
    case NORMALE = 'normale';
    case IMPORTANTE = 'importante';
    case URGENTE = 'urgente';

    public function label(): string
    {
        return match($this) {
            self::NORMALE => 'Normale',
            self::IMPORTANTE => 'Importante',
            self::URGENTE => 'Urgente',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NORMALE => 'blue',
            self::IMPORTANTE => 'orange',
            self::URGENTE => 'red',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
