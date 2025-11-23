<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'titre',
        'message',
        'data',
        'is_lu',
        'date_lecture',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_lu' => 'boolean',
            'date_lecture' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope pour les notifications non lues
    public function scopeNonLues($query)
    {
        return $query->where('is_lu', false);
    }

    // Marquer comme lu
    public function marquerCommeLu()
    {
        $this->update([
            'is_lu' => true,
            'date_lecture' => now(),
        ]);
    }
}
