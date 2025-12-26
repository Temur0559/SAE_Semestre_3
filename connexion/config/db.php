<?php
declare(strict_types=1);

// Charger les variables d'environnement
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
} catch (\Exception $e) {
    throw new \RuntimeException("Impossible de charger le fichier .env : " . $e->getMessage());
}

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    // Récupération des variables d'environnement
    $host = $_ENV['DB_HOST'] ?? throw new \RuntimeException('DB_HOST manquant dans .env');
    $port = $_ENV['DB_PORT'] ?? throw new \RuntimeException('DB_PORT manquant dans .env');
    $db   = $_ENV['DB_NAME'] ?? throw new \RuntimeException('DB_NAME manquant dans .env');
    $user = $_ENV['DB_USER'] ?? throw new \RuntimeException('DB_USER manquant dans .env');
    $pass = $_ENV['DB_PASSWORD'] ?? throw new \RuntimeException('DB_PASSWORD manquant dans .env');

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require;options='endpoint=ep-sweet-butterfly-agv0uvto'";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        $message = "Erreur de connexion à la base de données: " . $e->getMessage();
        // Masquer le mot de passe dans les logs
        $message = str_replace($pass, '***PASSWORD HIDDEN***', $message);
        throw new \RuntimeException($message, 0, $e);
    }
}