<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\JourSemaine;
use App\Enums\TypeSeance;

class EmploiDuTemps extends Model
{
    use HasFactory;

    protected $table = 'emploi_du_temps';

    protected $fillable = [
        'cours_id',
        'niveau_id',        
        'professeur_id',
        'salle_id',
        'semestre_id',
        'annee_academique_id', 
        'jour',
        'heure_debut',
        'heure_fin',
        'type_seance',
    ];

    protected $casts = [
        'jour' => JourSemaine::class,
        'heure_debut' => 'datetime:H:i',
        'heure_fin' => 'datetime:H:i',
        'type_seance' => TypeSeance::class,
    ];

    public function cours()
    {
        return $this->belongsTo(Cours::class);
    }

    public function niveau()      
    {
        return $this->belongsTo(Niveau::class);
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

    public function anneeAcademique()
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    /**
     * Scope pour filtrer l'emploi du temps sur l'année active
     */
    public function scopeAnneeActive($query)
    {
        return $query->whereHas('anneeAcademique', function($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Scope pour filtrer par niveau et semestre (très utile pour React)
     */
    public function scopePourGrille($query, $niveauId, $semestreId)
    {
        return $query->where('niveau_id', $niveauId)
                     ->where('semestre_id', $semestreId);
    }
}