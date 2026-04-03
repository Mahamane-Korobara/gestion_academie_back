<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'expediteur_id',
        'titre',
        'description',
        'type',
        'fichier_path',
        'fichier_original',
        'extension',
        'mime_type',
        'taille',
        'filiere_id',
        'niveau_id',
        'cours_id',
        'date_expiration',
        'est_actif',
    ];

    protected $casts = [
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
        if (!$this->uuid) {
            return '';
        }

        return route('documents.download', $this->uuid);
    }

    public function getPreviewUrlAttribute(): ?string
    {
        if (!$this->uuid) {
            return null;
        }

        if (!$this->isTextPreviewable()) {
            return null;
        }

        return route('documents.preview', $this->uuid);
    }

    public function isTextPreviewable(): bool
    {
        $mime = $this->mime_type ?? '';
        return str_starts_with($mime, 'text/')
            || in_array($mime, [
                'application/json',
                'application/xml',
                'application/x-python',
                'application/x-ruby',
                'application/x-php',
                'application/x-sh',
                'application/x-c',
                'application/x-c++',
                'application/x-stata',
                'application/x-tex',
            ], true);
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
