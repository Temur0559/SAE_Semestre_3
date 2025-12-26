<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/session.php';

// CORRECTION DU CHEMIN : dashboard_secretaire.php est dans connexion/View/
// On doit remonter d'un cran pour atteindre config/
require_once __DIR__ . '/../config/base_path.php';

if (!isset($_SESSION['identifiant'], $_SESSION['role']) || $_SESSION['role'] !== 'SECRETAIRE') {
    header('Location: login.php');
    exit;
}

$identifiant = htmlspecialchars($_SESSION['identifiant'], ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Secrétaire — UPHF</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/connexion/Style.css">
    <style>
        :root {
            --uphf-blue-dark: #004085;
            --uphf-blue-light: #007bff;
            --danger-color: #dc3545;
            --content-max-width: 1100px;
        }

        body {
            margin: 0;
            padding-top: 80px;
            background-color: #f4f7f6;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .app-header-nav {
            position: fixed;
            top: 0; left: 0; width: 100%;
            display: flex; justify-content: center; align-items: center;
            background-color: var(--uphf-blue-dark);
            height: 60px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 2000;
        }

        .header-inner-content {
            display: flex; align-items: center; width: 90%;
            max-width: var(--content-max-width);
            justify-content: space-between;
        }

        .header-logo { height: 30px; filter: brightness(0) invert(1); }

        .user-info-logout { display: flex; align-items: center; color: white; gap: 15px; }

        .logout-btn {
            background-color: var(--danger-color);
            color: white; border: none; padding: 8px 15px;
            cursor: pointer; font-weight: bold; border-radius: 4px;
        }

        .main-content-area {
            width: 90%;
            max-width: var(--content-max-width);
            background-color: white;
            border: 1px solid #e0e0e0;
            padding: 40px;
            box-sizing: border-box;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        h1 { color: var(--uphf-blue-dark); margin-top: 0; }

        .welcome-card {
            background: #eef6ff;
            border: 1px solid var(--uphf-blue-light);
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }

        .sync-section {
            background: #fff;
            border: 2px dashed #d0e0f0;
            padding: 30px;
            border-radius: 12px;
        }

        .btn-import {
            display: inline-block;
            background-color: var(--uphf-blue-light);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            font-weight: bold;
            border-radius: 6px;
            font-size: 1.1rem;
            transition: background 0.2s;
            margin-top: 15px;
        }

        .btn-import:hover { background-color: var(--uphf-blue-dark); }
    </style>
</head>
<body>

<header class="app-header-nav">
    <div class="header-inner-content">
        <img src="<?= BASE_PATH ?>/connexion/UPHF_logo.svg.png" class="header-logo" alt="UPHF">
        <div class="user-info-logout">
            <span><strong><?= $identifiant; ?></strong> (Secrétariat)</span>
            <form method="post" action="<?= BASE_PATH ?>/connexion/logout.php" style="display: inline-block; margin: 0;">
                <button class="logout-btn" type="submit">Se déconnecter</button>
            </form>
        </div>
    </div>
</header>

<div class="main-content-area">
    <div class="welcome-card">
        <h1>Bienvenue, <?= $identifiant; ?></h1>
        <p>Espace de travail : <strong><?= $role; ?></strong></p>
    </div>

    <div class="sync-section">
        <h2>Synchronisation VT (US11)</h2>
        <p>Mise à jour des absences et évaluations via import CSV.</p>

        <a href="<?= BASE_PATH ?>/vt/sae_vt.php" class="btn-import">
            📂 Importer les données VT (CSV)
        </a>
    </div>
</div>

</body>
</html>