<?php
declare(strict_types=1);

require_once __DIR__ . '/../../connexion/config/base_path.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mes absences</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/mesabsence/Style.css">
    <script>
        function submitFiltre(sel){ sel.form.submit(); }
    </script>
</head>
<body class="page">

<h1 class="title">Mes absences</h1>

<?php

if (isset($ok) && $ok === 'justif_sent'):
    ?>
    <div class="toolbar" style="justify-content:center;margin-bottom:16px;">
        <div class="alert success" style="width:min(1100px,94vw);text-align:center;border-radius:12px;">
            ✅ **Justificatif envoyé avec succès ! Il est maintenant en statut "En attente".**
        </div>
    </div>
<?php endif; ?>


<div class="toolbar">
    <form action="<?= BASE_PATH ?>/mesabsence/index.php" method="get" class="filter-form">
        <label for="filtre">Filtrer :</label>
        <select name="filtre" id="filtre" onchange="submitFiltre(this)">
            <?php
            $filtreActuel = isset($_GET['filtre']) ? $_GET['filtre'] : 'tous';
            $opts = [
                    'tous'        => 'Tous',
                    'accepté'     => 'Accepté',
                    'rejeté'      => 'Rejeté',
                    'en révision' => 'En révision',
                    'en attente'  => 'En attente'
            ];
            foreach ($opts as $val => $lab) {
                $sel = (strtolower($filtreActuel) === strtolower($val)) ? 'selected' : '';
                echo '<option value="'.htmlspecialchars($val).'" '.$sel.'>'.htmlspecialchars($lab).'</option>';
            }
            ?>
        </select>
    </form>
</div>

<div class="sheet">

    <aside class="identity">
        <div class="avatar">👤</div>
        <input type="text" value="<?= htmlspecialchars($identity['nom'] ?? '') ?>" readonly>
        <input type="text" value="<?= htmlspecialchars($identity['prenom'] ?? '') ?>" readonly>
        <input type="text" value="<?= htmlspecialchars($identity['naissance'] ?? '') ?>" readonly>
        <input type="text" value="<?= htmlspecialchars($identity['ine'] ?? '') ?>" readonly>
        <div class="program"><?= htmlspecialchars($identity['program'] ?? '') ?></div>
    </aside>

    <section class="table-wrap">
        <h2>Historique Complet des Absences et Déclarations</h2>
        <table class="abs-table">
            <thead>
            <tr>
                <th>DATE (ou Plage)</th>
                <th>COURS / MOTIF</th>
                <th>JUSTIFICATIF</th>
                <th>STATUT</th>
                <th>COMMENTAIRE</th>
                <th>ACTION</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($absences as $a): ?>
                <?php
                $isRange = $a['is_range'] ?? false;
                $s = strtolower($a['statut']);


                $cls = ($s === 'accepté' || $s === 'accepte') ? 'accepted'
                        : (($s === 'rejeté' || $s === 'rejete') ? 'rejected'
                                : ($s === 'en révision' || $s === 'en revision' ? 'review'
                                        : ($s === 'en attente' ? 'pending' : '')));
                $displayStatus = $a['statut'];


                if (strtolower($filtreActuel) !== 'tous') {
                    if (strpos(strtolower($displayStatus), strtolower($filtreActuel)) === false) {
                        continue;
                    }
                }
                ?>
                <tr>
                    <td><?= htmlspecialchars($a['date']) ?></td>
                    <td><?= htmlspecialchars($a['motif']) ?></td>
                    <td class="justif-cell">
                        <?php if (!empty($a['justificatif_id'])): ?>
                            <a href="<?= BASE_PATH ?>/mesabsence/get_justif.php?id=<?= (int)$a['justificatif_id'] ?>" target="_blank" title="Télécharger le justificatif">📄</a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="status <?= $cls ?>"><?= htmlspecialchars($displayStatus) ?></td>
                    <td><?= htmlspecialchars($a['commentaire'] ?? '') ?></td>
                    <td>
                        <?php if ($isRange): ?>
                            <button class="btn-disabled" disabled>DÉCLARATION</button>
                        <?php elseif (!empty($a['can_upload'])): ?>
                            <form action="<?= BASE_PATH ?>/mesabsence/upload.php" method="post" enctype="multipart/form-data" class="upload-form">
                                <input type="hidden" name="absence_id" value="<?= (int)$a['absence_id'] ?>">
                                <input type="file" name="justificatif" required>
                                <button type="submit" class="btn-insert">INSÉRER</button>
                            </form>
                        <?php else: ?>
                            <button class="btn-disabled" disabled>INDISPONIBLE</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>



</body>
</html>