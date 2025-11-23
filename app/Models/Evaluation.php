<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\StatutEvaluation;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'cours_id',
        'type_evaluation_id',
        'semestre_id',
        'titre',
        'coefficient',
        'date_evaluation',
        'heure_debut',
        'heure_fin',
        'salle_id',
        'instructions',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'coefficient' => 'decimal:2',
            'date_evaluation' => 'date',
            'heure_debut' => 'datetime:H:i',
            'heure_fin' => 'datetime:H:i',
            'statut' => StatutEvaluation::class,
        ];
    }

    public function cours()
    {
        return $this->belongsTo(Cours::class);
    }

    public function typeEvaluation()
    {
        return $this->belongsTo(TypeEvaluation::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }

    public function salle()
    {
        return $this->belongsTo(Salle::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }
}
