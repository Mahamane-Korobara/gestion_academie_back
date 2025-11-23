<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\StudentStatus;
use App\Enums\Sexe;

class Etudiant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'matricule',
        'nom',
        'prenom',
        'date_naissance',
        'sexe',
        'lieu_naissance',
        'adresse',
        'email_personnel',
        'telephone',
        'telephone_urgence',
        'filiere_id',
        'niveau_id',
        'annee_academique_id',
        'statut',
        'date_inscription',
    ];

    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'date_inscription' => 'date',
            'sexe' => Sexe::class,
            'statut' => StudentStatus::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function filiere()
    {
        return $this->belongsTo(Filiere::class);
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function anneeAcademique()
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function bulletins()
    {
        return $this->hasMany(Bulletin::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // Accesseur nom complet
    public function getNomCompletAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    // Scope pour filtrer par statut
    public function scopeActifs($query)
    {
        return $query->where('statut', StudentStatus::ACTIF);
    }
}
