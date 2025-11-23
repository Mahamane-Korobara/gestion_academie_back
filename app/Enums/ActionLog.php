<?php 

namespace App\Enums;

enum ActionLog: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case VIEW = 'view';
    case EXPORT = 'export';
    case VALIDATE = 'validate';

    public function label(): string
    {
        return match($this) {
            self::CREATE => 'Création',
            self::UPDATE => 'Modification',
            self::DELETE => 'Suppression',
            self::LOGIN => 'Connexion',
            self::LOGOUT => 'Déconnexion',
            self::VIEW => 'Consultation',
            self::EXPORT => 'Export',
            self::VALIDATE => 'Validation',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::CREATE => 'plus',
            self::UPDATE => 'edit',
            self::DELETE => 'trash',
            self::LOGIN => 'login',
            self::LOGOUT => 'logout',
            self::VIEW => 'eye',
            self::EXPORT => 'download',
            self::VALIDATE => 'check',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}