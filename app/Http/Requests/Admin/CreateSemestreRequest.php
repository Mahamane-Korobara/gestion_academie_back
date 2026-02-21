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
            'annee_academique_id' => ['required', 'exists:annees_academiques,id'],

            'numero' => [
                'required',
                Rule::in(['S1', 'S2']),
            ],

            'date_debut' => ['required', 'date'],
            'date_fin'   => ['required', 'date', 'after:date_debut'],

            // ✅ Dates d'examens obligatoires et saisies manuellement par l'admin
            'date_debut_examens' => ['required', 'date'],
            'date_fin_examens'   => ['required', 'date', 'after_or_equal:date_debut_examens'],

            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'annee_academique_id.required' => 'L\'année académique est obligatoire.',
            'annee_academique_id.exists'   => 'L\'année académique sélectionnée n\'existe pas.',
            'numero.required'              => 'Le numéro du semestre est obligatoire.',
            'numero.in'                    => 'Le semestre doit être S1 ou S2.',
            'date_debut.required'          => 'La date de début est obligatoire.',
            'date_fin.required'            => 'La date de fin est obligatoire.',
            'date_fin.after'               => 'La date de fin doit être après la date de début.',
            'date_debut_examens.required'  => 'La date de début des examens est obligatoire.',
            'date_fin_examens.required'    => 'La date de fin des examens est obligatoire.',
            'date_fin_examens.after_or_equal' => 'La date de fin des examens doit être après ou égale à la date de début des examens.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            // Doublon : même semestre pour la même année
            if ($this->filled('annee_academique_id') && $this->filled('numero')) {
                $exists = Semestre::where('annee_academique_id', $this->annee_academique_id)
                    ->where('numero', $this->numero)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('numero', 'Ce semestre existe déjà pour cette année académique.');
                }
            }

            // Les examens doivent être dans la période du semestre
            if ($this->filled(['date_debut', 'date_fin', 'date_debut_examens', 'date_fin_examens'])) {
                $debutSemestre = $this->date_debut;
                $finSemestre   = $this->date_fin;

                if ($this->date_debut_examens < $debutSemestre || $this->date_debut_examens > $finSemestre) {
                    $validator->errors()->add(
                        'date_debut_examens',
                        'La date de début des examens doit être comprise dans la période du semestre.'
                    );
                }

                if ($this->date_fin_examens < $debutSemestre || $this->date_fin_examens > $finSemestre) {
                    $validator->errors()->add(
                        'date_fin_examens',
                        'La date de fin des examens doit être comprise dans la période du semestre.'
                    );
                }
            }
        });
    }
}