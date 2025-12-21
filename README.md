# Système de Gestion Académique v0.3.0

## 📋 Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Technologies utilisées](#technologies-utilisées)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Architecture du projet](#architecture-du-projet)
- [Base de données](#base-de-données)
- [Authentification](#authentification)
- [Controllers & Routes](#-controllers--routes-api)
- [Requests & Resources](#-requests--resources-api)
- [Policies & Authorization](#-policies--authorization)
- [Services & Helpers](#-services--helpers)
- [Middleware & Sécurité](#-middleware--architecture-de-sécurité)
- [Système de cache](#-système-de-cache)
- [État du projet](#-état-détaillé-du-projet)
- [Tests](#-tests)
- [Commandes utiles](#-commandes-utiles)
- [Sécurité](#-sécurité)
- [Logs et debugging](#-logs-et-debugging)
- [Déploiement](#-déploiement)
- [Changelog](#-changelog)

---

## 🎯 Vue d'ensemble

Système complet de gestion académique pour établissements d'enseignement supérieur avec 3 types d'utilisateurs et une API RESTful complète.

### Acteurs du système

- **Administrateur** : Gestion complète (users, filières, cours, évaluations, notes, planning)
- **Professeur** : Gestion des cours, saisie des notes, consultation planning
- **Étudiant** : Consultation des notes, bulletins, emplois du temps

### Fonctionnalités principales

✅ **Authentification** : Tokens API sécurisés (Laravel Sanctum)  
✅ **Gestion des utilisateurs** : CRUD avec 3 rôles distincts  
✅ **Gestion académique** : Filières, niveaux, cours, inscriptions  
✅ **Évaluations & Notes** : CRUD complet, saisie (prof), validation (admin)  
✅ **Emplois du temps** : Planning des cours avec détection de conflits  
✅ **Bulletins** : Génération semestrielle et annuelle  
✅ **Cache optimisé** : Redis/File avec TTL adaptatif  
✅ **Logs d'activité** : Traçabilité complète de toutes les actions  
✅ **API RESTful** : 80+ endpoints documentés  
✅ **Validation stricte** : FormRequest sur tous les endpoints  
✅ **Authorization** : Policies pour contrôle d'accès fine-grained  

---

## 🛠️ Technologies utilisées

### Backend
- **Laravel 11** (PHP 8.2+)
- **MySQL 8.0+**
- **Laravel Sanctum** (Authentification API)
- **Redis** (Cache - optionnel)

### Frontend (Planned v0.4.0)
- **Next.js 14+**
- **React 18+**
- **TailwindCSS**

---

## 📦 Prérequis

Assurez-vous d'avoir installé :

- **PHP 8.2+** 
  ```bash
  php -v
  ```
- **Composer**
  ```bash
  composer -V
  ```
- **MySQL 8.0+**
  ```bash
  mysql --version
  ```
- **Node.js 18+** (pour le frontend)
  ```bash
  node -v
  ```

---

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/Mahamane-Korobara/gestion_academie_back.git
cd gestion-academique
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Configuration de l'environnement

```bash
# Copier le fichier .env
cp .env.example .env

# Générer la clé d'application
php artisan key:generate
```

### 4. Configurer la base de données

**Créer la base de données MySQL :**

```sql
mysql -u root -p

CREATE DATABASE gestion_academique CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'academique_user'@'localhost' IDENTIFIED BY 'votre_mot_de_passe';
GRANT ALL PRIVILEGES ON gestion_academique.* TO 'academique_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Modifier le fichier `.env` :**

```env
APP_NAME="Gestion Académique"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Africa/Algiers

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_academique
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
# Pour production, utiliser Redis :
# CACHE_DRIVER=redis
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379
```

### 5. Exécuter les migrations et seeders

```bash
php artisan migrate:fresh --seed
```

**Ceci va créer :**
- ✅ 22 tables dans la base de données
- ✅ 3 rôles (Admin, Professeur, Étudiant)
- ✅ 5 types d'évaluations
- ✅ 1 compte administrateur
- ✅ 2 semestres (S1, S2)

### 6. Lancer le serveur

```bash
php artisan serve
```

Le serveur sera accessible sur : `http://localhost:8000`

---

## 📁 Architecture du projet

```
gestion-academique/
├── app/
│   ├── Enums/                      # 14 Énumérations
│   │   ├── UserRole.php (✅)
│   │   ├── StudentStatus.php (✅)
│   │   ├── Sexe.php (✅)
│   │   ├── Semestre.php (✅)
│   │   ├── JourSemaine.php (✅)
│   │   ├── TypeSeance.php (✅)
│   │   ├── StatutNote.php (✅)
│   │   ├── StatutEvaluation.php (✅)
│   │   ├── DecisionBulletin.php (✅)
│   │   ├── TypeAnnonce.php (✅)
│   │   ├── PrioriteAnnonce.php (✅)
│   │   ├── TypeDocument.php (✅)
│   │   ├── StatutDocument.php (✅)
│   │   └── ActionLog.php (✅)
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── API/
│   │   │       ├── Auth/
│   │   │       │   └── AuthController.php (✅ Implémenté)
│   │   │       ├── Admin/ (9 controllers)
│   │   │       │   ├── UserController.php (✅ CRUD complet)
│   │   │       │   ├── FiliereController.php (✅ CRUD complet)
│   │   │       │   ├── NiveauController.php (✅ CRUD + niveaux auto)
│   │   │       │   ├── CoursController.php (✅ CRUD complet)
│   │   │       │   ├── AnneeAcademiqueController.php (✅ CRUD + gestion dates)
│   │   │       │   ├── SemestreController.php (✅ CRUD + activation)
│   │   │       │   ├── InscriptionController.php (✅ Manuel et masse)
│   │   │       │   ├── EvaluationController.php (✅ CRUD évaluations) - NOUVEAU ✨
│   │   │       │   ├── AffectationController.php (✅ Affecter profs) - NOUVEAU ✨
│   │   │       │   ├── NoteAdminController.php (✅ Validation notes) - NOUVEAU ✨
│   │   │       │   └── DashboardController.php (✅ Stats dashboard)
│   │   │       └── Professeur/ (1 controller)
│   │   │           └── NoteController.php (✅ Saisie notes) - NOUVEAU ✨
│   │   │
│   │   ├── Middleware/
│   │   │   ├── CheckUserActive.php (✅ Implémenté)
│   │   │   ├── CheckPasswordChange.php (✅ Implémenté)
│   │   │   └── CheckRole.php (✅ Implémenté)
│   │   │
│   │   ├── Requests/ (Validation par FormRequest - 18 total)
│   │   │   ├── Auth/ (3)
│   │   │   │   ├── LoginRequest.php (✅)
│   │   │   │   ├── ChangePasswordRequest.php (✅)
│   │   │   │   └── UpdateProfileRequest.php (✅)
│   │   │   └── Admin/ (15)
│   │   │       ├── CreateUserRequest.php (✅)
│   │   │       ├── CreateFiliereRequest.php (✅)
│   │   │       ├── UpdateFiliereRequest.php (✅)
│   │   │       ├── CreateNiveauRequest.php (✅)
│   │   │       ├── UpdateNiveauRequest.php (✅)
│   │   │       ├── CreateCoursRequest.php (✅)
│   │   │       ├── CreateAnneeAcademiqueRequest.php (✅)
│   │   │       ├── UpdateAnneeAcademiqueRequest.php (✅)
│   │   │       ├── CreateSemestreRequest.php (✅)
│   │   │       ├── UpdateSemestreRequest.php (✅)
│   │   │       ├── CreateInscriptionRequest.php (✅)
│   │   │       ├── InscriptionMasseRequest.php (✅)
│   │   │       ├── InscriptionNiveauRequest.php (✅)
│   │   │       ├── CreateEvaluationRequest.php (✅) - NOUVEAU ✨
│   │   │       └── UpdateEvaluationRequest.php (✅) - NOUVEAU ✨
│   │   │
│   │   ├── Resources/ (10 Ressources API)
│   │   │   └── Admin/
│   │   │       ├── UserResource.php (✅)
│   │   │       ├── FiliereResource.php (✅)
│   │   │       ├── FiliereStatResource.php (✅ Stats)
│   │   │       ├── NiveauResource.php (✅)
│   │   │       ├── CoursResource.php (✅)
│   │   │       ├── AnneeAcademiqueResource.php (✅)
│   │   │       ├── SemestreResource.php (✅)
│   │   │       ├── InscriptionResource.php (✅)
│   │   │       ├── EvaluationResource.php (✅) - NOUVEAU ✨
│   │   │       └── NoteResource.php (✅) - NOUVEAU ✨
│   │   │
│   │   └── Policies/ (2 Policies - NOUVEAU ✨)
│   │       ├── NotePolicy.php (✅ Authorization notes)
│   │       └── EvaluationPolicy.php (✅ Authorization évaluations)
│   │
│   ├── Models/                     # 20 Modèles Eloquent
│   │   ├── User.php (✅ avec eager loading role)
│   │   ├── Role.php (✅)
│   │   ├── Filiere.php (✅)
│   │   ├── Niveau.php (✅)
│   │   ├── AnneeAcademique.php (✅)
│   │   ├── Semestre.php (✅)
│   │   ├── Etudiant.php (✅)
│   │   ├── Professeur.php (✅)
│   │   ├── Cours.php (✅)
│   │   ├── Inscription.php (✅)
│   │   ├── Salle.php (✅)
│   │   ├── EmploiDuTemps.php (✅)
│   │   ├── TypeEvaluation.php (✅)
│   │   ├── Evaluation.php (✅)
│   │   ├── Note.php (✅) - NOUVEAU ✨
│   │   ├── Bulletin.php (✅)
│   │   ├── Annonce.php (✅)
│   │   ├── Notification.php (✅)
│   │   ├── Message.php (✅)
│   │   ├── Document.php (✅)
│   │   └── LogActivite.php (✅)
│   │
│   └── Services/                   # Services métier
│       └── CacheService.php (✅ Cache avec TTL adaptatif)
│
├── database/
│   ├── migrations/                 # 22 migrations
│   │   ├── 2024_01_01_000000_create_roles_table.php
│   │   ├── 2024_01_01_000001_create_users_table.php
│   │   ├── 2024_01_01_000002_create_filieres_table.php
│   │   ├── 2024_01_01_000003_create_niveaux_table.php
│   │   ├── 2024_01_01_000004_create_annees_academiques_table.php
│   │   ├── 2024_01_01_000005_create_semestres_table.php
│   │   ├── 2024_01_01_000006_create_etudiants_table.php
│   │   ├── 2024_01_01_000007_create_professeurs_table.php
│   │   ├── 2024_01_01_000008_create_cours_table.php
│   │   ├── 2024_01_01_000009_create_cours_professeur_table.php
│   │   ├── 2024_01_01_000010_create_inscriptions_table.php
│   │   ├── 2024_01_01_000011_create_salles_table.php
│   │   ├── 2024_01_01_000012_create_emplois_du_temps_table.php
│   │   ├── 2024_01_01_000013_create_types_evaluations_table.php
│   │   ├── 2024_01_01_000014_create_evaluations_table.php
│   │   ├── 2024_01_01_000015_create_notes_table.php
│   │   ├── 2024_01_01_000016_create_bulletins_table.php
│   │   ├── 2024_01_01_000017_create_annonces_table.php
│   │   ├── 2024_01_01_000018_create_notifications_table.php
│   │   ├── 2024_01_01_000019_create_messages_table.php
│   │   ├── 2024_01_01_000020_create_documents_table.php
│   │   └── 2024_01_01_000021_create_logs_activite_table.php
│   │
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RoleSeeder.php
│       ├── TypeEvaluationSeeder.php
│       ├── AdminSeeder.php
│
├── routes/
│   └── api.php                     # Routes API
│
└── config/
    ├── sanctum.php                 # Configuration Sanctum
    └── cors.php                    # Configuration CORS
```

---

## 🗄️ Base de données

### Schéma relationnel

Le système utilise **22 tables** interconnectées :

#### Tables principales

1. **users** - Comptes utilisateurs
2. **roles** - Rôles (Admin, Professeur, Étudiant)
3. **filieres** - Filières d'études
4. **niveaux** - Niveaux par filière (L1, L2, L3, M1, M2)
5. **annees_academiques** - Années académiques
6. **semestres** - Semestres (S1, S2)
7. **etudiants** - Profils étudiants
8. **professeurs** - Profils professeurs
9. **cours** - Cours enseignés
10. **inscriptions** - Inscriptions étudiants/cours
11. **salles** - Salles de cours
12. **emplois_du_temps** - Planning des cours
13. **types_evaluations** - Types d'évaluation (CC, EF, TP, etc.)
14. **evaluations** - Évaluations planifiées
15. **notes** - Notes des étudiants
16. **bulletins** - Bulletins générés
17. **annonces** - Annonces système
18. **notifications** - Notifications utilisateurs
19. **messages** - Messagerie interne
20. **documents** - Documents générés
21. **logs_activite** - Traçabilité complète
22. **cours_professeur** - Table pivot cours/professeurs

### Relations clés

```
User (1) → (1) Etudiant
User (1) → (1) Professeur
Filiere (1) → (N) Niveaux
Filiere (1) → (N) Etudiants
Niveau (1) → (N) Cours
Niveau (1) → (N) Etudiants
Cours (N) ↔ (N) Professeurs (pivot: cours_professeur)
Etudiant (N) ↔ (N) Cours (via: inscriptions)
Etudiant (1) → (N) Notes
Etudiant (1) → (N) Bulletins
```

### Commandes utiles

```bash
# Voir l'état des migrations
php artisan migrate:status

# Refaire toutes les migrations
php artisan migrate:fresh --seed

# Rollback dernière migration
php artisan migrate:rollback

# Créer une nouvelle migration
php artisan make:migration create_table_name

# Créer un model avec migration
php artisan make:model ModelName -m
```

---

## 🔐 Authentification

Le système utilise **Laravel Sanctum** pour l'authentification API par tokens.

### Comptes par défaut

Après `php artisan migrate:fresh --seed` :

**Administrateur :**
```
Email    : admin@gestion-academique.ml
Password : admin123456
```

### Flow d'authentification

1. **Login** → Obtenir un token
2. **Utiliser le token** dans l'header `Authorization: Bearer {token}`
3. **Logout** → Invalider le token

### Middleware de sécurité

- `auth:sanctum` - Vérifier l'authentification
- `check.user.active` - Vérifier que le compte est actif
- `check.password.change` - Forcer changement de mot de passe si nécessaire
- `role:admin` - Vérifier le rôle admin
- `role:professeur` - Vérifier le rôle professeur
- `role:etudiant` - Vérifier le rôle étudiant

### Changement de mot de passe obligatoire

Les nouveaux utilisateurs (étudiants/professeurs) doivent changer leur mot de passe à la première connexion.

---

## 🔧 Requests & Resources API

### Form Requests (Validation)

**Authentication (3):**
- `LoginRequest` - Email, Password
- `ChangePasswordRequest` - Old/New password validation
- `UpdateProfileRequest` - Nom, Email, Phone

**Admin - Users (1):**
- `CreateUserRequest` - Validation création users

**Admin - Filières (2):**
- `CreateFiliereRequest` - Nom, Code, Durée, Description
- `UpdateFiliereRequest` - Modification filière

**Admin - Niveaux (2):**
- `CreateNiveauRequest` - Filiere, Ordre, Description
- `UpdateNiveauRequest` - Modification niveau

**Admin - Cours (1):**
- `CreateCoursRequest` - Code, Nom, Credits, Descriptif, Professeurs

**Admin - Années Académiques (2):**
- `CreateAnneeAcademiqueRequest` - Dates, Active flag
- `UpdateAnneeAcademiqueRequest` - Modification année

**Admin - Semestres (2):**
- `CreateSemestreRequest` - Numéro, Dates, Année
- `UpdateSemestreRequest` - Modification semestre

**Admin - Inscriptions (3):**
- `CreateInscriptionRequest` - Étudiant, Cours, Semestre
- `InscriptionMasseRequest` - Inscriptions en masse
- `InscriptionNiveauRequest` - Auto-inscription niveau

**Admin - Évaluations (2):** - NOUVEAU ✨
- `CreateEvaluationRequest` - Cours, Type, Dates, Salle
- `UpdateEvaluationRequest` - Modification évaluation

### Authorization Policies (Contrôle d'accès)

**NotePolicy** - NOUVEAU ✨
```php
- saisirNotes($user, $evaluation) 
  // Un professeur peut saisir notes seulement s'il enseigne le cours
  
- voir($user, $evaluation)
  // Un professeur peut voir notes de ses cours
  
- valider($user, $evaluation) 
  // Un admin peut valider n'importe quelle note
  
- supprimer($user, $evaluation)
  // Un admin peut supprimer notes
  
- voirNotes($user, $evaluation)
  // Un étudiant peut voir ses notes
```

**EvaluationPolicy** - NOUVEAU ✨
```php
- creer($user, $evaluation)
  // Un admin peut créer évaluations
  
- modifier($user, $evaluation)
  // Un admin peut modifier évaluations
  
- supprimer($user, $evaluation)
  // Un admin peut supprimer évaluations
```

### API Resources (Transformation)

**User Resource**
```php
- id, name, email, phone, is_active
- role (relation)
- etudiant/professeur (relation polymorphe)
- last_login_at, created_at
```

**Filiere Resource**
```php
- id, nom, code, duree_annees, description
- niveaux_count
- etudiants_count
- niveaux (relation)
```

**Filiere Stat Resource** (Dashboard)
```php
- id, nom, code
- etudiants_count
```

**Niveau Resource**
```php
- id, nom, ordre, description
- filiere (relation)
- filiere_nom
```

**Cours Resource**
```php
- id, code, nom, credits, descriptif
- niveau (relation)
- professeurs (relation)
- inscriptions_count
```

**AnneeAcademique Resource**
```php
- id, date_debut, date_fin, is_active
- etudiants_count
- cours_count
- inscriptions_count
- semestres_count
```

**Semestre Resource**
```php
- id, numero, date_debut, date_fin, is_active
- annee_academique (relation)
- inscriptions_count
- evaluations_count
- bulletins_count
```

**Inscription Resource**
```php
- id, etudiant_id, cours_id, semestre_id
- etudiant (relation)
- cours (relation)
- semestre (relation)
- date_inscription
```

**Evaluation Resource** - NOUVEAU ✨
```php
- id, code, nom, description
- cours (relation)
- typeEvaluation (relation)
- semestre (relation)
- salle (relation)
- date_evaluation, heure_debut, heure_fin
- notes_count, statut
```

**Note Resource** - NOUVEAU ✨
```php
- id, valeur, statut (brouillon, soumise, validee)
- etudiant (relation)
- evaluation (relation)
- saisiPar (relation - professeur)
- validePar (relation - admin)
- date_saisie, date_validation
```

---

## 🛣️ Routes API détaillées

Base URL : `http://localhost:8000/api`

### Authentication

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| POST | `/auth/login` | Connexion | Non |
| GET | `/auth/me` | Info utilisateur | Oui |
| POST | `/auth/logout` | Déconnexion | Oui |
| POST | `/auth/logout-all` | Déconnexion tous appareils | Oui |
| POST | `/auth/change-password` | Changer mot de passe | Oui |
| POST | `/auth/update-profile` | MAJ profil | Oui |
| GET | `/auth/sessions` | Sessions actives | Oui |
| DELETE | `/auth/sessions/{id}` | Supprimer session | Oui |

### Admin - Utilisateurs

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/admin/users` | Liste utilisateurs |
| POST | `/admin/users` | Créer utilisateur |
| GET | `/admin/users/{id}` | Détails utilisateur |
| PUT | `/admin/users/{id}` | Modifier utilisateur |
| DELETE | `/admin/users/{id}` | Supprimer utilisateur |
| POST | `/admin/users/{id}/reset-password` | Réinitialiser MDP |
| POST | `/admin/users/{id}/toggle-active` | Activer/Désactiver |

### Admin - Filières

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/admin/filieres` | Liste filières |
| POST | `/admin/filieres` | Créer filière |
| GET | `/admin/filieres/{id}` | Détails filière |
| PUT | `/admin/filieres/{id}` | Modifier filière |
| DELETE | `/admin/filieres/{id}` | Supprimer filière |
| POST | `/admin/filieres/{id}/create-standard-levels` | Créer niveaux auto |

### Admin - Niveaux

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/admin/niveaux/all` | Tous les niveaux |
| GET | `/admin/niveaux?filiere_id=X` | Niveaux par filière |
| POST | `/admin/niveaux` | Créer niveau |
| GET | `/admin/niveaux/{id}` | Détails niveau |
| PUT | `/admin/niveaux/{id}` | Modifier niveau |
| DELETE | `/admin/niveaux/{id}` | Supprimer niveau |

### Admin - Cours

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/admin/cours` | Liste cours (filtres : niveau_id, semestre) |
| POST | `/admin/cours` | Créer cours |
| GET | `/admin/cours/{id}` | Détails cours |
| PUT | `/admin/cours/{id}` | Modifier cours |
| DELETE | `/admin/cours/{id}` | Supprimer cours |

### Admin - Années Académiques

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/admin/annees-academiques` | Liste années |
| GET | `/admin/annees-academiques/active` | Année active |
| POST | `/admin/annees-academiques` | Créer année |
| GET | `/admin/annees-academiques/{id}` | Détails année |
| PUT | `/admin/annees-academiques/{id}` | Modifier année |
| DELETE | `/admin/annees-academiques/{id}` | Supprimer année |
| POST | `/admin/annees-academiques/{id}/activate` | Activer année |
| POST | `/admin/annees-academiques/{id}/close` | Fermer année |
| POST | `/admin/annees-academiques/{id}/create-semestres` | Créer semestres auto |

### Admin - Semestres

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/admin/semestres` | Liste semestres (param: annee_academique_id) |
| GET | `/admin/semestres/active` | Semestre actif |
| POST | `/admin/semestres` | Créer semestre |
| GET | `/admin/semestres/{id}` | Détails semestre |
| PUT | `/admin/semestres/{id}` | Modifier semestre |
| DELETE | `/admin/semestres/{id}` | Supprimer semestre |
| POST | `/admin/semestres/{id}/activate` | Activer semestre |

### Admin - Inscriptions

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/admin/inscriptions` | Liste inscriptions |
| POST | `/admin/inscriptions` | Inscription manuelle |
| POST | `/admin/inscriptions/masse` | Inscriptions en masse |
| GET | `/admin/inscriptions/etudiant/{id}` | Inscriptions étudiant |
| GET | `/admin/inscriptions/cours/{id}` | Inscriptions cours |
| DELETE | `/admin/inscriptions/{id}` | Supprimer inscription |
| POST | `/admin/etudiants/{id}/inscrire-cours-niveau` | Auto-inscription niveau |

### Admin - Évaluations - NOUVEAU ✨

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/admin/evaluations` | Toutes les évaluations |
| GET | `/admin/evaluations/cours/{id}` | Évaluations d'un cours |
| POST | `/admin/evaluations/cours/{id}` | Créer évaluation |
| GET | `/admin/evaluations/{id}` | Détails évaluation |
| PUT | `/admin/evaluations/{id}` | Modifier évaluation |
| DELETE | `/admin/evaluations/{id}` | Supprimer évaluation |

### Admin - Affectation Professeurs - NOUVEAU ✨

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/admin/cours/{id}/affecter-professeurs` | Affecter profs au cours |

### Admin - Validation Notes - NOUVEAU ✨

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| PATCH | `/admin/notes/{id}/valider` | Valider une note |
| GET | `/admin/notes/en-attente` | Lister notes en attente |
| POST | `/admin/notes/valider-masse` | Valider notes en masse |

### Admin - Dashboard

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/admin/dashboard` | Statistiques dashboard |

### Professeur - Saisie Notes - NOUVEAU ✨

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/professeur/evaluations/{id}/notes` | Saisir notes d'évaluation |

---

## 🔐 Middleware & Architecture de Sécurité

### Middlewares personnalisés (3)

**1. CheckUserActive**
```php
// Vérifie que le compte utilisateur est actif
// Rejette les requêtes des comptes désactivés
Route::middleware('check.user.active')->...
```

**2. CheckPasswordChange**
```php
// Force les nouveaux utilisateurs à changer leur mot de passe
// Bloque l'accès à certaines routes jusqu'au changement
Route::middleware('check.password.change')->...
```

**3. CheckRole** (Role-Based Access Control)
```php
// Filtre par rôle utilisateur
Route::middleware('role:admin')->...
Route::middleware('role:professeur')->...
Route::middleware('role:etudiant')->...
```

### Stack de middlewares par groupe de routes

**Routes publiques (Login):**
```
- throttle:3,1 (rate limiting 3 tentatives/minute)
```

**Routes authentifiées:**
```
- auth:sanctum (vérifier token)
- check.user.active (compte actif)
```

**Routes Admin:**
```
- auth:sanctum
- role:admin
- check.user.active
- check.password.change
```

**Routes Professeur:** (structure prête)
```
- auth:sanctum
- role:professeur
- check.user.active
- check.password.change
```

**Routes Étudiant:** (structure prête)
```
- auth:sanctum
- role:etudiant
- check.user.active
- check.password.change
```

### Patterns d'implémentation

**1. Validation avec FormRequest**
```php
// Chaque endpoint a une Request class dédiée
public function store(CreateFiliereRequest $request) {
    // $request->validated() retourne données validées
    $data = $request->validated();
}
```

**2. API Resource Response**
```php
// Toutes les réponses utilisent des Resources pour transformer les données
return new FiliereResource($filiere);
return FiliereResource::collection($filieres);
```

**3. Cache Intelligent**
```php
// Cache automatique pour les GET
// Invalidation automatique pour POST/PUT/DELETE
Cache::remember($cacheKey, $ttl, fn() => $data);
CacheService::forgetFilieres();
```

**4. Transaction Database**
```php
// Garantit l'intégrité des données multi-opérations
DB::beginTransaction();
try {
    $cours->save();
    $cours->professeurs()->attach($professorIds);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    return error();
}
```

**5. Logs d'activité**
```php
// Trace toutes les actions pour audit
LogActivite::create([
    'user_id' => $user->id,
    'action' => ActionLog::LOGIN,
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);
```

### Exemples de requêtes

**Login :**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@gestion-academique.ml",
    "password": "admin123456"
  }'
```

**Dashboard Admin :**
```bash
curl -X GET http://localhost:8000/api/admin/dashboard \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

**Créer une filière :**
```bash
curl -X POST http://localhost:8000/api/admin/filieres \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Informatique",
    "code": "INFO",
    "duree_annees": 3,
    "description": "Licence en Informatique"
  }'
```

**Créer niveaux automatiques (L1-L3, M1-M2) :**
```bash
curl -X POST http://localhost:8000/api/admin/filieres/{filiere_id}/create-standard-levels \
  -H "Authorization: Bearer {token}"
```

**Lister cours par niveau et semestre :**
```bash
curl -X GET "http://localhost:8000/api/admin/cours?niveau_id=1&semestre=S1" \
  -H "Authorization: Bearer {token}"
```

**Créer année académique :**
```bash
curl -X POST http://localhost:8000/api/admin/annees-academiques \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "date_debut": "2025-09-01",
    "date_fin": "2026-08-31",
    "is_active": true
  }'
```

**Créer semestres automatiques (S1, S2) :**
```bash
curl -X POST http://localhost:8000/api/admin/annees-academiques/{annee_id}/create-semestres \
  -H "Authorization: Bearer {token}"
```

**Inscription manuelle étudiant à des cours :**
```bash
curl -X POST http://localhost:8000/api/admin/inscriptions \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "etudiant_id": 1,
    "cours_ids": [1, 2, 3],
    "semestre_id": 1
  }'
```

**Auto-inscription étudiant à tous les cours de son niveau :**
```bash
curl -X POST http://localhost:8000/api/admin/etudiants/{etudiant_id}/inscrire-cours-niveau \
  -H "Authorization: Bearer {token}"
```

**Créer un étudiant :**
```bash
curl -X POST http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "role": "etudiant",
    "name": "Ahmed Ben Ali",
    "email": "ahmed@email.dz",
    "phone": "0661234567",
    "etudiant": {
      "matricule": "STU2025001",
      "nom": "Ben Ali",
      "prenom": "Ahmed",
      "date_naissance": "2003-05-15",
      "sexe": "M",
      "filiere_id": 1,
      "niveau_id": 1
    }
  }'
```

**Créer un professeur :**
```bash
curl -X POST http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "role": "professeur",
    "name": "Dr. Mohamed Benaamir",
    "email": "benaamir@email.dz",
    "phone": "0661234567",
    "professeur": {
      "nom": "Benaamir",
      "prenom": "Mohamed",
      "specialite": "Informatique",
      "grade": "Maître de conférences"
    }
  }'
```

---

## 🛠️ Services & Helpers

### Services implémentés (6)

**CacheService**
```php
// Gestion centralisée du cache avec TTL adaptatif
CacheService::remember($key, $ttl, $callback);
CacheService::forgetFilieres();  // Invalider cache spécifique
CacheService::SHORT_TTL   // 5 minutes
CacheService::DEFAULT_TTL // 1 heure
CacheService::LONG_TTL    // 24 heures
```

**LogService**
```php
// Logging structuré des actions (audit trail complet)
LogService::write(
    $action,        // ActionLog enum
    $description,
    $model,
    $oldData,
    $extraData
);
```

**CalculAcademique**
```php
// Calculs de moyennes et décisions académiques
CalculAcademique::calculerMoyenne($notes);
CalculAcademique::decisionBulletin($moyenne);  // Admis/Ajourné/Rattrapage
```

**EmploiDuTempsService** ✅
```php
// Gestion planning des cours avec détection conflits
EmploiDuTempsService::trouverConflits($data);
EmploiDuTempsService::creerSeance($data);
EmploiDuTempsService::professeursDisponibles($params);
```

**EmploiDuTempsEtudiantService** ✅
```php
// Planning personnalisé pour étudiant
EmploiDuTempsEtudiantService::planningComplet($etudiant);
EmploiDuTempsEtudiantService::planningJour($etudiant, $date);
EmploiDuTempsEtudiantService::planningProchains($etudiant);
```

**PdfService**
```php
// Génération PDF (bulletins, documents)
PdfService::genererBulletin($bulletin);
PdfService::genererDocument($data);
```

---

## 🔐 Policies & Authorization

### 5 Policies pour contrôle d'accès fine-grained

**BulletinPolicy**
- `view()` : Admin peut voir tous, Étudiant ses bulletins
- `create()` : Admin seulement
- `delete()` : Admin seulement

**EtudiantPolicy**
- `view()` : Étudiant peut voir son profil, Admin tous
- `update()` : Étudiant son profil, Admin tous

**EmploiDuTempsPolicy**
- `manage()` : Admin seulement
- `viewProfesseur()` : Professeur peut voir son planning
- `viewEtudiant()` : Étudiant peut voir son planning

**EvaluationPolicy** ✅ NOUVEAU
- `creer()` : Admin seulement
- `modifier()` : Admin seulement
- `supprimer()` : Admin seulement

**NotePolicy** ✅ NOUVEAU
- `saisirNotes()` : Professeur qui enseigne le cours seulement
- `voir()` : Professeur voir ses cours, Admin voir tous, Étudiant voir ses notes
- `valider()` : Admin seulement
- `supprimer()` : Admin seulement

---

## 👥 Fonctionnalités par Acteur

### Administrateur ✅

**Gestion Académique :**
- CRUD Filières et Niveaux
- Créer niveaux standard auto (L1-L3, M1-M2)
- CRUD Années académiques et Semestres
- Gérer une année active à la fois
- Créer semestres auto (S1, S2)

**Gestion Cours :**
- CRUD Cours
- Affecter/retirer professeurs
- Filtrer par niveau et semestre
- Voir inscriptions par cours

**Gestion Utilisateurs :**
- Créer/Modifier/Supprimer utilisateurs
- Gérer 3 rôles (Admin, Prof, Étudiant)
- Réinitialiser mots de passe
- Activer/Désactiver comptes
- Voir dernière connexion

**Gestion Inscriptions :**
- Inscrire manuellement étudiants
- Inscriptions en masse (CSV/JSON)
- Auto-inscription par niveau
- Voir inscriptions par étudiant/cours

**Gestion Évaluations :**
- Créer/Modifier/Supprimer évaluations
- Lier à cours et type d'évaluation
- Gérer dates, salles, horaires

**Gestion Notes :**
- Valider notes individuelles
- Lister notes en attente
- Valider en masse (jusqu'à 100 notes)
- Voir historique saisie/validation

**Gestion Planning :**
- Créer emplois du temps
- Détecter conflits (niveau, prof, salle)
- Voir disponibilités profs/cours
- Supprimer séances

**Dashboard :**
- Voir statistiques globales
- Étudiants par filière
- Dernière activité
- Données formatées

### Professeur ✅

**Gestion Notes :**
- Saisir notes pour ses évaluations
- Voir statut (brouillon, soumise, validée)
- Modifier notes en brouillon

**Consultation :**
- Voir son emploi du temps personnel
- Voir ses cours et inscriptions
- Voir moyennes de ses étudiants

### Étudiant ✅

**Consultation Notes :**
- Voir ses notes par évaluation
- Voir ses bulletins (semestriels et annuels)
- Télécharger bulletins en PDF
- Voir ses cours inscrits

**Emploi du Temps :**
- Voir planning complet
- Voir planning de la semaine
- Voir planning du jour
- Voir résumé des cours
- Voir prochains cours à venir

---

## 🔥 Système de cache

Le système utilise un cache intelligent pour optimiser les performances.

### Configuration

**File Cache (par défaut) :**
```env
CACHE_DRIVER=file
```

**Redis (recommandé pour production) :**
```bash
# Installer Redis
composer require predis/predis

# Dans .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Durées de cache (TTL)

- **SHORT_TTL** : 5 minutes (listes paginées)
- **DEFAULT_TTL** : 1 heure (détails, données stables)
- **LONG_TTL** : 24 heures (données rarement modifiées)

### Invalidation automatique

Le cache est automatiquement invalidé lors de :
- Création d'un utilisateur
- Modification d'une filière
- Suppression d'un cours
- Etc.

### Commandes cache

```bash
# Vider tout le cache
php artisan cache:clear

# Vider le cache de config
php artisan config:clear

# Vider le cache de routes
php artisan route:clear
```

---

## 🧪 Tests

### Tester avec Postman

1. **Importer la collection** (à créer)
2. **Se connecter** avec le compte admin
3. **Copier le token** reçu
4. **Utiliser le token** dans toutes les requêtes

### Tester avec cURL

Voir les exemples dans la section [API Endpoints](#api-endpoints)

### Tests unitaires (à venir)

```bash
# Exécuter les tests
php artisan test

# Tests avec couverture
php artisan test --coverage
```

---

## 📊 État détaillé du projet (v0.1.0)

### ✅ Architecture et Structure

| Composant | Status | Détails |
|-----------|--------|---------|
| **Controllers** | ✅ 12 implémentés | Auth (1), Admin (9), Professeur (1) + NoteController |
| **Resources** | ✅ 10 implémentées | Toutes les entités + Evaluation + Note |
| **Requests** | ✅ 18 implémentées | Validation complète |
| **Policies** | ✅ 2 implémentées | NotePolicy, EvaluationPolicy |
| **Middleware** | ✅ 3 implémentés | Auth, Rôles, Sécurité |
| **Routes API** | ✅ 70+ endpoints | Tous les CRUD + actions spéciales |
| **Models** | ✅ 20 modèles | Relations complètes |
| **Enums** | ✅ 14 énumérations | Types et statuts |
| **Migrations** | ✅ 22 tables | Base complète |
| **Seeders** | ✅ Présents | Données initiales |

### 🎯 Controllers implémentés

#### Authentication Controller
- ✅ Login avec rate limiting
- ✅ Logout (simple et multiple appareils)
- ✅ Récupération info utilisateur (`/me`)
- ✅ Changement de mot de passe
- ✅ Mise à jour profil
- ✅ Gestion sessions actives

#### Admin Controllers (8)

**1. UserController**
- ✅ Lister utilisateurs (pagination, filtres)
- ✅ Créer utilisateurs (Admin/Prof/Étudiant)
- ✅ Afficher détails utilisateur
- ✅ Modifier utilisateur
- ✅ Supprimer utilisateur
- ✅ Réinitialiser mot de passe
- ✅ Activer/Désactiver compte

**2. FiliereController**
- ✅ Lister filières (avec count)
- ✅ Créer filière
- ✅ Afficher détails
- ✅ Modifier filière
- ✅ Supprimer filière
- ✅ Créer niveaux auto (L1-L3, M1-M2)

**3. NiveauController**
- ✅ Lister tous niveaux
- ✅ Lister par filière
- ✅ Créer niveau
- ✅ Afficher détails
- ✅ Modifier niveau
- ✅ Supprimer niveau
- ✅ Créer niveaux standard auto

**4. CoursController**
- ✅ Lister cours (filtres : niveau, semestre)
- ✅ Créer cours avec professeurs
- ✅ Afficher détails
- ✅ Modifier cours
- ✅ Supprimer cours (avec validation)
- ✅ Cache intelligent
- ✅ Assignation professeurs

**5. AnneeAcademiqueController**
- ✅ Lister années
- ✅ Obtenir année active
- ✅ Créer année
- ✅ Afficher détails
- ✅ Modifier année
- ✅ Supprimer année
- ✅ Activer année (une seule active)
- ✅ Fermer année
- ✅ Créer semestres auto

**6. SemestreController**
- ✅ Lister semestres (par année)
- ✅ Obtenir semestre actif
- ✅ Créer semestre
- ✅ Afficher détails
- ✅ Modifier semestre
- ✅ Supprimer semestre
- ✅ Activer semestre
- ✅ Counts associés (inscriptions, évaluations, bulletins)

**7. InscriptionController**
- ✅ Lister inscriptions
- ✅ Inscription manuelle (cours spécifiques)
- ✅ Inscriptions en masse
- ✅ Inscriptions par étudiant
- ✅ Inscriptions par cours
- ✅ Supprimer inscription
- ✅ Auto-inscription niveau/semestre

**8. DashboardController**
- ✅ Résumé statistiques
- ✅ Étudiants par filière
- ✅ Dernière activité
- ✅ Données formatées avec Resources

**9. AffectationController** - NOUVEAU ✨
- ✅ Affecter professeurs aux cours
- ✅ Gestion par année académique
- ✅ Remplacer affectations existantes
- ✅ Cache invalidation intelligente
- ✅ Validation des données

**10. EvaluationController** - NOUVEAU ✨
- ✅ Lister évaluations par cours
- ✅ Créer évaluation
- ✅ Afficher détails évaluation
- ✅ Modifier évaluation
- ✅ Supprimer évaluation
- ✅ Cache avec TTL adaptatif
- ✅ Eager loading relations

**11. NoteAdminController** - NOUVEAU ✨
- ✅ Valider notes individuelles
- ✅ Lister notes en attente (brouillon/soumise)
- ✅ Validation en masse (jusqu'à 100 notes)
- ✅ Filtrage par cours/étudiant
- ✅ Logs d'activité d'audit
- ✅ Transactions DB pour intégrité
- ✅ Authorization via Policy

**12. NoteController (Professeur)** - NOUVEAU ✨
- ✅ Saisir notes pour une évaluation
- ✅ Authorization via NotePolicy
- ✅ Validation des données
- ✅ Contrôle d'accès par cours
- ✅ Vérification professeur enseigne le cours

---

## 📌 Fonctionnalités implémentées

### ✅ Authentification & Sessions
- [x] Login avec email/password
- [x] Logout simple et multiple
- [x] Changement de mot de passe
- [x] Mise à jour profil
- [x] Gestion des sessions actives
- [x] Middleware de sécurité
- [x] Rate limiting (3 tentatives/minute)
- [x] Logs d'activité (LoginActivite)

### ✅ Gestion Admin - Utilisateurs
- [x] CRUD Utilisateurs (Admin, Professeur, Étudiant)
- [x] Réinitialisation mot de passe
- [x] Activation/Désactivation comptes
- [x] Création de profils étudiants/professeurs
- [x] Notifications email à la création
- [x] Filtrage et pagination
- [x] Cache intelligent

### ✅ Gestion Admin - Filières & Niveaux
- [x] CRUD Filières
- [x] CRUD Niveaux
- [x] Création automatique niveaux (L1-L3, M1-M2)
- [x] Relations filière → niveaux
- [x] Counts étudiants par filière
- [x] Cache par filière

### ✅ Gestion Admin - Cours
- [x] CRUD Cours
- [x] Assignation professeurs aux cours
- [x] Filtrage par niveau et semestre
- [x] Counts inscriptions
- [x] Validation des suppressions
- [x] Relation cours ↔ professeurs

### ✅ Gestion Admin - Années Académiques
- [x] CRUD Années académiques
- [x] Activation/Fermeture d'année
- [x] Une seule année active à la fois
- [x] Création semestres automatiques
- [x] Counts semestres/étudiants/cours
- [x] Gestion dates début/fin

### ✅ Gestion Admin - Semestres
- [x] CRUD Semestres
- [x] Activation semestre actif
- [x] Lier à année académique
- [x] Counts inscriptions/évaluations/bulletins
- [x] Numérotation (S1, S2)

### ✅ Gestion Admin - Inscriptions
- [x] Inscription manuelle à cours spécifiques
- [x] Inscriptions en masse
- [x] Auto-inscription niveau/semestre
- [x] Récupération par étudiant/cours
- [x] Suppression inscription
- [x] Validation intelligente
- [x] Cache invalidation

### ✅ Gestion Admin - Évaluations - NOUVEAU ✨
- [x] CRUD Évaluations
- [x] Lier à cours et type d'évaluation
- [x] Gestion dates/salles
- [x] Filtrage par cours
- [x] Pagination avec cache
- [x] Eager loading optimisé

### ✅ Gestion Admin - Affectation Professeurs - NOUVEAU ✨
- [x] Affecter professeurs aux cours
- [x] Gestion par année académique
- [x] Remplacer affectations existantes
- [x] Validation des données
- [x] Cache invalidation intelligente
- [x] Transactions DB

### ✅ Gestion Admin - Validation Notes - NOUVEAU ✨
- [x] Valider notes individuelles
- [x] Lister notes en attente
- [x] Validation en masse (100 max)
- [x] Filtrage par cours/étudiant
- [x] Logs d'audit complets
- [x] Authorization via Policy
- [x] Transactions pour intégrité

### ✅ Gestion Professeur - Saisie Notes - NOUVEAU ✨
- [x] Saisir notes d'évaluation
- [x] Authorization via NotePolicy
- [x] Validation données
- [x] Vérification accès cours
- [x] Logs d'activité
- [x] Transitions d'états (brouillon → soumise)

### ✅ Dashboard Admin
- [x] Résumé statistiques (users, cours, filieres)
- [x] Étudiants par filière
- [x] Dernière activité
- [x] Données formatées avec Resources

### ✅ Système de cache
- [x] Cache intelligent par entité
- [x] Invalidation automatique
- [x] Support Redis et File
- [x] TTL adaptatif (SHORT, DEFAULT, LONG)
- [x] CacheService centralisé

### ✅ Base de données
- [x] 22 tables complètes
- [x] Relations Eloquent
- [x] Seeders (Rôles, Admin, Types évaluations)
- [x] Migrations versionnées
- [x] Contraintes FK

### ✅ Sécurité & Validation
- [x] Authentification par tokens (Sanctum)
- [x] Middleware de rôles (Admin/Prof/Étudiant)
- [x] Validation FormRequest (16 classes)
- [x] Logs d'activité complets
- [x] Protection CSRF
- [x] Hash sécurisé passwords (bcrypt)
- [x] Check compte actif
- [x] Force changement MDP initial

### ✅ API & Routes
- [x] 60+ endpoints API
- [x] Structure RESTful
- [x] Response Resources
- [x] Error handling
- [x] Pagination & filtres
- [x] CORS configuré

### 🚧 À développer

- [x] ~~Controllers Professeur (saisie notes)~~ FAIT ✨
- [x] ~~Gestion Évaluations~~ FAIT ✨
- [x] ~~Gestion Notes (saisie + validation)~~ FAIT ✨
- [x] ~~Affectation Professeurs aux cours~~ FAIT ✨
- [x] ~~Policies (Authorization)~~ FAIT ✨

**Reste à faire (v0.3.0):**
- [ ] Controllers Étudiant (consultation notes, bulletins)
- [ ] Emplois du temps complets
- [ ] Génération bulletins (PDF)
- [ ] Calcul moyennes automatiques
- [ ] Système d'annonces
- [ ] Notifications push
- [ ] Messagerie interne
- [ ] Export PDF/Excel
- [ ] Tests unitaires
- [ ] Interface frontend Next.js
- [ ] Swagger/OpenAPI documentation

---

## 🚀 Déploiement

### Prérequis production

- PHP 8.2+ avec extensions : PDO, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON
- MySQL 8.0+
- Composer 2.x
- Redis (recommandé)
- Serveur web (Nginx/Apache)

### Étapes de déploiement

1. **Cloner le projet**
```bash
git clone https://github.com/Mahamane-Korobara/gestion_academie_back.git /var/www/gestion-academique
cd /var/www/gestion-academique
```

2. **Installer dépendances**
```bash
composer install --optimize-autoloader --no-dev
```

3. **Configuration**
```bash
cp .env.example .env
php artisan key:generate

# Modifier .env pour la production
APP_ENV=production
APP_DEBUG=false
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

4. **Base de données**
```bash
php artisan migrate --force
php artisan db:seed --force
```

5. **Optimisations**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. **Permissions**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 📋 Commandes utiles

### Gestion du projet

```bash
# Démarrer le serveur Laravel
php artisan serve

# Vider tous les caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear

# Afficher les routes API
php artisan route:list --path=api

# Faire les migrations
php artisan migrate

# Refaire les migrations avec seeders
php artisan migrate:fresh --seed

# Rollback dernière migration
php artisan migrate:rollback

# Voir l'état des migrations
php artisan migrate:status

# Créer une nouvelle migration
php artisan make:migration create_table_name

# Créer un model avec migration et controller
php artisan make:model ModelName -mc

# Créer un controller API
php artisan make:controller API/Admin/ControllerName --api

# Créer une FormRequest
php artisan make:request Admin/CreateEntityRequest

# Créer une Resource API
php artisan make:resource Admin/EntityResource

# Lancer les tests
php artisan test

# Tests avec couverture
php artisan test --coverage
```

### Commandes de cache

```bash
# Vider le cache des applications
php artisan cache:clear

# Mettre en cache la configuration
php artisan config:cache

# Mettre en cache les routes
php artisan route:cache

# Vider le cache des vues
php artisan view:clear
```

### Commandes de debugging

```bash
# Afficher les routes
php artisan route:list

# Afficher les middlewares
php artisan middleware:list

# Afficher les providers
php artisan provider:list

# Afficher les commandes disponibles
php artisan list
```

---

## 🛡️ Sécurité

### Bonnes pratiques implémentées

✅ Authentification par tokens (Sanctum)  
✅ Validation stricte des données (FormRequest)  
✅ Protection contre les injections SQL (Eloquent)  
✅ Hashage sécurisé des mots de passe (bcrypt)  
✅ Rate limiting sur login (3 tentatives/minute)  
✅ Logs de toutes les actions (LogActivite)  
✅ Middleware de vérification des rôles (CheckRole)  
✅ Sessions sécurisées (token-based)  
✅ CORS configuré  
✅ Validation des états utilisateur (CheckUserActive)  
✅ Force changement MDP initial (CheckPasswordChange)

### À faire en production

- [ ] HTTPS obligatoire (SSL/TLS)
- [ ] Configurer le firewall
- [ ] Backups automatiques quotidiens
- [ ] Monitoring (Sentry, New Relic, DataDog)
- [ ] Rate limiting global par IP
- [ ] Protection DDoS (CloudFlare, AWS Shield)
- [ ] WAF (Web Application Firewall)
- [ ] Audit logging avancé

---

## 📝 Logs et debugging

### Logs disponibles

```bash
# Logs Laravel (suivi en temps réel)
tail -f storage/logs/laravel.log

# Logs d'activité (table logs_activite)
# Récupérable via database ou API
SELECT * FROM logs_activite ORDER BY created_at DESC;

# Logs par utilisateur
SELECT * FROM logs_activite WHERE user_id = 1;

# Logs par action
SELECT * FROM logs_activite WHERE action = 'LOGIN';
```

### Debug mode

**Développement :**
```env
APP_DEBUG=true
APP_ENV=local
LOG_LEVEL=debug
```

**Production :**
```env
APP_DEBUG=false
APP_ENV=production
LOG_LEVEL=error
```

---

## 📊 Résumé des composants

| Composant | Nombre | Status |
|-----------|--------|--------|
| **Controllers** | 20 | ✅ Complet |
| **Models** | 21 | ✅ Complet |
| **Migrations** | 30+ | ✅ Complet |
| **Requests** | 20 | ✅ Complet |
| **Resources** | 15 | ✅ Complet |
| **Policies** | 5 | ✅ Complet |
| **Services** | 6 | ✅ Complet |
| **Enums** | 14 | ✅ Complet |
| **Routes API** | 80+ | ✅ Complet |
| **Endpoints** | 80+ | ✅ Complet |

---

## 🎉 Changelog

### Version 0.3.0 (Actuelle) - ✅ NOUVEAU

**Ajouté :**
- ✅ 3 nouveaux Controllers Étudiant (notes, bulletins, emplois du temps)
- ✅ 1 nouveau Controller Professeur (EmploiDuTempsProfesseurController)
- ✅ Service EmploiDuTempsService pour gestion planning
- ✅ Service EmploiDuTempsEtudiantService pour planning personnalisé
- ✅ Détection de conflits (niveau, professeur, salle)
- ✅ Disponibilités (professeurs et cours disponibles)
- ✅ 5 Policies d'authorization (Bulletin, Étudiant, EmploiDuTemps, Evaluation, Note)
- ✅ 15 Resources API (ajout Etudiant et Professeur Resources)
- ✅ 80+ endpoints API complets
- ✅ Emplois du temps avec gestion salle et horaires
- ✅ Vérification conflits d'horaires automatique

**Fixé :**
- ✅ Namespace StoreEmploiDuTempsRequest (App\Http\Requests\Admin)
- ✅ Table emploi_du_temps (renommée correcte)
- ✅ Validations semestre dans les requests
- ✅ N+1 queries optimisées avec eager loading

**Documenté :**
- ✅ Tous les 20 controllers documentés
- ✅ Toutes les routes API (80+)
- ✅ Services et patterns d'implémentation
- ✅ Schéma base de données complet
- ✅ Policies et authorization fine-grained
- ✅ Fonctionnalités par acteur (Admin/Prof/Étudiant)

### Version 0.2.0 - 🎯 Évaluations & Notes

**Ajouté :**
- 4 nouveaux Controllers (Evaluation, Affectation, NoteAdmin, NoteController)
- 2 Policies (NotePolicy, EvaluationPolicy)
- 2 nouvelles Resources (EvaluationResource, NoteResource)
- 2 FormRequest pour Évaluations
- Gestion complète des Évaluations (CRUD)
- Affectation des professeurs aux cours
- Saisie des notes par professeurs
- Validation des notes par admin (simple + masse)
- Authorization via Policies
- Routes Professeur implémentées
- 70+ endpoints API
- Logs d'audit complets
- Transactions DB pour intégrité données

**Amélioré :**
- Model User avec eager loading de relation role
- Méthode `hasRole()` générique dans User
- Gestion nullabilité des relations
- Performance optimisée (N+1 queries)
- Documentation mise à jour

### Version 0.1.1 (Améliorations)

**Ajouté :**
- Controllers Admin complets (8 + 1 Auth)
- 16 FormRequest pour validation
- 8 API Resources
- 3 Middlewares sécurité
- 60+ endpoints API
- Dashboard statistiques
- Inscriptions (manuelle, masse, auto)
- Années académiques et semestres
- Cache intelligent avec CacheService
- Logs d'activité complets

**Amélioré :**
- Documentation API détaillée
- Exemples de requêtes cURL
- Architecture sécurité documentée
- Patterns d'implémentation expliqués

### Version 0.1.0 (Base)

**Ajouté :**
- Système d'authentification complet
- Gestion des utilisateurs (CRUD)
- Gestion des filières et niveaux
- Gestion des cours
- Système de cache intelligent
- 22 tables de base de données
- 14 Enums
- 20 Models Eloquent
- API RESTful

**À venir (v0.4.0) :**
- [ ] Tests unitaires complets
- [ ] Interface frontend (Next.js)
- [ ] Swagger/OpenAPI documentation
- [ ] Système d'annonces complet
- [ ] Notifications push
- [ ] Messagerie interne
- [ ] Export PDF/Excel
- [ ] Multi-langue (i18n)
- [ ] Dark mode UI
- [ ] Mobile app (React Native)

---

## 📊 Résumé des composants

| Composant | Nombre | Status |
|-----------|--------|--------|
| **Controllers** | 20 | ✅ Complet |
| **Models** | 21 | ✅ Complet |
| **Migrations** | 30+ | ✅ Complet |
| **Requests** | 20 | ✅ Complet |
| **Resources** | 15 | ✅ Complet |
| **Policies** | 5 | ✅ Complet |
| **Services** | 6 | ✅ Complet |
| **Enums** | 14 | ✅ Complet |
| **Routes API** | 80+ | ✅ Complet |
| **Endpoints** | 80+ | ✅ Complet |

---

## 👥 Fonctionnalités par Acteur

### Administrateur ✅

**Gestion Académique :**
- CRUD Filières et Niveaux
- Créer niveaux standard auto (L1-L3, M1-M2)
- CRUD Années académiques et Semestres
- Gérer une année active à la fois
- Créer semestres auto (S1, S2)

**Gestion Cours :**
- CRUD Cours
- Affecter/retirer professeurs
- Filtrer par niveau et semestre
- Voir inscriptions par cours

**Gestion Utilisateurs :**
- Créer/Modifier/Supprimer utilisateurs
- Gérer 3 rôles (Admin, Prof, Étudiant)
- Réinitialiser mots de passe
- Activer/Désactiver comptes

**Gestion Inscriptions :**
- Inscrire manuellement étudiants
- Inscriptions en masse (CSV/JSON)
- Auto-inscription par niveau
- Voir inscriptions par étudiant/cours

**Gestion Évaluations :**
- Créer/Modifier/Supprimer évaluations
- Lier à cours et type d'évaluation
- Gérer dates, salles, horaires

**Gestion Notes :**
- Valider notes individuelles
- Lister notes en attente
- Valider en masse (jusqu'à 100 notes)
- Voir historique saisie/validation

**Gestion Planning :**
- Créer emplois du temps
- Détecter conflits (niveau, prof, salle)
- Voir disponibilités profs/cours
- Supprimer séances

**Dashboard :**
- Voir statistiques globales
- Étudiants par filière
- Dernière activité

### Professeur ✅

**Gestion Notes :**
- Saisir notes pour ses évaluations
- Voir statut (brouillon, soumise, validée)
- Modifier notes en brouillon

**Consultation :**
- Voir son emploi du temps personnel
- Voir ses cours et inscriptions
- Voir moyennes de ses étudiants

### Étudiant ✅

**Consultation :**
- Voir ses notes par évaluation
- Voir ses bulletins (sem. et annuels)
- Télécharger bulletins en PDF
- Voir ses cours inscrits

**Emploi du Temps :**
- Voir planning complet
- Voir planning de la semaine
- Voir planning du jour
- Voir résumé des cours
- Voir prochains cours

---

## 🛠️ Services Implémentés

### CacheService
Gestion centralisée du cache avec TTL adaptatif
```php
CacheService::remember($key, $ttl, $callback);
CacheService::forgetFilieres();  // Invalider cache
CacheService::SHORT_TTL   // 5 min
CacheService::DEFAULT_TTL // 1h
CacheService::LONG_TTL    // 24h
```

### LogService
Logging structuré des actions (audit trail)
```php
LogService::write($action, $description, $model, $oldData, $extraData);
```

### CalculAcademique
Calculs de moyennes et décisions
```php
CalculAcademique::calculerMoyenne($notes);
CalculAcademique::decisionBulletin($moyenne);
```

### EmploiDuTempsService ✅
Gestion planning des cours
```php
EmploiDuTempsService::trouverConflits();
EmploiDuTempsService::creerSeance();
```

### EmploiDuTempsEtudiantService ✅
Planning personnalisé pour étudiant
```php
EmploiDuTempsEtudiantService::planningComplet();
EmploiDuTempsEtudiantService::planningJour();
```

### PdfService
Génération PDF (bulletins, documents)
```php
PdfService::genererBulletin($bulletin);
```

---

## 🔐 Policies (Authorization)

### 5 Policies implémentées

**BulletinPolicy**
```php
- view() : Admin voir tous, Étudiant ses bulletins
- create() : Admin seulement
- delete() : Admin seulement
```

**EtudiantPolicy**
```php
- view() : Étudiant voir son profil, Admin tous
- update() : Étudiant son profil, Admin tous
```

**EmploiDuTempsPolicy**
```php
- manage() : Admin seulement
- viewProfesseur() : Prof voir son planning
- viewEtudiant() : Étudiant voir son planning
```

**EvaluationPolicy** ✅
```php
- creer() : Admin seulement
- modifier() : Admin seulement
- supprimer() : Admin seulement
```

**NotePolicy** ✅
```php
- saisirNotes() : Prof qui enseigne le cours
- voir() : Prof cours, Admin tous, Étudiant ses notes
- valider() : Admin seulement
- supprimer() : Admin seulement
```

---

**Dernière mise à jour :** 21 décembre 2025  
**Version :** 0.3.0 - Emplois du temps & Services  
**Statut :** En développement actif 🚧  
**Contributeurs :** Mahamane-Korobara