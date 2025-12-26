<?php
declare(strict_types=1);

// CORRECTION MAJEURE: Suppression de session_start() qui est déjà appelé par le contrôleur (index.php)
// session_start();

require_once __DIR__ . '/../../connexion/config/base_path.php';

// Les variables $identifiant et $role sont récupérées pour le header uniforme.
if (!isset($_SESSION)) { session_start(); }
$identifiant = htmlspecialchars($_SESSION['identifiant'] ?? '', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'] ?? '', ENT_QUOTES, 'UTF-8');

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
        /* Styles CSS Intégrés pour l'uniformité (Identique au Dashboard) */
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
            overflow-x: hidden; /* FIXE LE DÉFILEMENT HORIZONTAL */
        }

        /* --- Header UNIFORME ET FIXÉ --- */
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
            color: white;
            white-space: nowrap;
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

        /* --- Contenu PRINCIPAL CENTRÉ (.main-content-area) --- */
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

        /* Styles spécifiques à la page des absences */
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

        /* --- Styles des pastilles de statut RESTAURÉS --- */
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

        /* Les autres styles spécifiques (identité, tableau) restent les mêmes */
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
            <a href="<?= BASE_PATH ?>/connexion/View/dashboard_etudiant.php" class="btn">Accueil Étudiant</a>
            <a href="<?= BASE_PATH ?>/mesabsence/index.php" class="btn active-btn">Consulter Mes Absences</a>
            <a href="<?= BASE_PATH ?>/soum_justif/justification.php" class="btn">Justifier une Absence</a>
        </div>

        <div class="user-info-logout">
            <strong><?= $identifiant; ?> (<?= $role; ?>)</strong>
            <form method="post" action="<?= BASE_PATH ?>/connexion/logout.php" style="display: inline-block;">
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

                    // Les classes CSS pour les statuts
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
                            <?php else:
                                /**
                                 * LOGIQUE CORRIGÉE :
                                 * 1. On nettoie le statut pour éviter les erreurs d'espaces ou de majuscules
                                 */
                                $s_lower = strtolower(trim($a['statut']));


                                $peutDeposer = empty($a['justificatif_id']) || ($s_lower === 'en révision' || $s_lower === 'en revision');


                                if ($s_lower === 'en attente') {
                                    $peutDeposer = false;
                                }

                                if ($peutDeposer): ?>
                                    <form action="<?= BASE_PATH ?>/mesabsence/upload.php" method="post" enctype="multipart/form-data" class="upload-form">
                                        <input type="hidden" name="absence_id" value="<?= (int)$a['absence_id'] ?>">
                                        <input type="file" name="justificatif" required>
                                        <button type="submit" class="btn-insert">INSÉRER</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-disabled" disabled>INDISPONIBLE</button>
                                <?php endif; ?>
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