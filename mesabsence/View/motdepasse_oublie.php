<?php /* View/motdepasse_oublie.php */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mot de passe oublié — UPHF</title>
    <link rel="stylesheet" href="../Style.css">
</head>
<body>
<div class="auth-shell">
    <header class="brandbar"><img src="../UPHF_logo.svg.png" class="brandbar__logo" alt="UPHF"></header>

    <main class="card layout-1col">
        <h1 class="h1">Réinitialiser votre mot de passe</h1>
        <p class="sub">Indiquez votre identifiant institutionnel. Si le compte existe, vous recevrez les instructions de réinitialisation.</p>

        <form class="form" action="../traitement_mdp.php" method="POST" autocomplete="on">
            <div class="row">
                <label for="identifiant">Identifiant</label>
                <input class="input" id="identifiant" name="identifiant" type="text" placeholder="prenom.nom" required maxlength="100">
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Continuer</button>
                <a class="link" href="login.php">Retour à la connexion</a>
            </div>
        </form>
    </main>
</div>
</body>
</html>
