<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\TypeAnnonce;
use App\Enums\PrioriteAnnonce;

class Annonce extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'contenu',
        'type',
        'filiere_id',
        'niveau_id',
        'cours_id',
        'destinataire_id',
        'auteur_id',
        'priorite',
        'date_expiration',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeAnnonce::class,
            'priorite' => PrioriteAnnonce::class,
            'date_expiration' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function filiere()
    {
        return $this->belongsTo(Filiere::class);
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function cours()
    {
        return $this->belongsTo(Cours::class);
    }

    public function destinataire()
    {
        return $this->belongsTo(User::class, 'destinataire_id');
    }

    // Scope pour les annonces actives
    public function scopeActives($query)
    {
        return $query->where('is_active', true)
                     ->where(function($q) {
                         $q->whereNull('date_expiration')
                           ->orWhere('date_expiration', '>=', now());
                     });
    }
}