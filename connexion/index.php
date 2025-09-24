<?php
require_once __DIR__ . "/Presenter/UserPresenter.php";
error_reporting(E_ALL);
ini_set('display_errors', '1');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifiant = isset($_POST['identifiant']) ? $_POST['identifiant'] : "";
    $password   = isset($_POST['password']) ? $_POST['password'] : "";

    $presenter = new UserPresenter();
    $presenter->handleLogin($identifiant, $password);
} else {
    header("Location: View/login.php");



    exit;
}
