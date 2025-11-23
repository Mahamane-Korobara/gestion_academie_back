<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\StatutNote;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id',
        'evaluation_id',
        'note',
        'is_absent',
        'commentaire',
        'statut',
        'saisi_par',
        'valide_par',
        'date_saisie',
        'date_validation',
    ];

    protected function casts(): array
    {
        return [
            'note' => 'decimal:2',
            'is_absent' => 'boolean',
            'statut' => StatutNote::class,
            'date_saisie' => 'datetime',
            'date_validation' => 'datetime',
        ];
    }

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function saisiPar()
    {
        return $this->belongsTo(User::class, 'saisi_par');
    }

    public function validePar()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    // Scope pour les notes validées
    public function scopeValidees($query)
    {
        return $query->where('statut', StatutNote::VALIDEE);
    }
}
