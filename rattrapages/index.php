<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_once __DIR__ . '/../connexion/config/base_path.php';
require_role('ENSEIGNANT');

require_once __DIR__ . '/Model/RattrapageModel.php';

$rattrapages = \RattrapageModel::getStudentsForRattrapage();
?>
<!doctype html>
<html lang="fr"><head>
    <meta charset="utf-8"><title>Étudiants à rattraper</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/connexion/Style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/mesabsence/Style.css">
</head><body>
<div class="auth-shell">
    <header class="brandbar"><img src="<?= BASE_PATH ?>/connexion/UPHF_logo.svg.png" class="brandbar__logo" alt="UPHF"></header>
    <main class="card layout-1col">
        <h1>Liste des Évaluations à Rattraper</h1>
        <p>Affichage des étudiants dont l'absence à une évaluation a été officiellement excusée.</p>

        <?php if (empty($rattrapages)): ?>
            <div class="alert success" style="margin-top: 20px;">
                🎉 Aucun étudiant n'est actuellement en attente de rattrapage pour une absence excusée à une évaluation.
            </div>
        <?php else: ?>
            <div class="table-wrap" style="margin-top: 20px;">
                <table class="abs-table">
                    <thead>
                    <tr>
                        <th>Date Séance</th>
                        <th>Enseignement (Évaluation)</th>
                        <th>Étudiant (Nom Prénom)</th>
                        <th>Motif Justification Accepté</th>
                        <th>Statut</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rattrapages as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['date']) ?></td>
                            <td><?= htmlspecialchars($r['enseignement']) ?></td>
                            <td><?= htmlspecialchars($r['etudiant_nom']) ?> <?= htmlspecialchars($r['etudiant_prenom']) ?></td>
                            <td><?= htmlspecialchars($r['motif_justif_accepte']) ?></td>
                            <td><span class="status accepted">EXCUSÉ</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px;">
            <form method="post" action="../logout.php">
                <button class="btn btn-secondary" type="submit">Se déconnecter</button>
            </form>
        </div>
    </main>
</div>
</body></html>