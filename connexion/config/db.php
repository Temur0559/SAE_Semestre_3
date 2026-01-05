<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();


function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $host = $_ENV['DB_HOST'];
            $port = $_ENV['DB_PORT'];
            $dbname = $_ENV['DB_NAME'];
            $user = $_ENV['DB_USER'];
            $password = $_ENV['DB_PASSWORD'];


            preg_match('/^(ep-[a-z0-9-]+)/', $host, $matches);
            $endpointId = $matches[1] ?? null;

            if (!$endpointId) {
                throw new RuntimeException("Impossible d'extraire l'endpoint ID de l'hôte: $host");
            }

            // Construction du DSN avec l'endpoint ID
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};options='--client_encoding=UTF8 endpoint={$endpointId}'";

            $pdo = new PDO(
                $dsn,
                $user,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            error_log("Erreur de connexion DB: " . $e->getMessage());
            throw new RuntimeException("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    return $pdo;
}