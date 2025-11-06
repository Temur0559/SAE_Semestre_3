<?php
declare(strict_types=1);
session_start();


if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'identifiant' => 'abdelwaheb.chakour',
        'role' => 'ETUDIANT'
    ];
}


require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_role('ETUDIANT');
require_once __DIR__ . '/../connexion/config/base_path.php';


require_once __DIR__ . '/Model/AbsenceModel.php';


$userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
if ($userId <= 0) {
    header('Location: ' . BASE_PATH . '/connexion/View/login.php');
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

    $transformedRanges[] = [
        'absence_id' => null,
        'date' => 'Du ' . $range['date_debut'] . ' au ' . $range['date_fin'],
        'motif' => 'Déclaration: ' . $range['raison_demande'],
        'justificatif_id' => (int)$range['justificatif_id'],
        'statut' => 'En attente',
        'commentaire' => $range['commentaire'],
        'can_upload' => false,
        'is_range' => true,
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