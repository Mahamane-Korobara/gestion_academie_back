<?php
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\UserRole;
use App\Models\Role;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        // Si role est envoyé (nom du rôle), le convertir en role_id
        if ($this->has('role') && !$this->has('role_id')) {
            $role = Role::where('name', $this->role)->first();
            $this->merge([
                'role_id' => $role?->id,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // Accepter soit role (nom) soit role_id (ID)
            'role' => ['required_without:role_id', Rule::in(UserRole::values())],
            'role_id' => ['required_without:role', 'exists:roles,id'],
            
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
            
            // Pour les étudiants (détection automatique)
            'etudiant' => ['required_if:role,' . UserRole::ETUDIANT->value],
            'etudiant.matricule' => ['required_with:etudiant', 'unique:etudiants,matricule'],
            'etudiant.nom' => ['required_with:etudiant', 'string'],
            'etudiant.prenom' => ['required_with:etudiant', 'string'],
            'etudiant.date_naissance' => ['required_with:etudiant', 'date'],
            'etudiant.sexe' => ['required_with:etudiant', 'in:M,F'],
            'etudiant.filiere_id' => ['required_with:etudiant', 'exists:filieres,id'],
            'etudiant.niveau_id' => ['required_with:etudiant', 'exists:niveaux,id'],
            'etudiant.lieu_naissance' => ['nullable', 'string'],
            'etudiant.adresse' => ['nullable', 'string'],
            'etudiant.email_personnel' => ['nullable', 'email'],
            'etudiant.telephone' => ['nullable', 'string'],
            'etudiant.telephone_urgence' => ['nullable', 'string'],
            
            // Pour les professeurs (détection automatique)
            'professeur' => ['required_if:role,' . UserRole::PROFESSEUR->value],
            'professeur.code_professeur' => ['required_with:professeur', 'unique:professeurs,code_professeur'],
            'professeur.nom' => ['required_with:professeur', 'string'],
            'professeur.prenom' => ['required_with:professeur', 'string'],
            'professeur.specialite' => ['nullable', 'string'],
            'professeur.grade' => ['nullable', 'string'],
            'professeur.bio' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required_without' => 'Le rôle est obligatoire',
            'role.in' => 'Le rôle doit être: admin, professeur ou etudiant',
            'role_id.required_without' => 'L\'ID du rôle est obligatoire',
            'role_id.exists' => 'Le rôle sélectionné n\'existe pas',
            
            'name.required' => 'Le nom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            
            'etudiant.required_if' => 'Les informations de l\'étudiant sont obligatoires',
            'etudiant.matricule.required_with' => 'Le matricule est obligatoire',
            'etudiant.matricule.unique' => 'Ce matricule existe déjà',
            'etudiant.nom.required_with' => 'Le nom de l\'étudiant est obligatoire',
            'etudiant.prenom.required_with' => 'Le prénom de l\'étudiant est obligatoire',
            'etudiant.date_naissance.required_with' => 'La date de naissance est obligatoire',
            'etudiant.sexe.required_with' => 'Le sexe est obligatoire',
            'etudiant.filiere_id.required_with' => 'La filière est obligatoire',
            'etudiant.niveau_id.required_with' => 'Le niveau est obligatoire',
            
            'professeur.required_if' => 'Les informations du professeur sont obligatoires',
            'professeur.code_professeur.required_with' => 'Le code professeur est obligatoire',
            'professeur.code_professeur.unique' => 'Ce code professeur existe déjà',
            'professeur.nom.required_with' => 'Le nom du professeur est obligatoire',
            'professeur.prenom.required_with' => 'Le prénom du professeur est obligatoire',
        ];
    }
}