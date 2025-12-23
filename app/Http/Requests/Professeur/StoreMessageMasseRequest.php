<?php

namespace App\Http\Requests\Professeur;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Cours;

class StoreMessageMasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isProfesseur();
    }

    public function rules(): array
    {
        return [
            'filiere_id' => 'required|exists:filieres,id',
            'niveau_id' => 'required|exists:niveaux,id',
            'cours_id' => 'required|exists:cours,id',
            'sujet' => 'required|string|max:255',
            'contenu' => 'required|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérifier que le cours appartient à la filière et au niveau
            $cours = Cours::find($this->cours_id);
            if (!$cours || 
                $cours->niveau_id != $this->niveau_id || 
                $cours->niveau->filiere_id != $this->filiere_id) {
                $validator->errors()->add('cours_id', 'Le cours doit appartenir à la filière et au niveau sélectionnés.');
            }

            // Vérifier que le professeur enseigne ce cours
            $profId = $this->user()->professeur->id;
            if (!Cours::where('id', $this->cours_id)
                ->whereHas('professeurs', fn($q) => $q->where('professeurs.id', $profId))
                ->exists()) {
                $validator->errors()->add('cours_id', 'Vous n\'enseignez pas ce cours.');
            }
        });
    }
}