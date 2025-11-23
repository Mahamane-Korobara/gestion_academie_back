<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Niveau extends Model
{
    use HasFactory;

    protected $fillable = [
        'filiere_id',
        'nom',
        'ordre',
        'nombre_semestres',
    ];

    public function filiere()
    {
        return $this->belongsTo(Filiere::class);
    }

    public function cours()
    {
        return $this->hasMany(Cours::class);
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
