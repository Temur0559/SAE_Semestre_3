<?php
declare(strict_types=1);
session_start();

function require_login() {
    if (empty($_SESSION['user'])) { header('Location: ../connexion/View/login.php'); exit; }
}
function require_role() {
    $roles = func_get_args();
    require_login();
    $role = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : '';
    if (!in_array($role, $roles, true)) { http_response_code(403); echo "Accès refusé."; exit; }
}
