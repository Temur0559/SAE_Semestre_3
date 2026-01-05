<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/session.php';
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$error = isset($_GET['err']) ? $_GET['err'] : null;
$ok    = isset($_GET['ok'])  ? $_GET['ok']  : null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login — UPHF</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../Style.css">
    <style>
        /* Language button */
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
    <!-- LANGUAGE BUTTON -->
    <a href="login_fr.php<?= $error ? '?err=' . urlencode($error) : '' ?><?= $ok ? '?ok=' . urlencode($ok) : '' ?>" class="lang-btn">🇫🇷 Français</a>

    <header class="brandbar">
        <img src="../UPHF_logo.svg.png" class="brandbar__logo" alt="UPHF">
    </header>
    <main class="card layout-2col">
        <section class="panel-left">
            <h1 class="h1">Login to UPHF Services</h1>
            <?php if ($error === 'bad' || $error === 'badpass'): ?>
                <div class="alert error">Invalid username or password.</div>
            <?php elseif ($error === 'nouser'): ?>
                <div class="alert error">No account found for this username.</div>
            <?php elseif ($error === 'csrf'): ?>
                <div class="alert error">Session expired, please try again.</div>
            <?php elseif ($error === 'empty'): ?>
                <div class="alert error">Please fill in all fields.</div>
            <?php elseif ($ok === 'pwdchanged'): ?>
                <div class="alert success">Password updated successfully.</div>
            <?php endif; ?>
            <form class="form" action="../index.php" method="POST" autocomplete="on">
                <div class="row">
                    <label for="identifiant">Username</label>
                    <input class="input" id="identifiant" name="identifiant" type="text" placeholder="firstname.lastname or email" required>
                </div>
                <div class="row password">
                    <label for="password">Password</label>
                    <input class="input" id="password" name="password" type="password" placeholder="Your password" required>
                </div>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-primary">Sign in</button>
            </form>
        </section>
        <aside class="panel-right">
            <nav class="side-links">
                <a href="aide.php">Need help?</a>
            </nav>
        </aside>
    </main>
</div>

</body>
</html>