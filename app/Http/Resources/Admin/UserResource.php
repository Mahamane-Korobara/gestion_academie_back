<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'is_active' => $this->is_active,
            'must_change_password' => $this->must_change_password,
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),

            'role' => [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'display_name' => $this->role->display_name,
            ],

            // Profil dynamique selon rôle
            'profile' => $this->getProfile(),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    private function getProfile()
    {
        // === ÉTUDIANT ===
        if ($this->isEtudiant() && $this->etudiant) {
            return [
                'type' => 'etudiant',
                'id' => $this->etudiant->id,
                'matricule' => $this->etudiant->matricule,
                'nom' => $this->etudiant->nom,
                'prenom' => $this->etudiant->prenom,
                'nom_complet' => $this->etudiant->nom_complet ?? ($this->etudiant->nom . ' ' . $this->etudiant->prenom),
                'filiere' => $this->etudiant->filiere->nom,
                'niveau' => $this->etudiant->niveau->nom,
                'statut' => $this->etudiant->statut->label(),
            ];
        }

        // === PROFESSEUR ===
        if ($this->isProfesseur() && $this->professeur) {
            return [
                'type' => 'professeur',
                'id' => $this->professeur->id,
                'code' => $this->professeur->code_professeur,
                'nom' => $this->professeur->nom,
                'prenom' => $this->professeur->prenom,
                'nom_complet' => $this->professeur->nom_complet ?? ($this->professeur->nom . ' ' . $this->professeur->prenom),
                'specialite' => $this->professeur->specialite,
                'grade' => $this->professeur->grade,
            ];
        }

        // === ADMIN ===
        if ($this->isAdmin()) {
            return [
                'type' => 'admin',
                'permissions' => ['all']
            ];
        }

        return null;
    }
}
