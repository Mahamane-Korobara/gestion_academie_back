<?php 
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    // Ajoute ici tes deux URLs Vercel (le domaine personnalisé et l'url de secours)
    'allowed_origins' => [
        'http://localhost:3000',
        'https://gestion-academique.sahelstack.tech',
        'https://frontend-gestion-academique.vercel.app'
    ], 
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];