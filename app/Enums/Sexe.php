<?php 

namespace App\Enums;

enum Sexe: string
{
    case MASCULIN = 'M';
    case FEMININ = 'F';

    public function label(): string
    {
        return match($this) {
            self::MASCULIN => 'Masculin',
            self::FEMININ => 'Féminin',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
