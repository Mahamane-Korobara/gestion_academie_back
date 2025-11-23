# Guide de création du projet Laravel - Système de Gestion Académique

## Prérequis

Assurez-vous d'avoir installé :
- **PHP 8.2+** (vérifiez avec `php -v`)
- **Composer** (vérifiez avec `composer -V`)
- **MySQL 8.0+**
- **Node.js 18+** (pour le frontend plus tard)

## Étape 1 : Créer le projet Laravel

```bash
# Créer le projet Laravel
composer create-project laravel/laravel gestion-academique

# Entrer dans le dossier
cd gestion-academique
```

## Étape 2 : Configuration de la base de données

### Créer la base de données MySQL

```sql
-- Connectez-vous à MySQL
mysql -u root -p

-- Créer la base de données
CREATE DATABASE gestion_academique CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Créer un utilisateur dédié (optionnel mais recommandé)
CREATE USER 'academique_user'@'localhost' IDENTIFIED BY 'votre_mot_de_passe';
GRANT ALL PRIVILEGES ON gestion_academique.* TO 'academique_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Configurer le fichier `.env`

```bash
# Ouvrir le fichier .env et modifier ces lignes :

APP_NAME="Gestion Académique"
APP_ENV=local
APP_KEY=base64:... # Sera généré automatiquement
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_academique
DB_USERNAME=root  # ou academique_user
DB_PASSWORD=      # votre mot de passe

# Configuration du fuseau horaire
APP_TIMEZONE=Africa/Algiers
```

## Étape 3 : Générer la clé d'application

```bash
php artisan key:generate
```

## Étape 4 : Installer Laravel Sanctum (pour l'authentification API)

```bash
# Sanctum est déjà inclus dans Laravel 11, mais configurons-le
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Exécuter les migrations de Sanctum
php artisan migrate
```

## Étape 5 : Structure des dossiers personnalisée

```bash
# Créer les dossiers pour l'organisation du code

# Services (logique métier)
mkdir -p app/Services

# Enums (statuts, rôles, etc.)
mkdir -p app/Enums

# Policies (permissions)
mkdir -p app/Policies

# Traits réutilisables
mkdir -p app/Traits

# Observers (événements de modèles)
mkdir -p app/Observers

# Requests (validation)
mkdir -p app/Http/Requests

# Resources (transformation de données API)
mkdir -p app/Http/Resources

# Controllers API organisés
mkdir -p app/Http/Controllers/API/Admin
mkdir -p app/Http/Controllers/API/Professor
mkdir -p app/Http/Controllers/API/Student
mkdir -p app/Http/Controllers/API/Auth
```

## Étape 6 : Installer les dépendances utiles

```bash
# Laravel Debugbar (développement)
composer require barryvdh/laravel-debugbar --dev

# Laravel IDE Helper (autocomplétion)
composer require --dev barryvdh/laravel-ide-helper

# Spatie Laravel Permission (gestion avancée des rôles - optionnel)
composer require spatie/laravel-permission

# Laravel Excel (export de données)
composer require maatwebsite/excel

# Génération de PDF
composer require barryvdh/laravel-dompdf
```

## Étape 7 : Configuration CORS (pour le frontend React)

Le fichier `config/cors.php` est déjà présent. Modifiez-le si nécessaire :

```php
// config/cors.php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:5173'], // Vite dev server
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

## Étape 8 : Configurer les routes API

Modifier `bootstrap/app.php` pour définir le préfixe API :

```php
// bootstrap/app.php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

## Étape 9 : Vérifier l'installation

```bash
# Tester la connexion à la base de données
php artisan migrate:status

# Lancer le serveur de développement
php artisan serve

# Dans un autre terminal, surveiller les logs
php artisan tinker
```

## Étape 10 : Structure finale du projet

```
gestion-academique/
├── app/
│   ├── Enums/              # Énumérations (UserRole, StudentStatus, etc.)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── API/
│   │   │       ├── Admin/
│   │   │       ├── Professor/
│   │   │       ├── Student/
│   │   │       └── Auth/
│   │   ├── Requests/       # Validation des requêtes
│   │   └── Resources/      # Transformation JSON
│   ├── Models/             # Modèles Eloquent
│   ├── Observers/          # Événements de modèles
│   ├── Policies/           # Autorisations
│   ├── Services/           # Logique métier
│   └── Traits/             # Code réutilisable
├── database/
│   ├── migrations/         # Migrations de la base de données
│   ├── seeders/            # Données de test
│   └── factories/          # Factory pour les tests
├── routes/
│   └── api.php             # Routes API
└── tests/                  # Tests unitaires et fonctionnels
```
Les Enums permettent de définir des valeurs constantes typées

## Prochaines étapes

✅ **Projet Laravel créé et configuré**

Maintenant, nous allons :
1. 📊 Créer le schéma de base de données (migrations)
2. 🎨 Créer les modèles Eloquent avec relations
3. 🔐 Mettre en place l'authentification multi-rôles
4. 🚀 Développer les API endpoints

**Le projet est prêt !** Quelle est la prochaine étape que vous souhaitez aborder ?

## Commandes utiles

```bash
# Créer une migration
php artisan make:migration create_table_name

# Créer un modèle avec migration, factory, seeder et controller
php artisan make:model ModelName -mfsc

# Créer un controller API
php artisan make:controller API/ControllerName --api

# Créer une Request
php artisan make:request RequestName

# Créer une Policy
php artisan make:policy PolicyName

# Exécuter les migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Vider et réexécuter toutes les migrations
php artisan migrate:fresh

# Exécuter les seeders
php artisan db:seed

# Vider le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```