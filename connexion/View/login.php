<?php /* View/login.php */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — UPHF</title>
    <link rel="stylesheet" href="../Style.css">
</head>
<body>
<div class="auth-shell">
    <header class="brandbar"><img src="../UPHF_logo.svg.png" class="brandbar__logo" alt="Université Polytechnique Hauts-de-France"></header>

    <main class="card layout-2col">
        <section class="panel-left">
            <h1 class="h1">Connexion aux services UPHF</h1>
            <p class="sub">Saisissez vos identifiants institutionnels pour accéder aux applications réservées.</p>

            <form class="form" action="../traitement.php" method="POST" autocomplete="on">
                <div class="row">
                    <label for="identifiant">Identifiant</label>
                    <input class="input" id="identifiant" name="identifiant" type="text" placeholder="prenom.nom" required maxlength="100">
                </div>

                <div class="row password">
                    <label for="password">Mot de passe</label>
                    <input class="input" id="password" name="password" type="password" placeholder="Votre mot de passe" required maxlength="200">
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

                <button type="submit" class="btn btn-primary">S’identifier</button>
            </form>

            <div class="note">
                • Pour votre sécurité, déconnectez-vous et fermez le navigateur après utilisation.<br>
                • Vos identifiants sont personnels et ne doivent jamais être partagés.
            </div>
        </section>

        <aside class="panel-right">
            <div class="watermark"><img src="../UPHF_logo.svg.png" alt=""></div>
            <nav class="side-links">
                <a href="motdepasse_oublie.php">Mot de passe oublié ?</a>
                <a href="premiere_connexion.php">Première connexion ?</a>
                <a href="aide.php">Besoin d’aide ?</a>
            </nav>
        </aside>
    </main>
</div>

<script>

    (function(){
        var btn = document.querySelector('.toggle');
        var input = document.getElementById('password');
        if (!btn || !input) return;
        btn.addEventListener('click', function(){
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.classList.toggle('is-on', show); // affiche l'œil barré quand c'est visible
            btn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        });
    })();
</script>
</body>
</html>
