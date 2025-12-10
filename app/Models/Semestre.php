<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Semestre as SemestreEnum;

class Semestre extends Model
{
    use HasFactory;

    protected $fillable = [
        'annee_academique_id',
        'numero',
        'date_debut',
        'date_fin',
        'date_debut_examens',
        'date_fin_examens',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'numero' => SemestreEnum::class,
            'date_debut' => 'date',
            'date_fin' => 'date',
            'date_debut_examens' => 'date',
            'date_fin_examens' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function anneeAcademique()
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function emploisDuTemps()
    {
        return $this->hasMany(EmploiDuTemps::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    public function bulletins()
    {
        return $this->hasMany(Bulletin::class);
    }

    // Scope pour le semestre actif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function deactivateAllInAnnee($anneeAcademiqueId)
    {
        self::where('annee_academique_id', $anneeAcademiqueId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}