<?php

namespace App\Http\Requests\Professeur;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TypeAnnonce;
use Illuminate\Validation\Rule;

class StoreAnnonceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // On autorise si l'utilisateur est bien un professeur
        return $this->user()->isProfesseur();
    }

    public function rules(): array
    {
        return [
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            // On exclut 'globale' car seul l'admin peut le faire
            'type' => ['required', Rule::in(['filiere', 'niveau', 'cours', 'individuelle'])],
            
            // Validations conditionnelles selon le type
            'filiere_id' => 'required_if:type,filiere|exists:filieres,id',
            'niveau_id'  => 'required_if:type,niveau|exists:niveaux,id',
            'cours_id'   => 'required_if:type,cours|exists:cours,id',
            'destinataire_id' => 'required_if:type,individuelle|exists:users,id',
            
            // Attention aux valeurs de priorité ! Doivent matcher ton Enum
            'priorite' => 'required|in:normale,importante,urgente',
            'date_expiration' => 'nullable|date|after:today',
        ];
    }
}