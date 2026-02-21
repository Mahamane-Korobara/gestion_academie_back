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
            'date_debut' => ['sometimes', 'required', 'date'],
            'date_fin'   => ['sometimes', 'required', 'date', 'after:date_debut'],

            // ✅ Dates d'examens saisies manuellement — requises si fournies
            'date_debut_examens' => ['sometimes', 'required', 'date'],
            'date_fin_examens'   => ['sometimes', 'required', 'date', 'after_or_equal:date_debut_examens'],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_debut_examens.required'     => 'La date de début des examens est obligatoire.',
            'date_fin_examens.required'        => 'La date de fin des examens est obligatoire.',
            'date_fin_examens.after_or_equal'  => 'La date de fin des examens doit être après ou égale à la date de début.',
            'date_fin.after'                   => 'La date de fin doit être après la date de début.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            // Résoudre les dates effectives (requête ou modèle existant)
            $semestre     = $this->route('semestre');
            $debutSemestre = $this->date_debut       ?? $semestre?->date_debut?->toDateString();
            $finSemestre   = $this->date_fin         ?? $semestre?->date_fin?->toDateString();
            $debutExamens  = $this->date_debut_examens ?? $semestre?->date_debut_examens?->toDateString();
            $finExamens    = $this->date_fin_examens   ?? $semestre?->date_fin_examens?->toDateString();

            if (!$debutSemestre || !$finSemestre || !$debutExamens || !$finExamens) return;

            // Cohérence début < fin du semestre
            if ($debutSemestre > $finSemestre) {
                $validator->errors()->add('date_debut', 'La date de début doit être avant la date de fin.');
            }

            // Examens dans la période du semestre
            if ($debutExamens < $debutSemestre || $debutExamens > $finSemestre) {
                $validator->errors()->add(
                    'date_debut_examens',
                    'La date de début des examens doit être comprise dans la période du semestre.'
                );
            }

            if ($finExamens < $debutSemestre || $finExamens > $finSemestre) {
                $validator->errors()->add(
                    'date_fin_examens',
                    'La date de fin des examens doit être comprise dans la période du semestre.'
                );
            }
        });
    }
}