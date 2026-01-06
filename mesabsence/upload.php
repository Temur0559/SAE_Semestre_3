<?php
declare(strict_types=1);

require_once __DIR__ . '/../connexion/config/session.php';
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_once __DIR__ . '/Model/AbsenceModel.php';
require_once __DIR__ . '/UploadValidator.php';

require_role('ETUDIANT');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user']['id'] ?? 0;
$absenceId = (int)($_POST['absence_id'] ?? 0);
$justificatifId = (int)($_POST['justificatif_id'] ?? 0); // Récupère l'ID envoyé par la vue

// MODIFICATION : On accepte si on a une absence OU un justificatif à mettre à jour
if (($absenceId <= 0 && $justificatifId <= 0) || !isset($_FILES['justificatif'])) {
    header('Location: index.php?err=missing');
    exit;
}

// VALIDATION DU FICHIER UPLOADÉ
$errors = UploadValidator::validate($_FILES['justificatif']);
if (!empty($errors)) {
    $errorMsg = implode(' ', $errors);
    header('Location: index.php?err=' . urlencode($errorMsg));
    exit;
}

// Récupération des informations du fichier
$originalName = $_FILES['justificatif']['name'];
$tmpPath = $_FILES['justificatif']['tmp_name'];
$mimeType = mime_content_type($tmpPath);
$binaryContent = file_get_contents($tmpPath);

// Récupération du commentaire et motif libre
$commentaire = trim($_POST['commentaire'] ?? '');
$motifLibre = trim($_POST['motif_libre'] ?? '');

if ($binaryContent === false) {
    header('Location: index.php?err=read');
    exit;
}

try {
    if ($justificatifId > 0) {

        $success = AbsenceModel::updateJustificatif(
            $justificatifId,
            $userId,
            $originalName,
            $mimeType,
            $binaryContent
        );
        if ($success) {
            header('Location: index.php?ok=justif_sent');
        } else {
            header('Location: index.php?err=db');
        }
    } else {
        $justifId = AbsenceModel::insertJustificatif(
            $absenceId,
            $userId,
            $originalName,
            $mimeType,
            $binaryContent,
            $commentaire,
            $motifLibre
        );

        if ($justifId > 0) {
            header('Location: index.php?ok=justif_sent');
        } else {
            header('Location: index.php?err=db');
        }
    }
} catch (\Throwable $e) {
    error_log("Erreur upload justificatif : " . $e->getMessage());
    header('Location: index.php?err=exception');
}
exit;