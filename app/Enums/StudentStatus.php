<?php 

namespace App\Enums;

enum StudentStatus: string
{
    case ACTIF = 'actif';
    case REDOUBLANT = 'redoublant';
    case RATTRAPAGE = 'rattrapage';
    case DIPLOME = 'diplome';
    case PASSE_CLASSE_SUPERIEURE = 'passe_classe_superieure';
    case ABANDONNE = 'abandonne';
    case SUSPENDU = 'suspendu';

    public function label(): string
    {
        return match($this) {
            self::ACTIF => 'Actif',
            self::REDOUBLANT => 'Redoublant',
            self::RATTRAPAGE => 'Rattrapage',
            self::DIPLOME => 'Diplômé',
            self::PASSE_CLASSE_SUPERIEURE => 'Passé en classe supérieure',
            self::ABANDONNE => 'Abandonné',
            self::SUSPENDU => 'Suspendu',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIF => 'green',
            self::REDOUBLANT => 'orange',
            self::RATTRAPAGE => 'yellow',
            self::DIPLOME => 'blue',
            self::PASSE_CLASSE_SUPERIEURE => 'green',
            self::ABANDONNE => 'red',
            self::SUSPENDU => 'red',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}