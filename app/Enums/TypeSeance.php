<?php 

namespace App\Enums;

enum TypeSeance: string
{
    case COURS = 'cours';
    case TD = 'td';
    case TP = 'tp';
    case EXAMEN = 'examen';

    public function label(): string
    {
        return match($this) {
            self::COURS => 'Cours',
            self::TD => 'Travaux Dirigés',
            self::TP => 'Travaux Pratiques',
            self::EXAMEN => 'Examen',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::COURS => 'blue',
            self::TD => 'green',
            self::TP => 'purple',
            self::EXAMEN => 'red',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}