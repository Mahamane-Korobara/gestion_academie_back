<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnneeAcademiqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'annee' => [
                'sometimes',
                'string',
                'regex:/^\d{4}-\d{4}$/',
                Rule::unique('annees_academiques')->ignore($this->route('annee_academique'))
            ],

            // Validation robuste si l’un existe l’autre doit être valide
            'date_debut' => [
                'sometimes',
                'required_with:date_fin',
                'date',
                'before:date_fin'
            ],
            'date_fin' => [
                'sometimes',
                'required_with:date_debut',
                'date',
                'after:date_debut'
            ],

            'is_active' => ['sometimes', 'boolean'],
            'is_cloturee' => ['sometimes', 'boolean'],
        ];
    }
}
