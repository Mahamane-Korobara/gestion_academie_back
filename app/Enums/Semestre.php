<?php 

namespace App\Enums;

enum Semestre: string
{
    case S1 = 'S1';
    case S2 = 'S2';

    public function label(): string
    {
        return match($this) {
            self::S1 => 'Semestre 1',
            self::S2 => 'Semestre 2',
        };
    }

    public function numero(): int
    {
        return match($this) {
            self::S1 => 1,
            self::S2 => 2,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
