<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSemestreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['sometimes', 'date'],

            'date_debut_examens' => ['nullable', 'date'],
            'date_fin_examens' => ['nullable', 'date'],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            // Vérifie cohérence dates semestre
            if ($this->filled('date_debut') && $this->filled('date_fin') && $this->date_debut > $this->date_fin) {
                $validator->errors()->add('date_debut', 'La date de début doit être avant la date de fin.');
            }

            // Vérifie cohérence dates examens
            if ($this->filled('date_debut_examens') && $this->filled('date_fin_examens') && $this->date_debut_examens > $this->date_fin_examens) {
                $validator->errors()->add('date_debut_examens', 'La date de début des examens doit être avant la date de fin des examens.');
            }

            // Vérifie que les examens sont dans le semestre si semestre modifié
            if ($this->filled('date_debut_examens') && $this->filled('date_debut') && $this->filled('date_fin')) {
                if ($this->date_debut_examens < $this->date_debut || $this->date_debut_examens > $this->date_fin) {
                    $validator->errors()->add('date_debut_examens', 'La date de début des examens doit être comprise dans le semestre.');
                }
            }

            if ($this->filled('date_fin_examens') && $this->filled('date_debut') && $this->filled('date_fin')) {
                if ($this->date_fin_examens < $this->date_debut || $this->date_fin_examens > $this->date_fin) {
                    $validator->errors()->add('date_fin_examens', 'La date de fin des examens doit être comprise dans le semestre.');
                }
            }
        });
    }
}
