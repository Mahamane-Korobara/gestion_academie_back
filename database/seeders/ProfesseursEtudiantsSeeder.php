<?php

namespace Database\Seeders;

use App\Enums\StudentStatus;
use App\Models\AnneeAcademique;
use App\Models\Etudiant;
use App\Models\Filiere;
use App\Models\Niveau;
use App\Models\Professeur;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfesseursEtudiantsSeeder extends Seeder
{
    public function run(): void
    {
        $roleProfesseur = Role::where('name', 'professeur')->firstOrFail();
        $roleEtudiant = Role::where('name', 'etudiant')->firstOrFail();

        $anneeActive = $this->ensureActiveAcademicYear();
        $niveauxMaster = $this->getMasterNiveaux();

        $this->seedProfesseurs($roleProfesseur->id);
        $this->seedEtudiants($roleEtudiant->id, $anneeActive->id, $niveauxMaster);

        $this->command?->info('Professeurs et etudiants maliens seedees avec repartition M1/M2.');
    }

    private function ensureActiveAcademicYear(): AnneeAcademique
    {
        $activeYear = AnneeAcademique::active()->first();
        if ($activeYear) {
            return $activeYear;
        }

        AnneeAcademique::query()->update(['is_active' => false]);

        return AnneeAcademique::firstOrCreate(
            ['annee' => '2025-2026'],
            [
                'date_debut' => '2025-10-01',
                'date_fin' => '2026-07-31',
                'is_active' => true,
                'is_cloturee' => false,
            ]
        );
    }

    /**
     * @return array<int, Niveau>
     */
    private function getMasterNiveaux(): array
    {
        $codes = ['MIG', 'MRT', 'MFCA', 'MDA'];
        $niveaux = [];

        foreach ($codes as $code) {
            $filiere = Filiere::where('code', $code)->first();
            if (!$filiere) {
                continue;
            }

            $m1 = Niveau::where('filiere_id', $filiere->id)->where('nom', 'M1')->first();
            $m2 = Niveau::where('filiere_id', $filiere->id)->where('nom', 'M2')->first();

            if ($m1) {
                $niveaux[] = $m1;
            }
            if ($m2) {
                $niveaux[] = $m2;
            }
        }

        if (empty($niveaux)) {
            throw new \RuntimeException('Aucun niveau master trouve. Lancez FilieresMasterSeeder et NiveauxMasterSeeder.');
        }

        return $niveaux;
    }

    private function seedProfesseurs(int $roleProfesseurId): void
    {
        $professeurs = [
            ['prenom' => 'Mamadou', 'nom' => 'Traore', 'specialite' => 'Systemes d Information', 'grade' => 'Professeur'],
            ['prenom' => 'Fatoumata', 'nom' => 'Keita', 'specialite' => 'Finance et Audit', 'grade' => 'Maitre de conferences'],
            ['prenom' => 'Abdoulaye', 'nom' => 'Coulibaly', 'specialite' => 'Reseaux et Securite', 'grade' => 'Professeur'],
            ['prenom' => 'Aissata', 'nom' => 'Diallo', 'specialite' => 'Droit des Affaires', 'grade' => 'Maitre de conferences'],
            ['prenom' => 'Boubacar', 'nom' => 'Sissoko', 'specialite' => 'Data et BI', 'grade' => 'Assistant'],
            ['prenom' => 'Hawa', 'nom' => 'Sangare', 'specialite' => 'Gestion Financiere', 'grade' => 'Assistant'],
            ['prenom' => 'Yacouba', 'nom' => 'Dembele', 'specialite' => 'Telecommunications', 'grade' => 'Maitre de conferences'],
            ['prenom' => 'Mariam', 'nom' => 'Konate', 'specialite' => 'Fiscalite OHADA', 'grade' => 'Professeur'],
        ];

        foreach ($professeurs as $index => $profil) {
            $i = $index + 1;
            $email = sprintf(
                '%s.%s@universite-ml.edu.ml',
                Str::lower($profil['prenom']),
                Str::lower($profil['nom'])
            );

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'role_id' => $roleProfesseurId,
                    'name' => $profil['prenom'] . ' ' . $profil['nom'],
                    'phone' => '+2237' . str_pad((string) (1000000 + $i), 7, '0', STR_PAD_LEFT),
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                    'must_change_password' => true,
                    'email_verified_at' => now(),
                ]
            );

            Professeur::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'code_professeur' => sprintf('ML-PRF-%03d', $i),
                    'nom' => $profil['nom'],
                    'prenom' => $profil['prenom'],
                    'specialite' => $profil['specialite'],
                    'grade' => $profil['grade'],
                    'email_professionnel' => $email,
                    'telephone' => '+2237' . str_pad((string) (2000000 + $i), 7, '0', STR_PAD_LEFT),
                    'bio' => 'Enseignant de master, profil cree pour simulation realiste du Mali.',
                ]
            );
        }
    }

    /**
     * @param array<int, Niveau> $niveaux
     */
    private function seedEtudiants(int $roleEtudiantId, int $anneeAcademiqueId, array $niveaux): void
    {
        $etudiants = [
            ['prenom' => 'Oumou', 'nom' => 'Diakite', 'sexe' => 'F'],
            ['prenom' => 'Ibrahim', 'nom' => 'Maiga', 'sexe' => 'M'],
            ['prenom' => 'Kadiatou', 'nom' => 'Cisse', 'sexe' => 'F'],
            ['prenom' => 'Seydou', 'nom' => 'Doumbia', 'sexe' => 'M'],
            ['prenom' => 'Aminata', 'nom' => 'Bagayoko', 'sexe' => 'F'],
            ['prenom' => 'Modibo', 'nom' => 'Camara', 'sexe' => 'M'],
            ['prenom' => 'Nafissatou', 'nom' => 'Sidibe', 'sexe' => 'F'],
            ['prenom' => 'Cheick', 'nom' => 'Toure', 'sexe' => 'M'],
            ['prenom' => 'Assitan', 'nom' => 'Diarra', 'sexe' => 'F'],
            ['prenom' => 'Moussa', 'nom' => 'Togo', 'sexe' => 'M'],
            ['prenom' => 'Awa', 'nom' => 'Sanogo', 'sexe' => 'F'],
            ['prenom' => 'Bakary', 'nom' => 'Thiam', 'sexe' => 'M'],
            ['prenom' => 'Rokia', 'nom' => 'Berthe', 'sexe' => 'F'],
            ['prenom' => 'Lassana', 'nom' => 'Kone', 'sexe' => 'M'],
            ['prenom' => 'Fanta', 'nom' => 'Yattara', 'sexe' => 'F'],
            ['prenom' => 'Hamidou', 'nom' => 'Koumare', 'sexe' => 'M'],
            ['prenom' => 'Adjaratou', 'nom' => 'Samake', 'sexe' => 'F'],
            ['prenom' => 'Nouhoum', 'nom' => 'Ballo', 'sexe' => 'M'],
            ['prenom' => 'Kany', 'nom' => 'Kante', 'sexe' => 'F'],
            ['prenom' => 'Ismael', 'nom' => 'Soumare', 'sexe' => 'M'],
            ['prenom' => 'Binta', 'nom' => 'Kassogue', 'sexe' => 'F'],
            ['prenom' => 'Mahamadou', 'nom' => 'Coumare', 'sexe' => 'M'],
            ['prenom' => 'Maimouna', 'nom' => 'Fofana', 'sexe' => 'F'],
            ['prenom' => 'Adama', 'nom' => 'Barry', 'sexe' => 'M'],
            ['prenom' => 'Nene', 'nom' => 'Toure', 'sexe' => 'F'],
            ['prenom' => 'Souleymane', 'nom' => 'Sow', 'sexe' => 'M'],
            ['prenom' => 'Assetou', 'nom' => 'Sagara', 'sexe' => 'F'],
            ['prenom' => 'Issa', 'nom' => 'Kane', 'sexe' => 'M'],
            ['prenom' => 'Massa', 'nom' => 'Traore', 'sexe' => 'F'],
            ['prenom' => 'Djibril', 'nom' => 'Dembele', 'sexe' => 'M'],
            ['prenom' => 'Salimata', 'nom' => 'Keita', 'sexe' => 'F'],
        ];

        foreach ($etudiants as $index => $profil) {
            $i = $index + 1;
            $niveau = $niveaux[$index % count($niveaux)];
            $filiere = Filiere::find($niveau->filiere_id);

            $email = sprintf(
                '%s.%s%02d@etudiant.universite-ml.edu.ml',
                Str::lower(Str::ascii($profil['prenom'])),
                Str::lower(Str::ascii($profil['nom'])),
                $i
            );

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'role_id' => $roleEtudiantId,
                    'name' => $profil['prenom'] . ' ' . $profil['nom'],
                    'phone' => '+2237' . str_pad((string) (3000000 + $i), 7, '0', STR_PAD_LEFT),
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                    'must_change_password' => true,
                    'email_verified_at' => now(),
                ]
            );

            Etudiant::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'matricule' => sprintf('ML25-%s-%s-%03d', $filiere?->code ?? 'MST', $niveau->nom, $i),
                    'nom' => $profil['nom'],
                    'prenom' => $profil['prenom'],
                    'date_naissance' => now()->subYears(22 + ($i % 5))->subDays($i * 3)->toDateString(),
                    'sexe' => $profil['sexe'],
                    'lieu_naissance' => $i % 2 === 0 ? 'Bamako' : 'Sikasso',
                    'adresse' => $i % 3 === 0 ? 'Commune IV, Bamako' : 'Commune V, Bamako',
                    'email_personnel' => $email,
                    'telephone' => '+2237' . str_pad((string) (4000000 + $i), 7, '0', STR_PAD_LEFT),
                    'telephone_urgence' => '+2237' . str_pad((string) (5000000 + $i), 7, '0', STR_PAD_LEFT),
                    'filiere_id' => $niveau->filiere_id,
                    'niveau_id' => $niveau->id,
                    'annee_academique_id' => $anneeAcademiqueId,
                    'statut' => StudentStatus::ACTIF->value,
                    'date_inscription' => now()->toDateString(),
                ]
            );
        }
    }
}
