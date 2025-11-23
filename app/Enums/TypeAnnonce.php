<?php 

namespace App\Enums;

enum TypeAnnonce: string
{
    case GLOBALE = 'globale';
    case FILIERE = 'filiere';
    case NIVEAU = 'niveau';
    case COURS = 'cours';
    case INDIVIDUELLE = 'individuelle';

    public function label(): string
    {
        return match($this) {
            self::GLOBALE => 'Globale',
            self::FILIERE => 'Par filière',
            self::NIVEAU => 'Par niveau',
            self::COURS => 'Par cours',
            self::INDIVIDUELLE => 'Individuelle',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
