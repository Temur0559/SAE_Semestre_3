<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/base_path.php';
if (!isset($_SESSION['identifiant'], $_SESSION['role']) || $_SESSION['role'] !== 'ENSEIGNANT') {
    header('Location: login.php'); exit;
}
$identifiant = htmlspecialchars($_SESSION['identifiant'], ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr"><head>
    <meta charset="utf-8"><title>Espace Enseignant</title>
    <link rel="stylesheet" href="../Style.css">
</head><body>
<div class="auth-shell">
    <header class="brandbar"><img src="../UPHF_logo.svg.png" class="brandbar__logo" alt="UPHF"></header>
    <main class="card layout-1col">
        <h1>Bienvenue, <?= $identifiant; ?></h1>
        <p>Vous êtes connecté en tant qu'<strong><?= $role; ?></strong>.</p>

        <a href="<?= BASE_PATH ?>/rattrapages/index.php" class="btn btn-primary" style="margin-bottom: 15px;">
            Liste des Étudiants à Rattraper (Évaluations)
        </a>
        <form method="post" action="../logout.php">
            <button class="btn btn-secondary" type="submit">Se déconnecter</button>
        </form>
    </main>
</div>
</body></html>