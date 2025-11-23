<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Professeur extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code_professeur',
        'nom',
        'prenom',
        'specialite',
        'grade',
        'email_professionnel',
        'telephone',
        'bio',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cours()
    {
        return $this->belongsToMany(Cours::class, 'cours_professeur')
                    ->withPivot('annee_academique_id')
                    ->withTimestamps();
    }

    public function emploisDuTemps()
    {
        return $this->hasMany(EmploiDuTemps::class);
    }

    public function evaluations()
    {
        return $this->hasManyThrough(Evaluation::class, Cours::class);
    }

    // Accesseur nom complet
    public function getNomCompletAttribute(): string
    {
        return "{$this->grade} {$this->prenom} {$this->nom}";
    }
}
