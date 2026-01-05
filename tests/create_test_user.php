<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

require_once __DIR__ . '/../connexion/config/db.php';


try {
    $pdo = db();

    $email = 'test.etudiant@uphf.fr';
    $password = 'TestPassword123!';
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO utilisateur (identifiant, nom, prenom, email, mot_de_passe_hash, role, ine)
            VALUES (:id, :nom, :prenom, :email, :hash, 'ETUDIANT', 'TEST123456')
            ON CONFLICT (email) DO UPDATE SET mot_de_passe_hash = :hash2
            RETURNING id";

    $st = $pdo->prepare($sql);
    $st->execute([
        ':id' => 'test.etudiant',
        ':nom' => 'Test',
        ':prenom' => 'Etudiant',
        ':email' => $email,
        ':hash' => $hash,
        ':hash2' => $hash
    ]);

    $userId = $st->fetchColumn();

    echo "Utilisateur de test créé/mis à jour !\n";
    echo "   Email: $email\n";
    echo "   Password: $password\n";
    echo "   ID: $userId\n";

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}