<?php 

namespace App\Enums;

enum DecisionBulletin: string
{
    case ADMIS = 'admis';
    case RATTRAPAGE = 'rattrapage';
    case REDOUBLANT = 'redoublant';
    case DIPLOME = 'diplome';
    case PASSE_CLASSE_SUPERIEURE = 'passe_classe_superieure';

    public function label(): string
    {
        return match($this) {
            self::ADMIS => 'Admis',
            self::RATTRAPAGE => 'Rattrapage',
            self::REDOUBLANT => 'Redoublant',
            self::DIPLOME => 'Diplômé',
            self::PASSE_CLASSE_SUPERIEURE => 'Passé en classe supérieure',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ADMIS => 'green',
            self::RATTRAPAGE => 'yellow',
            self::REDOUBLANT => 'orange',
            self::DIPLOME => 'blue',
            self::PASSE_CLASSE_SUPERIEURE => 'green',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}