<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:cours,code'],
            'description' => ['nullable', 'string'],
            'coefficient' => ['required', 'numeric', 'min:0.5', 'max:10'],
            'nombre_heures' => ['nullable', 'integer', 'min:1'],
            'niveau_id' => ['required', 'exists:niveaux,id'],
            'semestre_id' => ['required', 'exists:semestres,id'],
            'annee_academique_id' => ['required', 'exists:annees_academiques,id'],
            'professeur_ids' => ['nullable', 'array'],
            'professeur_ids.*' => ['exists:professeurs,id'],
        ];
    }
}
