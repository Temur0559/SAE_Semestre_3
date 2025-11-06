<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;


    $host = 'ep-sweet-butterfly-agv0uvto-pooler.c-2.eu-central-1.aws.neon.tech';
    $port = '5432';
    $db   = 'neondb';
    $user = 'neondb_owner';
    $pass = 'npg_eAnKzSvo48lf';


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

        $message = str_replace($pass, '***PASSWORD HIDDEN***', $message);
        throw new \RuntimeException($message, 0, $e);
    }
}