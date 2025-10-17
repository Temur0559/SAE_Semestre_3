<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

final class UserModel {

    /** @return array|null */
    public static function findByEmail(string $email) {
        // On alias la colonne, ET on récupère aussi le nom brut au cas où
        $sql = "SELECT 
                    id, nom, prenom, email,
                    motdepassehash AS motDePasseHash,
                    motdepassehash,
                    role
                FROM utilisateur
                WHERE email = :email";
        $st = db()->prepare($sql);
        $st->execute([':email' => $email]);
        $u = $st->fetch();
        return $u ?: null;
    }

    /** @return array|null */
    public static function authenticate(string $email, string $password) {
        $user = self::findByEmail($email);
        if (!$user) return null;

        // Tolérance : lit l’alias si présent, sinon le nom brut
        $stored = '';
        if (isset($user['motDePasseHash']) && $user['motDePasseHash'] !== null) {
            $stored = (string)$user['motDePasseHash'];
        } elseif (isset($user['motdepassehash']) && $user['motdepassehash'] !== null) {
            $stored = (string)$user['motdepassehash'];
        }
        if ($stored === '') return null;

        // Détecte bcrypt ($2a/$2b/$2y$..) sinon compare en clair
        $isBcrypt = (bool)preg_match('/^\$2[aby]\$\d{2}\$/', $stored);
        if ($isBcrypt) {
            if (!password_verify($password, $stored)) return null;
        } else {
            if (!hash_equals($stored, $password)) return null;
        }
        return $user;
    }
}
