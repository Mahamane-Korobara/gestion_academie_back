<?php

namespace Database\Seeders;

use App\Models\AnneeAcademique;
use App\Models\Cours;
use App\Models\Filiere;
use App\Models\Niveau;
use App\Models\Semestre;
use Illuminate\Database\Seeder;

class CoursMasterSeeder extends Seeder
{
    public function run(): void
    {
        $anneeActive = $this->ensureActiveAcademicYear();
        [$s1, $s2] = $this->ensureSemestres($anneeActive->id);

        $catalogue = [
            'MIG' => [
                'M1' => [
                    ['titre' => 'Architecture des Systemes d Information', 'code' => 'MIG-M1-ASI', 'coefficient' => 3.0, 'heures' => 45, 'semestre_id' => $s1->id],
                    ['titre' => 'Analyse de Donnees Avancee', 'code' => 'MIG-M1-ADA', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s1->id],
                    ['titre' => 'Management de Projet Numerique', 'code' => 'MIG-M1-MPN', 'coefficient' => 2.0, 'heures' => 30, 'semestre_id' => $s2->id],
                    ['titre' => 'Cybersecurite pour Entreprise', 'code' => 'MIG-M1-CSE', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s2->id],
                ],
                'M2' => [
                    ['titre' => 'Gouvernance des SI', 'code' => 'MIG-M2-GSI', 'coefficient' => 3.0, 'heures' => 42, 'semestre_id' => $s1->id],
                    ['titre' => 'Big Data et BI', 'code' => 'MIG-M2-BDBI', 'coefficient' => 3.0, 'heures' => 42, 'semestre_id' => $s1->id],
                    ['titre' => 'Audit des SI', 'code' => 'MIG-M2-ASI2', 'coefficient' => 2.0, 'heures' => 30, 'semestre_id' => $s2->id],
                    ['titre' => 'Memoire et Soutenance', 'code' => 'MIG-M2-MEM', 'coefficient' => 4.0, 'heures' => 24, 'semestre_id' => $s2->id],
                ],
            ],
            'MRT' => [
                'M1' => [
                    ['titre' => 'Administration Reseau Avancee', 'code' => 'MRT-M1-ARA', 'coefficient' => 3.0, 'heures' => 45, 'semestre_id' => $s1->id],
                    ['titre' => 'Transmission Numerique', 'code' => 'MRT-M1-TNUM', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s1->id],
                    ['titre' => 'Securite des Infrastructures', 'code' => 'MRT-M1-SINF', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s2->id],
                    ['titre' => 'VoIP et Services Telecom', 'code' => 'MRT-M1-VOIP', 'coefficient' => 2.0, 'heures' => 30, 'semestre_id' => $s2->id],
                ],
                'M2' => [
                    ['titre' => 'Cloud et Virtualisation', 'code' => 'MRT-M2-CV', 'coefficient' => 3.0, 'heures' => 42, 'semestre_id' => $s1->id],
                    ['titre' => 'Ingenierie 5G et IoT', 'code' => 'MRT-M2-5GIOT', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s1->id],
                    ['titre' => 'Supervision et Qualite de Service', 'code' => 'MRT-M2-SQS', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s2->id],
                    ['titre' => 'Projet de Fin d Etudes Telecom', 'code' => 'MRT-M2-PFE', 'coefficient' => 4.0, 'heures' => 24, 'semestre_id' => $s2->id],
                ],
            ],
            'MFCA' => [
                'M1' => [
                    ['titre' => 'Normes Comptables OHADA', 'code' => 'MFCA-M1-NCO', 'coefficient' => 3.0, 'heures' => 45, 'semestre_id' => $s1->id],
                    ['titre' => 'Analyse Financiere', 'code' => 'MFCA-M1-AF', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s1->id],
                    ['titre' => 'Controle de Gestion Avance', 'code' => 'MFCA-M1-CGA', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s2->id],
                    ['titre' => 'Fiscalite des Entreprises', 'code' => 'MFCA-M1-FE', 'coefficient' => 2.0, 'heures' => 30, 'semestre_id' => $s2->id],
                ],
                'M2' => [
                    ['titre' => 'Audit Legal et Contractuel', 'code' => 'MFCA-M2-ALC', 'coefficient' => 3.0, 'heures' => 42, 'semestre_id' => $s1->id],
                    ['titre' => 'Gestion des Risques Financiers', 'code' => 'MFCA-M2-GRF', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s1->id],
                    ['titre' => 'Evaluation d Entreprise', 'code' => 'MFCA-M2-EE', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s2->id],
                    ['titre' => 'Memoire Professionnel Audit', 'code' => 'MFCA-M2-MPA', 'coefficient' => 4.0, 'heures' => 24, 'semestre_id' => $s2->id],
                ],
            ],
            'MDA' => [
                'M1' => [
                    ['titre' => 'Droit OHADA Approfondi', 'code' => 'MDA-M1-DOA', 'coefficient' => 3.0, 'heures' => 45, 'semestre_id' => $s1->id],
                    ['titre' => 'Techniques Contractuelles', 'code' => 'MDA-M1-TC', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s1->id],
                    ['titre' => 'Contentieux Commercial', 'code' => 'MDA-M1-CC', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s2->id],
                    ['titre' => 'Droit Fiscal des Affaires', 'code' => 'MDA-M1-DFA', 'coefficient' => 2.0, 'heures' => 30, 'semestre_id' => $s2->id],
                ],
                'M2' => [
                    ['titre' => 'Arbitrage et Mediation', 'code' => 'MDA-M2-AM', 'coefficient' => 3.0, 'heures' => 42, 'semestre_id' => $s1->id],
                    ['titre' => 'Conformite et Gouvernance', 'code' => 'MDA-M2-CG', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s1->id],
                    ['titre' => 'Pratique du Conseil Juridique', 'code' => 'MDA-M2-PCJ', 'coefficient' => 2.5, 'heures' => 36, 'semestre_id' => $s2->id],
                    ['titre' => 'Memoire Professionnel Droit', 'code' => 'MDA-M2-MPD', 'coefficient' => 4.0, 'heures' => 24, 'semestre_id' => $s2->id],
                ],
            ],
        ];

        foreach ($catalogue as $codeFiliere => $parNiveau) {
            $filiere = Filiere::where('code', $codeFiliere)->first();
            if (!$filiere) {
                continue;
            }

            foreach ($parNiveau as $nomNiveau => $coursList) {
                $niveau = Niveau::where('filiere_id', $filiere->id)->where('nom', $nomNiveau)->first();
                if (!$niveau) {
                    continue;
                }

                foreach ($coursList as $cours) {
                    Cours::updateOrCreate(
                        ['code' => $cours['code'], 'annee_academique_id' => $anneeActive->id],
                        [
                            'titre' => $cours['titre'],
                            'description' => 'Cours de master adapte au contexte professionnel malien.',
                            'coefficient' => $cours['coefficient'],
                            'nombre_heures' => $cours['heures'],
                            'niveau_id' => $niveau->id,
                            'semestre_id' => $cours['semestre_id'],
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        $this->command?->info('Cours master (M1/M2) seedees.');
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
     * @return array<int, Semestre>
     */
    private function ensureSemestres(int $anneeAcademiqueId): array
    {
        $s1 = Semestre::updateOrCreate(
            ['annee_academique_id' => $anneeAcademiqueId, 'numero' => 'S1'],
            [
                'date_debut' => '2025-10-01',
                'date_fin' => '2026-01-31',
                'date_debut_examens' => '2026-01-20',
                'date_fin_examens' => '2026-01-31',
                'is_active' => true,
            ]
        );

        $s2 = Semestre::updateOrCreate(
            ['annee_academique_id' => $anneeAcademiqueId, 'numero' => 'S2'],
            [
                'date_debut' => '2026-02-01',
                'date_fin' => '2026-06-30',
                'date_debut_examens' => '2026-06-20',
                'date_fin_examens' => '2026-06-30',
                'is_active' => false,
            ]
        );

        return [$s1, $s2];
    }
}
