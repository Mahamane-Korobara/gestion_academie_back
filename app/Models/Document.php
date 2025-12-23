<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\TypeDocument;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'expediteur_id',
        'titre',
        'description',
        'type',
        'fichier_path',
        'fichier_original',
        'taille',
        'filiere_id',
        'niveau_id',
        'cours_id',
        'date_expiration',
        'est_actif',
    ];

    protected $casts = [
        'type' => TypeDocument::class,
        'date_expiration' => 'datetime',
        'taille' => 'integer',
        'est_actif' => 'boolean',
    ];

    public function expediteur()
    {
        return $this->belongsTo(User::class, 'expediteur_id');
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

    public function scopeActifs($query)
    {
        return $query->where('est_actif', true)
                     ->where(function ($q) {
                         $q->whereNull('date_expiration')
                           ->orWhere('date_expiration', '>=', now());
                     });
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->fichier_path);
    }

    public function getTailleFormateeAttribute(): string
    {
        $size = $this->taille;
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}