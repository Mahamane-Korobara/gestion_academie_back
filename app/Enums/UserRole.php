<?php 

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case PROFESSEUR = 'professeur';
    case ETUDIANT = 'etudiant';

    /**
     * Obtenir le label lisible
     */
    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrateur',
            self::PROFESSEUR => 'Professeur',
            self::ETUDIANT => 'Étudiant',
        };
    }

    /**
     * Obtenir toutes les valeurs
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Obtenir les options pour un select
     */
    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label()
        ], self::cases());
    }
}