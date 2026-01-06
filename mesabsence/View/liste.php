<?php
declare(strict_types=1);


require_once __DIR__ . '/../../connexion/config/base_path.php';

if (!isset($_SESSION)) { session_start(); }
$identifiant = htmlspecialchars($_SESSION['identifiant'] ?? '', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES, 'UTF-8');

// Récupération des données pour la page
require_once __DIR__ . '/../Model/AbsenceModel.php';

$userId = $_SESSION['user']['id'] ?? 0;
if ($userId === 0) {
    header('Location: ' . BASE_PATH . '/connexion/View/login_fr.php');
    exit;
}

$filtre = $_GET['filtre'] ?? 'tous';
$identity = AbsenceModel::getIdentity($userId);
$absences = AbsenceModel::getAbsencesForStudent($userId, $filtre);
$ok = $_GET['ok'] ?? null;

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mes absences</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/mesabsence/Style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/connexion/Style.css">

    <style>
        :root {
            --uphf-blue-dark: #004085;
            --uphf-blue-light: #007bff;
            --uphf-text-dark: #333;
            --uphf-bg-light: #f4f7f6;
            --uphf-border-color: #e0e0e0;
            --content-max-width: 1400px;
        }

        body {
            padding-top: 60px;
            margin: 0;
            background-color: var(--uphf-bg-light);
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .app-header-nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--uphf-blue-dark);
            height: 60px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            box-sizing: border-box;
        }

        .header-inner-content {
            display: flex;
            align-items: center;
            width: 90%;
            max-width: var(--content-max-width);
            justify-content: space-between;
            box-sizing: border-box;
        }

        .header-logo {
            height: 30px;
            margin-right: 20px;
            filter: brightness(0) invert(1);
        }

        .header-nav-links a.btn {
            background-color: transparent;
            border: none;
            color: white;
            padding: 10px 15px;
            margin-right: 5px;
            border-radius: 0;
            font-weight: bold;
            box-sizing: border-box;
        }
        .header-nav-links a.btn.active-btn {
            background-color: var(--uphf-blue-light);
            border-bottom: 3px solid white;
        }

        .user-info-logout {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
            white-space: nowrap;
        }

        .lang-switch-btn {
            background-color: white;
            color: var(--uphf-blue-dark);
            padding: 8px 15px;
            border-radius: 0;
            text-decoration: none;
            font-weight: bold;
            border: 2px solid white;
            transition: all 0.2s;
        }

        .lang-switch-btn:hover {
            background-color: var(--uphf-blue-light);
            color: white;
        }

        .user-info-logout button.btn {
            background-color: #dc3545;
            border: none;
            border-radius: 0;
            padding: 8px 15px;
            color: white;
            cursor: pointer;
            box-sizing: border-box;
        }

        .main-content-area {
            width: 90%;
            max-width: var(--content-max-width);
            margin: 20px auto;
            padding: 0;
            flex-grow: 1;
            background-color: white;
            border: 1px solid var(--uphf-border-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-radius: 0;
            box-sizing: border-box;
        }

        .title {
            text-align: center;
            margin-top: 20px;
            color: var(--uphf-text-dark);
        }
        .toolbar {
            background-color: white;
            border-bottom: 1px solid var(--uphf-border-color);
            padding: 10px 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            box-sizing: border-box;
        }

        .sheet {
            display: flex;
            padding: 0;
            border-radius: 0;
            width: 100%;
            box-sizing: border-box;
        }

        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px; /* Pastille ronde */
            font-size: 0.8em;
            font-weight: bold;
            color: white;
            text-align: center;
            min-width: 70px;
        }
        .status.accepted { background-color: #28a745; } /* Vert */
        .status.rejected { background-color: #dc3545; } /* Rouge */
        .status.review { background-color: #ffc107; color: #333; } /* Jaune/orange, texte noir */
        .status.pending { background-color: #6c757d; } /* Gris */
        .status.en-attente { background-color: #6c757d; }

    </style>

    <script>
        function submitFiltre(sel){ sel.form.submit(); }
    </script>
</head>
<body>

<header class="app-header-nav">
    <div class="header-inner-content">
        <div class="header-logo-container">
            <img src="<?= BASE_PATH ?>/connexion/UPHF_logo.svg.png" class="header-logo" alt="UPHF">
        </div>

        <div class="header-nav-links">
            <a href="<?= BASE_PATH ?>/connexion/View/dashboard_etudiant_fr.php" class="btn">Accueil Étudiant</a>
            <a href="<?= BASE_PATH ?>/mesabsence/index.php" class="btn active-btn">Consulter Mes Absences</a>
            <a href="<?= BASE_PATH ?>/soum_justif/justification.php" class="btn">Justifier une Absence</a>
        </div>

        <div class="user-info-logout">
            <strong><?= $identifiant; ?> (<?= $role; ?>)</strong>

            <!-- BOUTON LANGUE -->
            <a href="<?= BASE_PATH ?>/mesabsence/View/liste_en.php<?= isset($_GET['filtre']) ? '?filtre=' . urlencode($_GET['filtre']) : '' ?>" class="lang-switch-btn">🇬🇧 English</a>

            <form method="post" action="<?= BASE_PATH ?>/connexion/logout.php" style="display: inline-block; margin: 0;">
                <button class="btn" type="submit">Se déconnecter</button>
            </form>
        </div>
    </div>
</header>

<div class="main-content-area">
    <h1 class="title">Mes absences</h1>

    <?php
    if (isset($ok) && $ok === 'justif_sent'):
        ?>
        <div class="alert success">
            **Justificatif envoyé avec succès ! Il est maintenant en statut "En attente".**
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

                    $cls = '';
                    if ($s === 'accepté' || $s === 'accepte') {
                        $cls = 'accepted';
                    } elseif ($s === 'rejeté' || $s === 'rejete') {
                        $cls = 'rejected';
                    } elseif ($s === 'en révision' || $s === 'en revision') {
                        $cls = 'review';
                    } elseif ($s === 'en attente') {
                        $cls = 'pending';
                    }
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
                        <td class="justif-cell" style="text-align: center;">
                            <?php
                            if (!empty($a['justificatif_id']) && ($a['has_file'] ?? true)): ?>
                                <a href="<?= BASE_PATH ?>/mesabsence/get_justif.php?id=<?= (int)$a['justificatif_id'] ?>" target="_blank">📄</a>
                            <?php else: ?>
                                <span style="color: #ccc;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="status <?= $cls ?>"><?= htmlspecialchars($displayStatus) ?></td>
                        <td><?= htmlspecialchars($a['commentaire'] ?? '') ?></td>
                        <td>
                            <?php
                            // On récupère les propriétés de la ligne actuelle
                            $isRange = $a['is_range'] ?? false;
                            // On vérifie si le statut est "en révision" (indépendamment de la casse)
                            $s_lower = strtolower(trim($a['statut']));
                            $enRevision = ($s_lower === 'en révision' || $s_lower === 'en revision');

                            // 1. Si l'upload est autorisé (Absence normale en révision OU Déclaration en révision)
                            if ($enRevision): ?>
                                <form action="<?= BASE_PATH ?>/mesabsence/upload.php" method="post" enctype="multipart/form-data" class="upload-form">
                                    <input type="hidden" name="absence_id" value="<?= (int)($a['absence_id'] ?? 0) ?>">
                                    <input type="hidden" name="justificatif_id" value="<?= (int)($a['justificatif_id'] ?? 0) ?>">
                                    <input type="file" name="justificatif" required>
                                    <button type="submit" class="btn-insert">INSÉRER</button>
                                </form>

                            <?php
                            // 2. Sinon, si c'est une plage horaire classique (en attente)
                            elseif ($isRange): ?>
                                <button class="btn-disabled" disabled>DÉCLARATION</button>

                            <?php
                            // 3. Cas par défaut pour les absences unitaires non modifiables
                            else: ?>
                                <button class="btn-disabled" disabled>INDISPONIBLE</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</div>

</body>
</html>