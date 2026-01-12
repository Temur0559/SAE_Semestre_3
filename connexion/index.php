<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/Presenter/LoginPresenter.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = $_POST['identifiant'] ?? '';
    $pass = $_POST['password'] ?? '';

    // si pas de @ on rajoute le mail
    if ($mail !== '' && strpos($mail, '@') === false) {
        $mail .= '@uphf.fr';
    }

    $p = new LoginPresenter();
    $p->handleLogin($mail, $pass);
} else {
    header('Location: View/login_fr.php');
}