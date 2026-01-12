<?php
declare(strict_types=1);

// Upload un fichier, quand l'étudiant en fournis un nouveau (Demande de précision, insertion dévérouillée)

require_once __DIR__ . '/../connexion/config/session.php';
require_once __DIR__ . '/../connexion/config/db.php';
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_once __DIR__ . '/../equipe_pedag/Model/ActionModel.php';

require_once __DIR__ . '/UploadValidator.php';

require_role('ETUDIANT');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user']['id'] ?? 0;
$justificatifId = (int)$_POST['justificatif_id'] ?? -1;

// On accepte si on a un justificatif à mettre à jour
if($justificatifId <= 0 || !isset($_FILES['justificatif'])) {
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

if ($binaryContent === false) {
    header('Location: index.php?err=read');
    exit;
}

try {
    (new ActionModel(db()))->insertionNouveauFichier(
        $justificatifId,
        $binaryContent,
        $originalName,
        $mimeType
    );

    (new ActionModel(db()))->ajouter_decision($justificatifId, 'SOUMISSION', '', $userId);
    header('Location: index.php?ok=justif_sent');
} catch (\Throwable $e) {
    echo $e->getMessage();

    var_export($e->getTrace());
    error_log("Erreur upload justificatif : " . $e->getMessage());
    //header('Location: index.php?err=exception');
}
exit;