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
    ];

    protected function casts(): array
    {
        return [
            'is_lu' => 'boolean',
            'date_lecture' => 'datetime',
        ];
    }

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

    // Scope pour les messages non lus
    public function scopeNonLus($query)
    {
        return $query->where('is_lu', false);
    }
}