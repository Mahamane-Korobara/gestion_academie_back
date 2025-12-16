<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeEvaluation extends Model
{
    use HasFactory;

    protected $table = 'types_evaluations';
    protected $fillable = [
        'nom',
        'code',
        'coefficient_defaut',
        'description',
    ];

    protected $casts = [
    'coefficient_defaut' => 'decimal:2',
    ];


    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }
}

