<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/base_path.php';

if (!isset($_SESSION['user']['id'], $_SESSION['user']['role']) || ($_SESSION['user']['role'] ?? '') !== 'ENSEIGNANT') {
    header('Location: ' . BASE_PATH . '/connexion/View/login.php');
    exit;
}

$user = $_SESSION['user'];

$prenom = $user['prenom'] ?? 'Cher';
$nom = $user['nom'] ?? 'Enseignant';

$url_rattrapages = BASE_PATH . '/rattrapages/index.php';
$url_mes_absences = BASE_PATH . '/mesabsence/index.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Espace Enseignant - Tableau de Bord</title>
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
            overflow-x: hidden;
            font-family: 'Arial', sans-serif;
        }

        .app-header-nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--uphf-blue-dark);
            height: 60px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .header-inner-content {
            display: flex;
            align-items: center;
            width: 90%;
            max-width: var(--content-max-width);
            justify-content: space-between;
        }

        .header-logo {
            height: 30px;
            margin-right: 20px;
            filter: brightness(0) invert(1);
        }

        .header-nav-links a.btn {
            background-color: transparent;
            border: none;
            color: white;
            padding: 10px 15px;
            margin-right: 5px;
            border-radius: 0;
            font-weight: bold;
            text-decoration: none;
        }
        .header-nav-links a.btn.active-btn {
            background-color: var(--uphf-blue-light);
            border-bottom: 3px solid white;
        }

        .user-info-logout {
            margin-left: auto;
            display: flex;
            align-items: center;
            color: white;
        }
        .user-info-logout button.btn {
            background-color: #dc3545;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            color: white;
            cursor: pointer;
        }


        .main-content-area {
            width: 90%;
            max-width: var(--content-max-width);
            margin: 20px auto;
            padding: 0;
            flex-grow: 1;
            box-sizing: border-box;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .dashboard-card {
            background-color: #e0f7fa;
            border: 1px solid #b2dfdb;
            border-radius: 10px;
            padding: 30px 20px;
            text-align: center;
            text-decoration: none;
            color: #004d40;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background-color: #c0f4f4;
        }
        .card-icon {
            font-size: 3.5em;
            margin-bottom: 10px;
            display: block;
        }
        main.card {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<header class="app-header-nav">
    <div class="header-inner-content">
        <div class="header-logo-container">
            <img src="<?= BASE_PATH ?>/connexion/UPHF_logo.svg.png" class="header-logo" alt="UPHF">
        </div>

        <div class="header-nav-links">
            <a href="<?= BASE_PATH ?>/connexion/View/dashboard_enseignant.php" class="btn active-btn">Accueil Enseignant</a>
            <a href="<?= $url_rattrapages ?>" class="btn">Gérer les Rattrapages</a>
        </div>

        <div class="user-info-logout">
            <strong><?= htmlspecialchars($prenom) . ' ' . htmlspecialchars($nom) ?> (<?= htmlspecialchars($user['role'] ?? 'N/A') ?>)</strong>
            <form method="post" action="<?= BASE_PATH ?>/connexion/logout.php" style="display: inline-block; margin-left: 15px;">
                <button class="btn" type="submit">Se déconnecter</button>
            </form>
        </div>
    </div>
</header>
<div class="main-content-area">
    <main class="card layout-1col">
        <h1>Tableau de Bord Enseignant</h1>
        <p>Bienvenue <?= htmlspecialchars($prenom) . ' ' . htmlspecialchars($nom); ?>. Gérez les rattrapages pour vos cours.</p>

        <div class="card-grid">

            <a href="<?= $url_rattrapages ?>" class="dashboard-card">
                <span class="card-icon">📚</span>
                Gérer les Rattrapages (Mes Cours)
            </a>
        </div>
    </main>
</div>
</body>
</html>