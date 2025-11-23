<?php

namespace App\Models;

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\DecisionBulletin;

class Bulletin extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id',
        'semestre_id',
        'moyenne_generale',
        'rang',
        'total_etudiants',
        'observations',
        'decision',
        'fichier_pdf',
        'est_genere',
        'date_generation',
        'genere_par',
    ];

    protected function casts(): array
    {
        return [
            'moyenne_generale' => 'decimal:2',
            'decision' => DecisionBulletin::class,
            'est_genere' => 'boolean',
            'date_generation' => 'datetime',
        ];
    }

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }

    public function generePar()
    {
        return $this->belongsTo(User::class, 'genere_par');
    }
}
