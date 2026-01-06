<?php
declare(strict_types=1);

require_once __DIR__ . '/../connexion/config/session.php'; // ✅ Au lieu de session_start()
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_once __DIR__ . '/Model/AbsenceModel.php';

require_role('ETUDIANT');




$userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
if ($userId <= 0) {
    header('Location: ' . BASE_PATH . '/connexion/View/login_fr.php');
    exit;
}

$filtre   = isset($_GET['filtre']) ? (string)$_GET['filtre'] : 'tous';
$ok       = isset($_GET['ok']) ? $_GET['ok'] : null;

$identity = AbsenceModel::getIdentity($userId);


$absences = AbsenceModel::getAbsencesForStudent($userId, $filtre);


$pendingRanges = AbsenceModel::getPendingRangeJustifications($userId);


$message = '';
if ($ok === 'justif_sent') {
    $message = 'Justificatif envoyé avec succès ! Il est maintenant en statut **"En attente"**.';
}



$transformedRanges = [];
foreach ($pendingRanges as $range) {
    $action = $range['last_action'] ?? '';

    // Détermination précise du statut
    if (in_array($action, ['DEMANDE_PRECISIONS', 'RENVOI_FICHIER', 'AUTORISATION_RENVOI'])) {
        $statutEtudiant = 'En révision';
        $canUpload = true;
    } else {
        $statutEtudiant = 'En attente';
        $canUpload = false;
    }

    $transformedRanges[] = [
        'absence_id'      => 0,
        'date'            => 'Du ' . $range['date_debut'] . ' au ' . $range['date_fin'],
        'motif'           => 'DÉCLARATION : ' . ($range['raison_demande'] ?? 'Absence déclarée'),
        'justificatif_id' => (int)$range['justificatif_id'],
        'statut'          => $statutEtudiant,
        'commentaire'     => $range['commentaire'] ?? '',
        'can_upload'      => $canUpload,
        'is_range'        => true,
        'has_file'        => true
    ];
}

// Fusion des absences individuelles et des déclarations de plage
$combinedList = array_merge($absences, $transformedRanges);


usort($combinedList, function ($a, $b) {
    $dateAStr = $a['date'];
    $dateBStr = $b['date'];

    if (strpos($dateAStr, 'Du ') === 0) {
        $dateAStr = str_replace('Du ', '', $dateAStr);
        $dateAStr = explode(' au ', $dateAStr)[0];
    }
    if (strpos($dateBStr, 'Du ') === 0) {
        $dateBStr = str_replace('Du ', '', $dateBStr);
        $dateBStr = explode(' au ', $dateBStr)[0];
    }

    $dateA = strtotime($dateAStr);
    $dateB = strtotime($dateBStr);

    return $dateA <=> $dateB;
});


$absences = $combinedList;
$message = $message;


require_once __DIR__ . '/View/liste.php';
