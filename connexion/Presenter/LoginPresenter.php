<?php
declare(strict_types=1);
require_once __DIR__ . '/../Model/UserModel.php';

final class LoginPresenter {
    public function handleLogin(string $email, string $password) {
        session_start();


        if (!isset($_POST['csrf'], $_SESSION['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
            header('Location: View/login.php?err=csrf'); exit;
        }
        if ($email === '' || $password === '') {
            header('Location: View/login.php?err=empty'); exit;
        }


        $user = UserModel::authenticate($email, $password);
        if (!$user) {
            $u = UserModel::findByEmail($email);
            header('Location: View/login.php?err=' . ($u ? 'badpass' : 'nouser')); exit;
        }

        // identifiant "prenom.nom" pour affichage
        $identifiant = $user['email'];
        if (false !== ($pos = strpos($identifiant, '@'))) {
            $identifiant = substr($identifiant, 0, $pos);
        }

        // Remplit les clés que les dashboards lisent
        $_SESSION['identifiant'] = $identifiant;
        $_SESSION['role']        = $user['role'];

        // copie complète si besoin ailleurs
        $_SESSION['user'] = [
            'id'     => (int)$user['id'],
            'email'  => $user['email'],
            'nom'    => $user['nom'],
            'prenom' => $user['prenom'],
            'role'   => $user['role'],
        ];

        // Redirection par rôle  tout dans connexion/View/
        switch ($user['role']) {
            case 'ETUDIANT':    header('Location: View/dashboard_etudiant.php');   break;
            case 'ENSEIGNANT':  header('Location: View/dashboard_enseignant.php'); break;
            case 'RESPONSABLE': header('Location: View/dashboard_responsable.php');break;
            case 'SECRETAIRE':  header('Location: View/dashboard_secretaire.php'); break;
            default:            header('Location: View/login.php');               break;
        }
        exit;
    }
}
