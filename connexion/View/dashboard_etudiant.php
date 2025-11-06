<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/base_path.php';

if (!isset($_SESSION['identifiant'], $_SESSION['role']) || $_SESSION['role'] !== 'ETUDIANT') {
    header('Location: login.php'); exit;
}
$identifiant = htmlspecialchars($_SESSION['identifiant'], ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr"><head>
    <meta charset="utf-8"><title>Espace Étudiant</title>
    <link rel="stylesheet" href="../Style.css">
</head><body>
<div class="auth-shell">
    <header class="brandbar"><img src="../UPHF_logo.svg.png" class="brandbar__logo" alt="UPHF"></header>
    <main class="card layout-1col">
        <h1>Bienvenue, <?= $identifiant; ?></h1>
        <p>Vous êtes connecté en tant que <strong><?= $role; ?></strong>.</p>

        <a href="<?= BASE_PATH ?>/mesabsence/index.php" class="btn btn-primary" style="margin-right: 15px; margin-bottom: 10px;">
            Consulter Mes Absences
        </a>

        <a href="<?= BASE_PATH ?>/soum_justif/justification.php" class="btn btn-primary" style="margin-right: 15px; margin-bottom: 10px;">
            Justifier une Absence
        </a>

        <form method="post" action="../logout.php" style="display: inline-block;">
            <button class="btn btn-secondary" type="submit">Se déconnecter</button>
        </form>
    </main>
</div>
</body></html>