<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/session.php';
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$error = isset($_GET['err']) ? $_GET['err'] : null;
$ok    = isset($_GET['ok'])  ? $_GET['ok']  : null;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Connexion — UPHF</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../Style.css">
    <style>
        /* Bouton langue */
        .lang-btn {
            position: absolute;
            top: 30px;
            right: 30px;
            padding: 10px 20px;
            background: white;
            color: #004085;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: 2px solid #004085;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.2s;
            z-index: 100;
        }
        .lang-btn:hover {
            background: #004085;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .auth-shell {
            position: relative;
        }
    </style>
</head>
<body>

<div class="auth-shell">
    <!-- BOUTON LANGUE -->
    <a href="login_en.php<?= $error ? '?err=' . urlencode($error) : '' ?><?= $ok ? '?ok=' . urlencode($ok) : '' ?>" class="lang-btn">🇬🇧 English</a>

    <header class="brandbar">
        <img src="../UPHF_logo.svg.png" class="brandbar__logo" alt="UPHF">
    </header>
    <main class="card layout-2col">
        <section class="panel-left">
            <h1 class="h1">Connexion aux services UPHF</h1>
            <?php if ($error === 'bad' || $error === 'badpass'): ?>
                <div class="alert error">Identifiant ou mot de passe invalide.</div>
            <?php elseif ($error === 'nouser'): ?>
                <div class="alert error">Aucun compte trouvé pour cet identifiant.</div>
            <?php elseif ($error === 'csrf'): ?>
                <div class="alert error">Session expirée, réessaie.</div>
            <?php elseif ($error === 'empty'): ?>
                <div class="alert error">Merci de remplir tous les champs.</div>
            <?php elseif ($ok === 'pwdchanged'): ?>
                <div class="alert success">Mot de passe mis à jour.</div>
            <?php endif; ?>
            <form class="form" action="../index.php" method="POST" autocomplete="on">
                <div class="row">
                    <label for="identifiant">Identifiant</label>
                    <input class="input" id="identifiant" name="identifiant" type="text" placeholder="prenom.nom ou email" required>
                </div>
                <div class="row password">
                    <label for="password">Mot de passe</label>
                    <input class="input" id="password" name="password" type="password" placeholder="Votre mot de passe" required>
                </div>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-primary">S'identifier</button>
            </form>
        </section>
        <aside class="panel-right">
            <nav class="side-links">
                <a href="aide.php">Besoin d'aide ?</a>
            </nav>
        </aside>
    </main>
</div>

</body>
</html>