<?php
declare(strict_types=1);

// Démarrer la session une seule fois avec des paramètres sécurisés
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,      // Protection XSS
        'cookie_samesite' => 'Strict',  // Protection CSRF
        // 'cookie_secure' => true,     // Décommenter si HTTPS
        'use_strict_mode' => true,
        'use_only_cookies' => true,
    ]);
}