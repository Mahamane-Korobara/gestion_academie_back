<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotesExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'semestre_id',
        'filiere_id',
        'niveau_id',
        'exported_by',
        'exported_at',
        'status',
        'obsolete_at',
        'notes_count',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'exported_at' => 'datetime',
            'obsolete_at' => 'datetime',
        ];
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }

    public function filiere()
    {
        return $this->belongsTo(Filiere::class);
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function exportedBy()
    {
        return $this->belongsTo(User::class, 'exported_by');
    }
}
