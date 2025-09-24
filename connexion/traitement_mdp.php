<?php
require_once __DIR__ . "/Presenter/PasswordPresenter.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifiant = isset($_POST['identifiant']) ? $_POST['identifiant'] : "";

    $presenter = new PasswordPresenter();
    $presenter->handlePasswordReset($identifiant);
} else {
    header("Location: View/mdp_oublie.php");
    exit;
}
