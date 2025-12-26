<?php
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
    header('Location: View/login.php');
}