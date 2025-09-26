<?php /* View/premiere_connexion.php */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Première connexion — UPHF</title>
    <link rel="stylesheet" href="../Style.css">
</head>
<body>
<div class="auth-shell">
    <header class="brandbar"><img src="../UPHF_logo.svg.png" class="brandbar__logo" alt="UPHF"></header>

    <main class="card layout-1col">
        <h1 class="h1">Activer votre compte</h1>
        <p class="sub">Créez un mot de passe pour finaliser l’activation de votre accès.</p>

        <form class="form" action="../traitement_premiere.php" method="POST" autocomplete="off">
            <div class="row">
                <label for="identifiant">Identifiant</label>
                <input class="input" id="identifiant" name="identifiant" type="text" placeholder="prenom.nom" required maxlength="100">
            </div>

            <div class="row password">
                <label for="pwd1">Nouveau mot de passe</label>
                <input class="input" id="pwd1" name="password" type="password"
                       placeholder="Au moins 8 caractères"
                       minlength="8" maxlength="200"
                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d).{8,}$"
                       title="Au moins 8 caractères dont une majuscule, une minuscule et un chiffre."
                       required>
                <button class="toggle" type="button" aria-label="Afficher/Masquer le mot de passe">

                    <svg xmlns="http://www.w3.org/2000/svg" class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>

                    <svg xmlns="http://www.w3.org/2000/svg" class="icon-eye icon-eye--off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-3.24 4.49"/>
                        <path d="M1 1l22 22"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>

            <div class="row">
                <label for="pwd2">Confirmer le mot de passe</label>
                <input class="input" id="pwd2" name="password_confirm" type="password" required maxlength="200">
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Activer mon compte</button>
                <a class="link" href="login.php">Annuler</a>
            </div>

            <div class="note">
                Exigences : minimum 8 caractères avec au moins une majuscule, une minuscule et un chiffre.
            </div>
        </form>
    </main>
</div>

<script>

    (function(){
        var btn = document.querySelector('.toggle');
        var p1 = document.getElementById('pwd1');
        var p2 = document.getElementById('pwd2');
        if (btn && p1){
            btn.addEventListener('click', function(){
                var show = p1.type === 'password';
                p1.type = show ? 'text' : 'password';
                btn.classList.toggle('is-on', show);
                btn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            });
        }
        if (p1 && p2){
            var check = function(){
                p2.setCustomValidity(p2.value === p1.value ? '' : 'Les mots de passe ne correspondent pas.');
            };
            p1.addEventListener('input', check);
            p2.addEventListener('input', check);
        }
    })();
</script>
</body>
</html>
