<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Filiere extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'code',
        'duree_annees',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function niveaux()
    {
        return $this->hasMany(Niveau::class);
    }

    public function etudiants()
    {
        return $this->hasMany(Etudiant::class);
    }

    public function annonces()
    {
        return $this->hasMany(Annonce::class);
    }
}

