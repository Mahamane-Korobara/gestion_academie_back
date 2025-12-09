<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNiveauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'nom' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('niveaux')->where(function ($query) {
                    return $query->where('filiere_id', $this->niveau->filiere_id);
                })->ignore($this->niveau)
            ],
            'ordre' => ['sometimes', 'integer', 'min:1'],
            'nombre_semestres' => ['sometimes', 'integer', 'min:1', 'max:4'],
        ];
    }
}
