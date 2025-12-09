<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnneeAcademique extends Model
{
    use HasFactory;

    protected $table = 'annees_academiques';
    
    protected $fillable = [
        'annee',
        'date_debut',
        'date_fin',
        'is_active',
        'is_cloturee',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'is_active' => 'boolean',
            'is_cloturee' => 'boolean',
        ];
    }

    public function semestres()
    {
        return $this->hasMany(Semestre::class);
    }

    public function cours()
    {
        return $this->hasMany(Cours::class);
    }

    public function etudiants()
    {
        return $this->hasMany(Etudiant::class);
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    // Scope pour l'année active
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
