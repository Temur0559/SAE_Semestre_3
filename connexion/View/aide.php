<?php
require_once __DIR__ . "/../Presenter/HelpPresenter.php";
$presenter = new HelpPresenter();
$message = $presenter->getHelpMessage();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aide — UPHF</title>
    <link rel="stylesheet" href="../Style.css">
</head>
<body>
<div class="auth-shell">
    <header class="brandbar"><img src="../UPHF_logo.svg.png" class="brandbar__logo" alt="UPHF"></header>

    <main class="card layout-1col">
        <h1 class="page-title">Aide</h1>
        <p class="page-sub"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="actions"><a class="btn-secondary" href="login.php">Retour</a></div>
    </main>
</div>
</body>
</html>
