<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mes absences</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/mesabsence/Style.css">
    <script>
        function submitFiltre(sel){ sel.form.submit(); }
    </script>
</head>
<body class="page">

<h1 class="title">Mes absences</h1>

<div class="toolbar">
    <form action="../index.php" method="get" class="filter-form">
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
        <table class="abs-table">
            <thead>
            <tr>
                <th>DATE</th>
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
                $s = strtolower($a['statut']);
                $cls = ($s === 'accepté' || $s === 'accepte') ? 'accepted'
                        : (($s === 'rejeté' || $s === 'rejete') ? 'rejected'
                                : ($s === 'en révision' || $s === 'en revision' ? 'review'
                                        : ($s === 'en attente' ? 'pending' : '')));
                ?>
                <tr>
                    <td><?= htmlspecialchars($a['date']) ?></td>
                    <td><?= htmlspecialchars($a['motif']) ?></td>
                    <td class="justif-cell">
                        <?php if (!empty($a['justificatif_id'])): ?>
                            <a href="/mesabsence/get_justif.php?id=<?= (int)$a['justificatif_id'] ?>" target="_blank" title="Télécharger le justificatif">📄</a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="status <?= $cls ?>"><?= htmlspecialchars($a['statut']) ?></td>
                    <td><?= htmlspecialchars($a['commentaire'] ?? '') ?></td>
                    <td>
                        <?php if (!empty($a['can_upload'])): ?>
                            <form action="/mesabsence/upload.php" method="post" enctype="multipart/form-data" class="upload-form">
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

<div class="actions-bottom">
    <form action="/mesabsence/save.php" method="post">
        <input type="hidden" name="filtre" value="<?= htmlspecialchars($filtreActuel ?? 'tous') ?>">
        <button class="btn-primary" type="submit">ENREGISTRER LES MODIFICATIONS</button>
    </form>
</div>

</body>
</html>
