<?php
declare(strict_types=1);

require_once __DIR__ . '/../connexion/config/session.php'; // ✅ Session centralisée
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_once __DIR__ . '/Model/AbsenceModel.php';
require_once __DIR__ . '/UploadValidator.php'; // ✅ Nouveau validateur

require_role('ETUDIANT');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user']['id'] ?? 0;
$absenceId = (int)($_POST['absence_id'] ?? 0);

if ($absenceId <= 0 || !isset($_FILES['fichier'])) {
    header('Location: index.php?err=missing');
    exit;
}

// ✅ VALIDATION DU FICHIER UPLOADÉ
$errors = UploadValidator::validate($_FILES['fichier']);
if (!empty($errors)) {
    $errorMsg = implode(' ', $errors);
    header('Location: index.php?err=' . urlencode($errorMsg));
    exit;
}

// Récupération des informations du fichier
$originalName = $_FILES['fichier']['name'];
$tmpPath = $_FILES['fichier']['tmp_name'];
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
    // Insertion du justificatif
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
        header('Location: index.php?success=upload');
    } else {
        header('Location: index.php?err=db');
    }
} catch (\Throwable $e) {
    error_log("Erreur upload justificatif : " . $e->getMessage());
    header('Location: index.php?err=exception');
}
exit;