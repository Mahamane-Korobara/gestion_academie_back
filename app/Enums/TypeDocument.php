<?php 

namespace App\Enums;

enum TypeDocument: string
{
    case CERTIFICAT_SCOLARITE = 'certificat_scolarite';
    case RELEVE_NOTES = 'releve_notes';
    case ATTESTATION_REUSSITE = 'attestation_reussite';
    case CERTIFICAT_INSCRIPTION = 'certificat_inscription';
    case DIPLOME = 'diplome';

    public function label(): string
    {
        return match($this) {
            self::CERTIFICAT_SCOLARITE => 'Certificat de scolarité',
            self::RELEVE_NOTES => 'Relevé de notes',
            self::ATTESTATION_REUSSITE => 'Attestation de réussite',
            self::CERTIFICAT_INSCRIPTION => 'Certificat d\'inscription',
            self::DIPLOME => 'Diplôme',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}