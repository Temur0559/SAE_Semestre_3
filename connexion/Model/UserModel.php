<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

final class UserModel {

    /** @return array|null */
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

    /** * @return array|null
     * CORRECTION US1: Implémentation finale sécurisée avec password_verify().
     */
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