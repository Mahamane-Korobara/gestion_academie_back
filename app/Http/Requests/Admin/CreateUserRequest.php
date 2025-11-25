<?php
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\UserRole;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(UserRole::values())],

            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],

            // Si le rôle = étudiant
            'etudiant' => ['required_if:role,' . UserRole::ETUDIANT->value],
            'etudiant.matricule' => ['required_with:etudiant', 'unique:etudiants,matricule'],
            'etudiant.nom' => ['required_with:etudiant', 'string'],
            'etudiant.prenom' => ['required_with:etudiant', 'string'],
            'etudiant.date_naissance' => ['required_with:etudiant', 'date'],
            'etudiant.sexe' => ['required_with:etudiant', 'in:M,F'],
            'etudiant.filiere_id' => ['required_with:etudiant', 'exists:filieres,id'],
            'etudiant.niveau_id' => ['required_with:etudiant', 'exists:niveaux,id'],

            // Si le rôle = professeur
            'professeur' => ['required_if:role,' . UserRole::PROFESSEUR->value],
            'professeur.code_professeur' => ['required_with:professeur', 'unique:professeurs,code_professeur'],
            'professeur.nom' => ['required_with:professeur', 'string'],
            'professeur.prenom' => ['required_with:professeur', 'string'],
            'professeur.specialite' => ['nullable', 'string'],
            'professeur.grade' => ['nullable', 'string'],
        ];
    }
}
