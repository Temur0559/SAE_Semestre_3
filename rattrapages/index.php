<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_once __DIR__ . '/../connexion/config/base_path.php';
require_role('ENSEIGNANT');

require_once __DIR__ . '/Model/RattrapageModel.php';

// Récupérer l'ID de l'enseignant connecté
$profId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
if ($profId <= 0) {
    header('Location: ' . BASE_PATH . '/connexion/View/login.php'); exit;
}

// Définir les variables utilisateur et de navigation
$user = $_SESSION['user'];
$identifiant = htmlspecialchars($user['identifiant'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($user['role'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
$prenom = htmlspecialchars($user['prenom'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
$nom = htmlspecialchars($user['nom'] ?? 'N/A', ENT_QUOTES, 'UTF-8');

$url_accueil = BASE_PATH . '/connexion/View/dashboard_enseignant.php';
$url_rattrapages = BASE_PATH . '/rattrapages/index.php';
$url_validation = BASE_PATH . '/equipe_pedag/index.php';

$assignedResources = \RattrapageModel::getAssignedResources($profId);

$ressource_selectionnee = $_GET['ressource'] ?? '';
if (!in_array($ressource_selectionnee, $assignedResources) && $ressource_selectionnee !== '') {
    $ressource_selectionnee = '';
}

$rattrapages = \RattrapageModel::getStudentsForRattrapage($profId, $ressource_selectionnee);

$message_filtre = "Affichage pour toutes les ressources attribuées (" . count($assignedResources) . " cours).";
if ($ressource_selectionnee !== '') {
    $message_filtre = "Affichage filtré pour la ressource : **" . htmlspecialchars($ressource_selectionnee) . "**";
}

function groupResourcesBySemester(array $resources): array {
    $grouped = [];
    foreach ($resources as $code) {
        if (preg_match('/[PSR](\d)/', $code, $matches)) {
            $semestre = "Semestre " . $matches[1];
        } else {
            $semestre = "Autres";
        }
        $grouped[$semestre][] = $code;
    }
    ksort($grouped);
    return $grouped;
}
$groupedResources = groupResourcesBySemester($assignedResources);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rattrapages</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/connexion/Style.css">
    <style>
        :root {
            --uphf-blue-dark: #004085;
            --uphf-blue-light: #007bff;
            --content-max-width: 1400px;
        }

        body {
            padding-top: 60px; /* Espace pour le header fixe */
            margin: 0;
            background-color: #f0f4f8;
            font-family: 'Arial', sans-serif;
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
        }

        .header-inner-content {
            display: flex;
            align-items: center;
            width: 90%;
            max-width: var(--content-max-width);
            justify-content: space-between;
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
            text-decoration: none;
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
        .user-info-logout strong {
            margin-right: 15px;
            font-size: 1em;
            font-weight: bold;
        }
        .user-info-logout button.btn {
            background-color: #dc3545;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            color: white;
            cursor: pointer;
        }

        .container {
            max-width: 1000px; margin: 20px auto; padding: 25px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 90%;
        }
        h1 { color: #004d40; margin-bottom: 25px; border-bottom: 2px solid #004d40; padding-bottom: 10px; }
        h2 { color: #333; font-size: 1.2em; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #cceeff; padding: 12px; text-align: left; }
        th { background-color: #e0f7fa; color: #004d40; font-weight: bold; }
        tr:nth-child(even) { background-color: #f7f7f7; }
        .filtre-form { margin-bottom: 20px; padding: 15px; border: 1px solid #b2dfdb; border-radius: 8px; background-color: #e0fcfc; }
        .filtre-form label { font-weight: bold; color: #004d40; margin-right: 10px; }
        select { padding: 8px; border-radius: 5px; border: 1px solid #b2dfdb; }
        .filtre-message { margin-top: 10px; font-style: italic; color: #004d40; }
        .status-excusée { font-weight: bold; color: #2e7d32; }
        .no-data { padding: 20px; text-align: center; border: 1px solid #ffccbc; background-color: #ffe0b2; color: #d32f2f; border-radius: 8px; }
        .logout-link { display: none; }
    </style>
</head>
<body>

<header class="app-header-nav">
    <div class="header-inner-content">
        <div class="header-logo-container">
            <img src="<?= BASE_PATH ?>/connexion/UPHF_logo.svg.png" class="header-logo" alt="UPHF">
        </div>

        <div class="header-nav-links">
            <a href="<?= $url_accueil ?>" class="btn">Accueil Enseignant</a>
            <a href="<?= $url_rattrapages ?>" class="btn active-btn">Gérer les Rattrapages</a>

        </div>

        <div class="user-info-logout">
            <strong><?= $prenom . ' ' . $nom ?> (<?= $role ?>)</strong>
            <form method="post" action="<?= BASE_PATH ?>/connexion/logout.php" style="display: inline-block; margin-left: 15px;">
                <button class="btn" type="submit">Se déconnecter</button>
            </form>
        </div>
    </div>
</header>
<div class="container">
    <h1>Liste des Rattrapages (Évaluations Excusées)</h1>

    <div class="filtre-form">
        <form method="get" action="index.php">
            <label for="ressource">Filtrer par Ressource :</label>
            <select name="ressource" id="ressource" onchange="this.form.submit()">
                <option value="">-- Toutes mes ressources --</option>
                <?php
                foreach ($groupedResources as $semestre => $codes):
                    ?>
                    <optgroup label="<?php echo htmlspecialchars($semestre); ?>">
                        <?php foreach ($codes as $code): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>"
                                    <?php if ($ressource_selectionnee === $code) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($code); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <div class="filtre-message"><?php echo $message_filtre; ?></div>
        </form>
    </div>

    <?php if (empty($rattrapages)): ?>
        <p class="no-data">Aucun étudiant à rattraper pour les cours sélectionnés (évaluations excusées).</p>
    <?php else: ?>
        <h2>Étudiants à faire rattraper</h2>
        <table>
            <thead>
            <tr>
                <th>Ressource (Code)</th>
                <th>Nom de l'étudiant</th>
                <th>Date de l'absence (Évaluation)</th>
                <th>Statut</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rattrapages as $r): ?>
                <tr>
                    <td>
                        <strong title="<?php echo htmlspecialchars($r['enseignement_libelle']); ?>">
                            <?php echo htmlspecialchars($r['ressource_code']); ?>
                        </strong>
                        <br><span style="font-size: 0.9em; color: #555;"><?php echo htmlspecialchars($r['enseignement_libelle']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($r['etudiant_prenom']) . ' ' . htmlspecialchars($r['etudiant_nom']); ?></td>
                    <td>
                        <?php
                        $date = new DateTime($r['date_absence']);
                        echo $date->format('d/m/Y');
                        ?>
                    </td>
                    <td class="status-excusée"><?php echo htmlspecialchars($r['statut_justif']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>