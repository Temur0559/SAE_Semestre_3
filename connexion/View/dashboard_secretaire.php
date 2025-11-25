<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/base_path.php'; // Inclure BASE_PATH
if (!isset($_SESSION['identifiant'], $_SESSION['role']) || $_SESSION['role'] !== 'SECRETAIRE') {
    header('Location: login.php'); exit;
}
$identifiant = htmlspecialchars($_SESSION['identifiant'], ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr"><head>
    <meta charset="utf-8"><title>Espace Secrétaire</title>
    <link rel="stylesheet" href="../Style.css">
</head><body>
<div class="auth-shell">
    <header class="brandbar"><img src="../UPHF_logo.svg.png" class="brandbar__logo" alt="UPHF"></header>
    <main class="card layout-1col">
        <h1>Bienvenue, <?= $identifiant; ?></h1>
        <p>Vous êtes connecté en tant que <strong><?= $role; ?></strong>.</p>

        <h2>Synchronisation VT (US11)</h2>
        <p>Lancez l'outil d'importation pour mettre à jour les absences et les évaluations à partir d'un fichier CSV.</p>

        <a href="<?= BASE_PATH ?>/vt/sae_vt.php" class="btn btn-primary" style="margin-bottom: 20px;">
            Importer les données VT (CSV)
        </a>

        <form method="post" action="../logout.php">
            <button class="btn btn-secondary" type="submit">Se déconnecter</button>
        </form>
    </main>
</div>
</body></html>