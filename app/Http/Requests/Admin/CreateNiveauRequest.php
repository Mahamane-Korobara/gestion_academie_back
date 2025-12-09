<?php
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateNiveauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'filiere_id' => ['required', 'exists:filieres,id'],
            'nom' => [
                'required',
                'string',
                'max:50',
                Rule::unique('niveaux')->where(function ($query) {
                    return $query->where('filiere_id', $this->filiere_id);
                })
            ],
            'ordre' => ['required', 'integer', 'min:1'],
            'nombre_semestres' => ['required', 'integer', 'min:1', 'max:4'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.unique' => 'Ce niveau existe déjà pour cette filière',
            'filiere_id.required' => 'La filière est obligatoire',
            'filiere_id.exists' => 'La filière sélectionnée n\'existe pas',
        ];
    }
}