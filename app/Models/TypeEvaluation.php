<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'code',
        'coefficient_defaut',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'coefficient_defaut' => 'decimal:2',
        ];
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }
}

