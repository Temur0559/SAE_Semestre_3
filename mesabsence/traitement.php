<?php
require_once __DIR__ . "/Presenter/LoginPresenter.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifiant = $_POST['identifiant'] ?? "";
    $password    = $_POST['password'] ?? "";

    $presenter = new LoginPresenter();
    $presenter->handleLogin($identifiant, $password);
} else {
    header("Location: View/login.php");
    exit;
}
