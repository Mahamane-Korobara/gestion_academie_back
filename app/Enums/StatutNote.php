<?php 

namespace App\Enums;

enum StatutNote: string
{
    case BROUILLON = 'brouillon';
    case SOUMISE = 'soumise';
    case VALIDEE = 'validee';

    public function label(): string
    {
        return match($this) {
            self::BROUILLON => 'Brouillon',
            self::SOUMISE => 'Soumise',
            self::VALIDEE => 'Validée',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::BROUILLON => 'gray',
            self::SOUMISE => 'yellow',
            self::VALIDEE => 'green',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}