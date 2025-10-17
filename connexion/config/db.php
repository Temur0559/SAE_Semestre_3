<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = 'iutinfo-sgbd.uphf.fr';
    $port = '5432';
    $db   = 'iutinfo476';   
    $user = 'iutinfo476';
    $pass = 'Jnne4A/f';

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;options='--client_encoding=UTF8'";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
    ]);
    return $pdo;
}
