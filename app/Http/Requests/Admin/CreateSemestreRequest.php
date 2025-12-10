<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Semestre;

class CreateSemestreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'annee_academique_id' => [
                'required',
                'exists:annees_academiques,id'
            ],

            'numero' => [
                'required',
                Rule::in(['S1','S2']),
            ],

            'date_debut' => [
                'required',
                'date'
            ],

            'date_fin' => [
                'required',
                'date',
                'after:date_debut'
            ],

            'date_debut_examens' => [
                'nullable',
                'date'
            ],

            'date_fin_examens' => [
                'nullable',
                'date'
            ],

            'is_active' => [
                'boolean'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'annee_academique_id.required' => 'L\'année académique est obligatoire.',
            'annee_academique_id.exists' => 'L\'année académique sélectionnée n\'existe pas.',

            'numero.required' => 'Le numéro du semestre est obligatoire.',
            'numero.in' => 'Le semestre doit être S1 ou S2.',

            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début est invalide.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.after' => 'La date de fin doit être après la date de début.',

            'date_debut_examens.date' => 'La date de début des examens est invalide.',
            'date_fin_examens.date' => 'La date de fin des examens est invalide.',

            'is_active.boolean' => 'Le champ is_active doit être un booléen (true ou false).',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            // Vérification doublon année + semestre
            if ($this->filled('annee_academique_id') && $this->filled('numero')) {
                $exists = Semestre::where('annee_academique_id', $this->annee_academique_id)
                    ->where('numero', $this->numero)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('numero', 'Ce semestre existe déjà pour cette année académique.');
                }
            }

            // Validation des dates d'examens 

            // Vérifie cohérence début exam < fin exam
            if ($this->filled('date_debut_examens') && $this->filled('date_fin_examens')) {
                if ($this->date_debut_examens > $this->date_fin_examens) {
                    $validator->errors()->add('date_debut_examens',
                        'La date de début des examens doit être avant la date de fin des examens.');
                }
            }

            // Vérifie que les examens sont dans la période du semestre
            if ($this->filled('date_debut_examens')) {
                if ($this->date_debut_examens < $this->date_debut || $this->date_debut_examens > $this->date_fin) {
                    $validator->errors()->add('date_debut_examens',
                        'La date de début des examens doit être comprise dans la période du semestre.');
                }
            }

            if ($this->filled('date_fin_examens')) {
                if ($this->date_fin_examens < $this->date_debut || $this->date_fin_examens > $this->date_fin) {
                    $validator->errors()->add('date_fin_examens',
                        'La date de fin des examens doit être comprise dans la période du semestre.');
                }
            }
        });
    }
}
