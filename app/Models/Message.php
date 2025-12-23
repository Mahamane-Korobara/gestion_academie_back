<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'expediteur_id',
        'destinataire_id',
        'sujet',
        'contenu',
        'is_lu',
        'date_lecture',
        'message_parent_id',
        'deleted_at_expediteur',  
        'deleted_at_destinataire',
    ];

    protected function casts(): array
    {
        return [
            'is_lu' => 'boolean',
            'date_lecture' => 'datetime',
            'deleted_at_expediteur' => 'datetime', 
            'deleted_at_destinataire' => 'datetime',
        ];
    }

    // Relations
    public function expediteur()
    {
        return $this->belongsTo(User::class, 'expediteur_id');
    }

    public function destinataire()
    {
        return $this->belongsTo(User::class, 'destinataire_id');
    }

    public function messageParent()
    {
        return $this->belongsTo(Message::class, 'message_parent_id');
    }

    public function reponses()
    {
        return $this->hasMany(Message::class, 'message_parent_id');
    }

    // Scopes
    public function scopeNonLus($query)
    {
        return $query->where('is_lu', false);
    }

    public function scopeNonSupprimesPour($query, int $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where(function($subQ) use ($userId) {
                // Messages reçus non supprimés
                $subQ->where('destinataire_id', $userId)
                     ->whereNull('deleted_at_destinataire');
            })
            ->orWhere(function($subQ) use ($userId) {
                // Messages envoyés non supprimés
                $subQ->where('expediteur_id', $userId)
                     ->whereNull('deleted_at_expediteur');
            });
        });
    }
}