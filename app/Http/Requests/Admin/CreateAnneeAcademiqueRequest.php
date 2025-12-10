<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateAnneeAcademiqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'annee'      => ['required', 'string', 'unique:annees_academiques,annee', 'regex:/^\d{4}-\d{4}$/'],
            'date_debut' => ['required', 'date', 'before:date_fin'],
            'date_fin'   => ['required', 'date', 'after:date_debut'],
            'is_active'  => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'annee.required' => 'L\'année académique est obligatoire',
            'annee.unique'   => 'Cette année académique existe déjà',
            'annee.regex'    => 'Le format doit être YYYY-YYYY (ex: 2024-2025)',
            'date_debut.required' => 'La date de début est obligatoire',
            'date_debut.before'   => 'La date de début doit être avant la date de fin',
            'date_fin.required'   => 'La date de fin est obligatoire',
            'date_fin.after'      => 'La date de fin doit être après la date de début',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Nettoyer le format de l'année
        if ($this->has('annee')) {
            $this->merge([
                'annee' => trim($this->annee),
            ]);
        }

        // Générer automatiquement l'année si non fournie
        if (!$this->has('annee') && $this->filled('date_debut')) {
            $year = date('Y', strtotime($this->date_debut));
            $nextYear = $year + 1;
            $this->merge(['annee' => "$year-$nextYear"]);
        }

        // Mettre un défaut pour is_active
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
