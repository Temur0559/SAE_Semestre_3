<?php
require_once __DIR__ . "/Presenter/RegisterPresenter.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifiant = $_POST['identifiant'] ?? "";
    $password    = $_POST['password'] ?? "";

    $presenter = new RegisterPresenter();
    $presenter->handleRegister($identifiant, $password);
} else {
    header("Location: View/premiere_connexion.php");
    exit;
}
