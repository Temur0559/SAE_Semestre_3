<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_once __DIR__ . '/../connexion/config/base_path.php';
require_role('ETUDIANT');

require_once __DIR__ . '/../mesabsence/Model/AbsenceModel.php';

$userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
$identity = \AbsenceModel::getIdentity($userId);
$absences = \AbsenceModel::getAbsencesForStudent($userId, 'tous');

$notifications = [];
$nbAlertes = 0;

foreach ($absences as $a) {
    $statut = strtolower($a['statut']);

    try {
        $date = (new DateTime($a['date']))->format('d/m/Y');
    } catch (\Exception $e) {
        $date = $a['date'];
    }


    if ($statut === 'rejeté' || $statut === 'en révision') {
        $nbAlertes++;
        $notifications[] = [
                'type' => 'alert',
                'titre' => "Action Rejetée / Précisions demandées (Absence du {$date})",
                'message' => "Votre justification pour l'absence au cours '{$a['motif']}' a été <strong>{$a['statut']}e</strong>. Raison : {$a['commentaire']}",
                'date' => $date
        ];
    }

    elseif ($statut === 'en attente' && $a['justificatif_id'] === null) {
        $nbAlertes++;
        $notifications[] = [
                'type' => 'reminder',
                'titre' => "Justification requise (Absence du {$date})",
                'message' => "Vous êtes absent(e) au cours '{$a['motif']}' et n'avez pas encore soumis de justificatif. Veuillez agir rapidement.",
                'date' => $date
        ];
    }
}


$notifications = array_reverse($notifications);

?>
<!doctype html>
<html lang="fr"><head>
    <meta charset="utf-8"><title>Mes Notifications</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/connexion/Style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/mesabsence/Style.css">
    <style>
        .notification-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border-left: 5px solid;
        }
        .notification-card.alert { border-left-color: #e63946; }
        .notification-card.reminder { border-left-color: #ff9f2d; }
        .notification-title { font-weight: 700; margin-bottom: 4px; font-size: 1.1em; }
        .notification-date { font-size: 0.8em; color: var(--muted); float: right; }
    </style>
</head><body>
<div class="auth-shell">
    <header class="brandbar"><img src="<?= BASE_PATH ?>/connexion/UPHF_logo.svg.png" class="brandbar__logo" alt="UPHF"></header>
    <main class="card layout-1col">
        <h1>Mes Notifications (<?= $nbAlertes ?> Alertes)</h1>
        <p>Messages importants et rappels concernant l'état de vos absences et justificatifs.</p>

        <div style="margin-top: 20px; max-width: 800px; width: 100%;">
            <?php if (empty($notifications)): ?>
                <div class="alert success">
                    ✅ Aucune notification ou alerte active. Tout est en ordre.
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <div class="notification-card <?= htmlspecialchars($n['type']) ?>">
                        <span class="notification-date">Le <?= htmlspecialchars($n['date']) ?></span>
                        <div class="notification-title"><?= $n['titre'] ?></div>
                        <p style="margin: 0; font-size: 0.9em;"><?= $n['message'] ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a class="btn btn-secondary" href="<?= BASE_PATH ?>/connexion/View/dashboard_etudiant.php" style="margin-top: 20px;">← Retour au Tableau de Bord</a>
    </main>
</div>
</body></html>