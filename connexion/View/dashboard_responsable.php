<?php
declare(strict_types=1);
session_start();

// On remonte d'un dossier pour atteindre config/base_path.php
require_once __DIR__ . '/../config/base_path.php';

if (!isset($_SESSION['identifiant'], $_SESSION['role']) || $_SESSION['role'] !== 'RESPONSABLE') {
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
    <title>Espace Responsable - Tableau de Bord</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/connexion/Style.css">
    <style>
        :root {
            --uphf-blue-dark: #004085;
            --uphf-blue-light: #007bff;
            --content-max-width: 1400px;
        }

        body {
            padding-top: 60px;
            margin: 0;
            background-color: #f4f7f6;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            font-family: sans-serif;
        }

        /* Header Fixe style ENT */
        .app-header-nav {
            position: fixed;
            top: 0; left: 0; width: 100%;
            display: flex; justify-content: center; align-items: center;
            background-color: var(--uphf-blue-dark);
            height: 60px;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        .header-inner-content {
            display: flex; align-items: center; width: 95%;
            max-width: var(--content-max-width);
            justify-content: space-between;
        }

        .header-logo { height: 30px; filter: brightness(0) invert(1); }

        .header-nav-links a {
            color: white; padding: 20px 15px;
            text-decoration: none; font-weight: bold;
        }

        .header-nav-links a.active-btn {
            background-color: var(--uphf-blue-light);
            border-bottom: 3px solid white;
        }

        .main-content-area {
            width: 95%; max-width: var(--content-max-width);
            margin: 20px auto; background: white;
            border: 1px solid #ddd; padding: 30px;
            box-sizing: border-box;
        }

        .action-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px; margin-top: 30px;
        }

        .action-card {
            background: #f9f9f9; border: 1px solid #ccc;
            padding: 30px; text-align: center; text-decoration: none; color: #333;
        }

        .action-card:hover { border-color: var(--uphf-blue-light); background: #fff; }

        .logout-btn { background: #dc3545; color: white; border: none; padding: 8px 15px; cursor: pointer; }
    </style>
</head>
<body>

<header class="app-header-nav">
    <div class="header-inner-content">
        <img src="<?= BASE_PATH ?>/connexion/UPHF_logo.svg.png" class="header-logo" alt="UPHF">

        <nav class="header-nav-links">
            <a href="<?= BASE_PATH ?>/connexion/View/dashboard_responsable.php" class="active-btn">Accueil</a>
            <a href="<?= BASE_PATH ?>/equipe_pedag/index.php">Gestion Absences</a>
            <a href="<?= BASE_PATH ?>/Statistiques/index.php">Statistiques</a>
        </nav>

        <div style="color:white;">
            <strong><?= $identifiant ?></strong>
            <form action="<?= BASE_PATH ?>/connexion/logout.php" method="POST" style="display:inline; margin-left:10px;">
                <button class="logout-btn">Déconnexion</button>
            </form>
        </div>
    </div>
</header>

<div class="main-content-area">
    <h1>Tableau de Bord Responsable</h1>
    <p>Sélectionnez une action ci-dessous :</p>

    <div class="action-grid">
        <a href="<?= BASE_PATH ?>/equipe_pedag/index.php" class="action-card">
            <h3>📂 Équipe Pédagogique</h3>
            <p>Gérer les justificatifs d'absences.</p>
        </a>

        <a href="<?= BASE_PATH ?>/Statistiques/index.php" class="action-card">
            <h3>📊 Statistiques</h3>
            <p>Visualiser les graphiques d'absences.</p>
        </a>
    </div>
</div>

</body>
</html>