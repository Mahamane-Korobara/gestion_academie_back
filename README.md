# Système de Gestion Académique

## 📋 Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Technologies utilisées](#technologies-utilisées)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Architecture du projet](#architecture-du-projet)
- [Base de données](#base-de-données)
- [Authentification](#authentification)
- [API Endpoints](#api-endpoints)
- [Système de cache](#système-de-cache)
- [Tests](#tests)
- [Déploiement](#déploiement)

---

## 🎯 Vue d'ensemble

Système complet de gestion académique pour établissements d'enseignement supérieur avec 3 types d'utilisateurs :

- **Administrateur** : Gestion complète du système
- **Professeur** : Gestion des cours et notes
- **Étudiant** : Consultation des notes et bulletins

### Fonctionnalités principales

✅ Gestion des utilisateurs (Admin, Professeurs, Étudiants)  
✅ Gestion des filières et niveaux  
✅ Gestion des cours et inscriptions  
✅ Système d'authentification sécurisé avec Laravel Sanctum  
✅ Système de cache optimisé (Redis/File)  
✅ Logs d'activité complets  
✅ Notifications par email  
✅ API RESTful complète  

---

## 🛠️ Technologies utilisées

### Backend
- **Laravel 11** (PHP 8.2+)
- **MySQL 8.0+**
- **Laravel Sanctum** (Authentification API)
- **Redis** (Cache - optionnel)

### Frontend (prévu)
- **Nextjs**

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
│   ├── Enums/                      # Énumérations
│   │   ├── UserRole.php
│   │   ├── StudentStatus.php
│   │   ├── Sexe.php
│   │   ├── Semestre.php
│   │   ├── JourSemaine.php
│   │   ├── TypeSeance.php
│   │   ├── StatutNote.php
│   │   ├── StatutEvaluation.php
│   │   ├── DecisionBulletin.php
│   │   ├── TypeAnnonce.php
│   │   ├── PrioriteAnnonce.php
│   │   ├── TypeDocument.php
│   │   ├── StatutDocument.php
│   │   └── ActionLog.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── API/
│   │   │       ├── Auth/
│   │   │       │   └── AuthController.php
│   │   │       └── Admin/
│   │   │           ├── UserController.php
│   │   │           ├── FiliereController.php
│   │   │           ├── NiveauController.php
│   │   │           └── CoursController.php
│   │   │
│   │   ├── Middleware/
│   │   │   ├── CheckUserActive.php
│   │   │   ├── CheckPasswordChange.php
│   │   │   └── CheckRole.php
│   │   │
│   │   ├── Requests/
|   |   |   └── Auth/
|   │   │   │   ├── LoginRequest.php
|   │   │   │   ├── ChangePasswordRequest.php
|   │   │   │   ├── UpdateProfileRequest.php
│   │   │   └── Admin/
│   │   │       ├── CreateUserRequest.php
│   │   │       ├── CreateFiliereRequest.php
│   │   │       ├── UpdateFiliereRequest.php
│   │   │       ├── CreateNiveauRequest.php
│   │   │       ├── UpdateNiveauRequest.php
│   │   │       └── CreateCoursRequest.php
│   │   │
│   │   └── Resources/
│   │       └── Admin/
│   │           ├── FiliereResource.php
│   │           ├── NiveauResource.php
|   |           ├── UserResource.php
│   │           └── CoursResource.php
|   |           
│   │
│   ├── Models/                     # Modèles Eloquent
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Filiere.php
│   │   ├── Niveau.php
│   │   ├── AnneeAcademique.php
│   │   ├── Semestre.php
│   │   ├── Etudiant.php
│   │   ├── Professeur.php
│   │   ├── Cours.php
│   │   ├── Inscription.php
│   │   ├── Salle.php
│   │   ├── EmploiDuTemps.php
│   │   ├── TypeEvaluation.php
│   │   ├── Evaluation.php
│   │   ├── Note.php
│   │   ├── Bulletin.php
│   │   ├── Annonce.php
│   │   ├── Notification.php
│   │   ├── Message.php
│   │   ├── Document.php
│   │   └── LogActivite.php
│   │
│   └── Services/                   # Services métier
│       └── CacheService.php
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

## 🌐 API Endpoints

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
| GET | `/admin/cours` | Liste cours |
| POST | `/admin/cours` | Créer cours |
| GET | `/admin/cours/{id}` | Détails cours |
| PUT | `/admin/cours/{id}` | Modifier cours |
| DELETE | `/admin/cours/{id}` | Supprimer cours |

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
  '
```

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

## 📊 Fonctionnalités implémentées

### ✅ Authentification
- [x] Login avec email/password
- [x] Logout simple et multiple
- [x] Changement de mot de passe
- [x] Mise à jour profil
- [x] Gestion des sessions
- [x] Middleware de sécurité
- [x] Rate limiting (3 tentatives/minute)

### ✅ Gestion Admin
- [x] CRUD Utilisateurs (Admin, Professeur, Étudiant)
- [x] Réinitialisation mot de passe
- [x] Activation/Désactivation comptes
- [x] CRUD Filières
- [x] CRUD Niveaux
- [x] Création automatique niveaux (L1-L3, M1-M2)
- [x] CRUD Cours
- [x] Assignation professeurs aux cours

### ✅ Système de cache
- [x] Cache intelligent par entité
- [x] Invalidation automatique
- [x] Support Redis et File
- [x] TTL adaptatif

### ✅ Base de données
- [x] 22 tables complètes
- [x] Relations Eloquent
- [x] Seeders (Rôles, Admin, Types évaluations)
- [x] Migrations versionnées

### ✅ Sécurité
- [x] Authentification par tokens
- [x] Middleware de rôles
- [x] Validation des données
- [x] Logs d'activité
- [x] Protection CSRF

### 🚧 À développer

- [ ] Dashboard Admin avec statistiques
- [ ] Controllers Professeur (notes, emploi du temps)
- [ ] Controllers Étudiant (consultation)
- [ ] Gestion années académiques
- [ ] Gestion semestres
- [ ] Gestion emplois du temps
- [ ] Saisie et validation notes
- [ ] Génération bulletins
- [ ] Calcul moyennes
- [ ] Système d'annonces
- [ ] Notifications push
- [ ] Messagerie interne
- [ ] Export PDF/Excel
- [ ] Interface frontend React

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

## 🛡️ Sécurité

### Bonnes pratiques implémentées

✅ Authentification par tokens (Sanctum)  
✅ Validation stricte des données  
✅ Protection contre les injections SQL (Eloquent)  
✅ Hashage sécurisé des mots de passe (bcrypt)  
✅ Rate limiting sur login  
✅ Logs de toutes les actions  
✅ Middleware de vérification des rôles  
✅ Sessions sécurisées  

### À faire en production

- [ ] HTTPS obligatoire
- [ ] Configurer le firewall
- [ ] Backups automatiques
- [ ] Monitoring (Sentry, New Relic)
- [ ] Rate limiting global
- [ ] Protection DDoS

---

## 📝 Logs et debugging

### Logs disponibles

```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Logs d'activité (table logs_activite)
# Accessible via l'interface admin
```

### Debug mode

**Développement :**
```env
APP_DEBUG=true
APP_ENV=local
```

**Production :**
```env
APP_DEBUG=false
APP_ENV=production
```

---


## 🎉 Changelog

### Version 0.1.0 (Actuelle)

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

**À venir (v0.2.0) :**
- Dashboard administrateur
- Controllers professeur
- Controllers étudiant
- Gestion des notes
- Génération des bulletins

---

**Dernière mise à jour :** 23 novembre 2025  
**Version :** 0.1.0  
**Statut :** En développement actif 🚧