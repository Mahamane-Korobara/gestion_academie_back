<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Semestre;

class Cours extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'code',
        'description',
        'coefficient',
        'nombre_heures',
        'niveau_id',
        'semestre',
        'annee_academique_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'coefficient' => 'decimal:2',
            'semestre' => Semestre::class,
            'is_active' => 'boolean',
        ];
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function anneeAcademique()
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function professeurs()
    {
        return $this->belongsToMany(Professeur::class, 'cours_professeur')
                    ->withPivot('annee_academique_id')
                    ->withTimestamps();
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function emploisDuTemps()
    {
        return $this->hasMany(EmploiDuTemps::class);
    }

    public function annonces()
    {
        return $this->hasMany(Annonce::class);
    }

    // Scope pour les cours actifs
    public function scopeActifs($query)
    {
        return $query->where('is_active', true);
    }
}
