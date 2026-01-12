<?php
declare(strict_types=1);

require_once __DIR__ . '/../../connexion/config/base_path.php';

if (!isset($_SESSION)) { session_start(); }
$identifiant = htmlspecialchars($_SESSION['identifiant'] ?? '', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES, 'UTF-8');


require_once __DIR__ . '/../Model/AbsenceModel.php';

$userId = $_SESSION['user']['id'] ?? 0;
if ($userId === 0) {
    header('Location: ' . BASE_PATH . '/connexion/View/login_en.php');
    exit;
}

$filtre = $_GET['filtre'] ?? 'tous';
$identity = AbsenceModel::getIdentity($userId);
$absences = AbsenceModel::getAbsencesForStudent($userId, $filtre);
$ok = $_GET['ok'] ?? null;

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Absences</title>
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
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
            text-align: center;
            min-width: 70px;
        }
        .status.accepted { background-color: #28a745; } /* Vert */
        .status.rejected { background-color: #dc3545; } /* Rouge */
        .status.review { background-color: #85AEDE; color: #333; } /* Jaune/orange, texte noir */
        .status.pending { background: #F5B363; color: #856404; } /* Gris */
        .status.notjustified { background-color: gray; }

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
            <a href="<?= BASE_PATH ?>/connexion/View/dashboard_etudiant_en.php" class="btn">Student Home</a>
            <a href="<?= BASE_PATH ?>/mesabsence/index.php" class="btn active-btn">View My Absences</a>
            <a href="<?= BASE_PATH ?>/soum_justif/justification.php" class="btn">Justify an Absence</a>
        </div>

        <div class="user-info-logout">
            <strong><?= $identifiant; ?> (<?= $role; ?>)</strong>

            <a href="<?= BASE_PATH ?>/mesabsence/View/liste.php<?= isset($_GET['filtre']) ? '?filtre=' . urlencode($_GET['filtre']) : '' ?>" class="lang-switch-btn">🇫🇷 Français</a>

            <form method="post" action="<?= BASE_PATH ?>/connexion/logout.php" style="display: inline-block; margin: 0;">
                <button class="btn" type="submit">Logout</button>
            </form>
        </div>
    </div>
</header>

<div class="main-content-area">
    <h1 class="title">My Absences</h1>

    <?php if (isset($ok) && $ok === 'justif_sent'): ?>
        <div class="alert success" style="background-color: #d4edda; color: #155724; padding: 15px; margin: 10px; border-radius: 5px;">
            Justification sent successfully! It is now in "Pending" status.
        </div>
    <?php endif; ?>

    <div class="toolbar">
        <form action="<?= BASE_PATH ?>/mesabsence/index.php" method="get" class="filter-form">
            <label for="filtre">Filter:</label>
            <select name="filtre" id="filtre" onchange="submitFiltre(this)">
                <?php
                $filtreActuel = isset($_GET['filtre']) ? $_GET['filtre'] : 'tous';
                $opts = [
                        'tous' => 'All',
                        'accepté' => 'Accepted',
                        'rejeté' => 'Rejected',
                        'en révision' => 'Under Review',
                        'en attente' => 'Pending'
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
            <h2>Complete History of Absences and Declarations</h2>
            <table class="abs-table">
                <thead>
                <tr>
                    <th>DATE (or Range)</th>
                    <th>COURSE / REASON</th>
                    <th>JUSTIFICATION</th>
                    <th>STATUS</th>
                    <th>COMMENT</th>
                    <th>ACTION</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($absences as $a): ?>
                    <?php
                    $isRange = $a['is_range'] ?? false;
                    $s = strtolower(trim($a['statut']));

                    $cls = '';
                    if ($s === 'accepté' || $s === 'accepte') {
                        $cls = 'accepted';
                    } elseif ($s === 'rejeté' || $s === 'rejete') {
                        $cls = 'rejected';
                    } elseif ($s === 'À préciser' || $s === 'en revision') {
                        $cls = 'review';
                    } elseif ($s === 'en attente') {
                        $cls = 'pending';
                    }
                    else if ($s === 'non justifié' || $s === 'non justifie') {
                        $cls = 'notjustified';
                    }

                    $displayStatus = match($s) {
                        'accepté', 'accepte' => 'Accepted',
                        'rejeté', 'rejete' => 'Rejected',
                        'en révision', 'en revision', 'under review' => 'Under Review',
                        'en attente', 'pending' => 'Pending',
                        'non justifié', 'non justifie' => 'Not Justified',
                        default => $a['statut']
                    };

                    if (strtolower($filtreActuel) !== 'tous') {
                        if (strpos(strtolower($a['statut']), strtolower($filtreActuel)) === false) {
                            continue;
                        }
                    }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($a['date']) ?></td>
                        <td><?= htmlspecialchars($a['motif']) ?></td>
                        <td class="justif-cell" style="text-align: center;">
                            <?php
                            if ($a['files_id'] != NULL && count($a['files_id']) != 0): ?>
                                <a href="<?= BASE_PATH .'/mesabsence/get_files.php?ids=' . implode(',', $a['justificatif_id'])?>">📄</a>
                            <?php else: ?>
                                <span style="color: #ccc;">—</span>
                            <?php endif;
                            ?>
                        </td>
                        <td class="status <?= $cls ?>"><?= htmlspecialchars($displayStatus) ?></td>
                        <td><?= htmlspecialchars($a['commentaire'] ?? '') ?></td>
                        <td>
                            <?php
                            // On récupère les propriétés de la ligne actuelle
                            $isRange = $a['is_range'] ?? false;
                            // On vérifie si le statut est "en révision" (indépendamment de la casse)
                            $s_lower = strtolower(trim($a['statut']));
                            $enRevision = ($s_lower === 'À préciser');

                            // 1. Si l'upload est autorisé (Absence normale en révision OU Déclaration en révision)
                            if ($enRevision): ?>
                                <form action="<?= BASE_PATH ?>/mesabsence/upload.php" method="post" enctype="multipart/form-data" class="upload-form">
                                    <input type="hidden" name="justificatif_id" value="<?= $a['main_id'] ?>">
                                    <input type="file" name="justificatif" required>
                                    <button type="submit" class="btn-insert">INSERT</button>
                                </form>

                            <?php
                            // 2. Sinon, si c'est une plage horaire classique (en attente)
                            elseif ($isRange): ?>
                                <button class="btn-disabled" disabled>STATEMENT</button>

                            <?php
                            // 3. Cas par défaut pour les absences unitaires non modifiables
                            else: ?>
                                <button class="btn-disabled" disabled>UNAVAILABLE</button>
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