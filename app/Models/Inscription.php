<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id',
        'cours_id',
        'annee_academique_id',
        'semestre_id',
        'date_inscription',
    ];

    protected function casts(): array
    {
        return [
            'date_inscription' => 'date',
        ];
    }

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function cours()
    {
        return $this->belongsTo(Cours::class);
    }

    public function anneeAcademique()
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }
}
