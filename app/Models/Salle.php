<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salle extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'batiment',
        'capacite',
        'equipements',
        'is_disponible',
    ];

    protected function casts(): array
    {
        return [
            'is_disponible' => 'boolean',
        ];
    }

    public function emploisDuTemps()
    {
        return $this->hasMany(EmploiDuTemps::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    // Scope pour les salles disponibles
    public function scopeDisponibles($query)
    {
        return $query->where('is_disponible', true);
    }
}

