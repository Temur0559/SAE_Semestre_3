<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/base_path.php'; // Inclure BASE_PATH pour les liens

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Aide — UPHF</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../Style.css">
</head>
<body>
<div class="auth-shell">
    <header class="brandbar">
        <img src="../UPHF_logo.svg.png" class="brandbar__logo" alt="UPHF">
    </header>

    <main class="card layout-1col">
        <h1>Besoin d’aide pour justifier une absence ?</h1>
        <p>Voici la procédure, les délais et les règles à suivre :</p>

        <h2>Procédure de Justification (Délai légal)</h2>
        <ul>
            <li>La justification doit être soumise **dès que possible** via la page <a href="<?= BASE_PATH ?>/soum_justif/justification.php">Déclaration/Justification d'Absences</a>.</li>
            <li>Le **délai légal maximum est de 48 heures** après la fin de l'absence (ou après votre retour en cours si l'absence se prolonge).</li>
            <li>Passé ce délai, votre justification pourra être **refusée** (sauf autorisation expresse du Responsable Pédagogique).</li>
            <li>Vous recevrez une **notification par email** et sur la page <a href="<?= BASE_PATH ?>/notifications/index.php">Mes Notifications</a> pour les rappels de justification.</li>
        </ul>

        <h2>Conséquences en cas de non-justification</h2>
        <ul>
            <li>Toute absence **non justifiée** dans les délais entraînera l'application du règlement intérieur.</li>
            <li>En général, une absence non justifiée à une évaluation ou un contrôle peut entraîner la note de **0/20 (Abs)**.</li>
        </ul>

        <h2>Support et Ressources</h2>
        <ul>
            <li>Vérifiez que votre identifiant est bien de la forme <strong>prenom.nom</strong> ou votre adresse <strong>@uphf.fr</strong>.</li>
            <li>Vérifiez que votre mot de passe est correct (respectez majuscules et minuscules).</li>
            <li>Pour le règlement intérieur de votre composante (BUT Informatique, IUT, etc.), veuillez consulter le <a href="https://uphf.fr/reglement-interieur" target="_blank">Règlement Intérieur de l'UPHF</a>.</li>
            <li>Si le problème de connexion persiste, contactez le <strong>support informatique de l’UPHF</strong> :
                <br><a href="mailto:support@uphf.fr">support@uphf.fr</a>
            </li>
        </ul>
        <p><a class="btn btn-secondary" href="login.php">← Retour à la connexion</a></p>
    </main>
</div>
</body>
</html>