<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TypeAnnonce;

class UpdateAnnonceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'type' => 'required|in:' . implode(',', TypeAnnonce::values()),
            'filiere_id' => 'required_if:type,filiere|exists:filieres,id',
            'niveau_id' => 'required_if:type,niveau|exists:niveaux,id',
            'cours_id' => 'required_if:type,cours|exists:cours,id',
            'destinataire_id' => 'required_if:type,individuelle|exists:users,id',
            'priorite' => 'required|in:normale,importante,urgente',
            'date_expiration' => 'nullable|date|after:today',
        ];
    }
}