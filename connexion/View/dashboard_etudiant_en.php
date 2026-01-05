<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/base_path.php';

if (!isset($_SESSION['identifiant'], $_SESSION['role']) || $_SESSION['role'] !== 'ETUDIANT') {
    header('Location: login_en.php'); exit;
}
$identifiant = htmlspecialchars($_SESSION['identifiant'], ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Area - Dashboard</title>
    <link rel="stylesheet" href="../Style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/mesabsence/Style.css">
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
            gap: 15px;
            color: white;
        }

        /* Language button */
        .lang-switch-btn {
            background-color: white;
            color: var(--uphf-blue-dark);
            padding: 8px 15px;
            border-radius: 0;
            text-decoration: none;
            font-weight: bold;
            border: 2px solid white;
            transition: all 0.2s;
        }

        .lang-switch-btn:hover {
            background-color: var(--uphf-blue-light);
            color: white;
        }

        .user-info-logout button.btn {
            background-color: #dc3545;
            border-radius: 0;
            padding: 8px 15px;
            color: white;
        }

        .main-content-area {
            width: 90%;
            max-width: var(--content-max-width);
            margin: 20px auto;
            padding: 0;
            flex-grow: 1;
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: 0;
            box-sizing: border-box;
        }
        .card {
            border-radius: 0;
            border: none;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
        }

    </style>
</head>
<body>

<header class="app-header-nav">
    <div class="header-inner-content">
        <div class="header-logo-container">
            <img src="../UPHF_logo.svg.png" class="header-logo" alt="UPHF">
        </div>

        <div class="header-nav-links">
            <a href="<?= BASE_PATH ?>/connexion/View/dashboard_etudiant_en.php" class="btn active-btn">Student Home</a>
            <a href="<?= BASE_PATH ?>/mesabsence/index.php" class="btn">View My Absences</a>
            <a href="<?= BASE_PATH ?>/soum_justif/justification.php" class="btn">Justify an Absence</a>
        </div>

        <div class="user-info-logout">
            <strong><?= $identifiant; ?> (<?= $role; ?>)</strong>

            <!-- LANGUAGE BUTTON -->
            <a href="dashboard_etudiant_fr.php" class="lang-switch-btn">🇫🇷 Français</a>

            <form method="post" action="../logout.php" style="display: inline-block; margin: 0;">
                <button class="btn" type="submit">Logout</button>
            </form>
        </div>
    </div>
</header>

<div class="main-content-area">
    <main class="card layout-1col">
        <h1>Student Dashboard</h1>
        <p>Welcome <?= $identifiant; ?>. Use the navigation bar at the top to access different sections of your space.</p>

        <div class="nav-main" style="border-radius: 0; border: 1px solid #e0e0e0; background: #f4f7f6; padding: 20px; margin-top: 20px; display: flex; gap: 15px;">
            <h3>My quick actions:</h3>
            <a href="<?= BASE_PATH ?>/mesabsence/index.php" class="btn" style="background-color: #007bff; color: white; border-radius: 0;">View My Absences</a>
            <a href="<?= BASE_PATH ?>/soum_justif/justification.php" class="btn" style="background-color: #007bff; color: white; border-radius: 0;">Justify an Absence</a>
        </div>

        <div class="card-content" style="margin-top: 30px; border-top: 1px solid #e0e0e0; padding-top: 20px;">
            <h2>General Information</h2>
            <p>Here you can add widgets or summary information.</p>
        </div>
    </main>
</div>

</body>
</html>
```

---

## **✅ CE QUI A ÉTÉ AJOUTÉ**

1. **CSS pour le bouton** (ligne 58-69) :
- `.lang-switch-btn` : bouton blanc avec bordure
- Survol : devient bleu UPHF
- S'intègre parfaitement avec le design existant

2. **Bouton dans le header** (ligne 107) :
- Placé entre le nom d'utilisateur et le bouton de déconnexion
- Utilise `gap: 15px` pour l'espacement
- Format : `🇬🇧 English` / `🇫🇷 Français`

3. **Garde tout le reste identique** :
- Même CSS UPHF
- Même structure
- Même navigation
- Même style bleu foncé

---

## **📸 RÉSULTAT VISUEL**

Le bouton apparaît dans le header :
```
[Logo UPHF] [Nav links...]  [Jean.Dupont (ETUDIANT)] [🇬🇧 English] [Se déconnecter]