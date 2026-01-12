<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

// Permet de récupérer des informations relatives aux comptes utilisateurs
final class UserModel {

    // Récupérer les informations d'un compte utilisateur grace à son email
    public static function findByEmail(string $email) {

        $sql = "SELECT
                    id, nom, prenom, email,
                    mot_de_passe_hash, 
                    role
                FROM \"utilisateur\"
                WHERE email = :email";
        $st = db()->prepare($sql);

        $st->setFetchMode(PDO::FETCH_ASSOC);
        $st->execute([':email' => $email]);
        $u = $st->fetch();
        return $u ?: null;
    }

    // Prend en paramètre un email et un mot de passe, renvoie les informations de
    // l'utilisateur si ils sont valides
    public static function authenticate(string $email, string $password) {
        $user = self::findByEmail($email);
        if (!$user) return null;

        $stored_password_hash = '';
        if (isset($user['mot_de_passe_hash']) && $user['mot_de_passe_hash'] !== null) {
            $stored_password_hash = (string)$user['mot_de_passe_hash'];
        }

        if ($stored_password_hash === '') return null;

        // VÉRIFICATION SÉCURISÉE: Utilise le hachage stocké.
        if (password_verify($password, $stored_password_hash)) {
            return $user;
        }

        // Le code de rétrocompatibilité a été supprimé.
        return null;
    }
}