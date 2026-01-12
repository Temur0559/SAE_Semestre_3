<?php
declare(strict_types=1);

// Fonction permettant de vérifier si l'utilisateur est connecté pour consulter la page
function require_login() {
    if (empty($_SESSION['user'])) { header('Location: ../connexion/View/login_fr.php'); exit; }
}
// Fonction permettant de vérifier si un utilisateur a les permissions requises pour consulter la page
function require_role() {
    $roles = func_get_args();
    require_login();
    $role = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : '';
    if (!in_array($role, $roles, true)) { http_response_code(403); echo "Accès refusé."; exit; }
}
