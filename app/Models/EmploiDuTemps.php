<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\JourSemaine;
use App\Enums\TypeSeance;

class EmploiDuTemps extends Model
{
    use HasFactory;

    protected $fillable = [
        'cours_id',
        'professeur_id',
        'salle_id',
        'semestre_id',
        'jour',
        'heure_debut',
        'heure_fin',
        'type_seance',
    ];

    protected function casts(): array
    {
        return [
            'jour' => JourSemaine::class,
            'heure_debut' => 'datetime:H:i',
            'heure_fin' => 'datetime:H:i',
            'type_seance' => TypeSeance::class,
        ];
    }

    public function cours()
    {
        return $this->belongsTo(Cours::class);
    }

    public function professeur()
    {
        return $this->belongsTo(Professeur::class);
    }

    public function salle()
    {
        return $this->belongsTo(Salle::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }
}
