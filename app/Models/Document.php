<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\TypeDocument;
use App\Enums\StatutDocument;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id',
        'type',
        'titre',
        'fichier_path',
        'statut',
        'date_demande',
        'date_delivrance',
        'traite_par',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeDocument::class,
            'statut' => StatutDocument::class,
            'date_demande' => 'date',
            'date_delivrance' => 'date',
        ];
    }

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function traitePar()
    {
        return $this->belongsTo(User::class, 'traite_par');
    }
}
