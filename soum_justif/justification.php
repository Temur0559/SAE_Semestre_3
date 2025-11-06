<?php
declare(strict_types=1);
session_start();


require_once __DIR__ . '/../vendor/autoload.php';


require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_role('ETUDIANT');
require_once __DIR__ . '/../connexion/config/base_path.php';


require_once __DIR__ . '/../mesabsence/Model/AbsenceModel.php';
require_once __DIR__ . '/../Notification/NotificationService.php';



$userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
if ($userId <= 0) { header('Location: ' . BASE_PATH . '/connexion/View/login.php'); exit; }
$userEmail = $_SESSION['user']['email'] ?? 'inconnu@uphf.fr';
$userName = trim(($_SESSION['user']['prenom'] ?? '') . ' ' . ($_SESSION['user']['nom'] ?? ''));



$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["valider"])) {

    $dateDebut       = isset($_POST["dateDebut"]) ? (string)$_POST["dateDebut"] : '';
    $dateFin         = isset($_POST["dateFin"]) ? (string)$_POST["dateFin"] : '';
    $commentaire     = isset($_POST["motifSelected"]) ? trim((string)$_POST["motifSelected"]) : '';
    $raison          = isset($_POST["raisonSelected"]) ? (string)$_POST["raisonSelected"] : 'Autre';
    $file            = isset($_FILES['fileSelected']) ? $_FILES['fileSelected'] : null;

    $hasFile = ($file && $file['error'] === UPLOAD_ERR_OK);


    if (empty($dateDebut) || empty($dateFin)) {
        $message = "Erreur: Veuillez sélectionner une date de début et une date de fin.";
    } elseif (strtotime($dateDebut) === false || strtotime($dateFin) === false) {
        $message = "Erreur: Format de date invalide.";
    } elseif (strtotime($dateDebut) > strtotime($dateFin)) {
        $message = "Erreur: La date de début ne peut pas être postérieure à la date de fin.";
    } elseif (!$hasFile && $commentaire === '') {
        $message = "Erreur: Veuillez ajouter un commentaire OU importer un fichier justificatif.";
    } else {

        try {

            $original = $hasFile ? (string)$file['name'] : null;
            $mime     = $hasFile ? (string)($file['type'] ?: 'application/octet-stream') : null;
            $blob     = $hasFile ? file_get_contents($file['tmp_name']) : null;


            $justifId = AbsenceModel::insertDemandeJustification(
                    $userId,
                    $dateDebut,
                    $dateFin,
                    $original,
                    $mime,
                    $blob,
                    $commentaire,
                    $raison
            );


            $subject = "Confirmation de soumission de justificatif d'absence - UPHF";
            $body = "<p>Bonjour " . htmlspecialchars($userName) . ",</p>"
                    . "<p>Votre demande de justification (ID #$justifId) couvrant la période du <strong>" . htmlspecialchars($dateDebut) . "</strong> au <strong>" . htmlspecialchars($dateFin) . "</strong> a été enregistrée.</p>"
                    . "<p>Motif déclaré: <strong>" . htmlspecialchars($raison) . "</strong></p>"
                    . "<p>Statut actuel: <strong>En attente de traitement</strong>.</p>";

            if (!NotificationService::sendEmail($userEmail, $subject, $body, $userName)) {
                error_log("Échec de l'envoi de l'email de confirmation à $userEmail pour justif #$justifId.");
            }

            $message = "Demande de justification enregistrée (ID $justifId). En attente de validation.";


            header('Location: ' . BASE_PATH . '/mesabsence/index.php?ok=justif_sent');
            exit;

        } catch (Throwable $e) {
            $message = "Erreur d'insertion : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Justification d'Absence</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/connexion/Style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/soum_justif/Index.css">
    <style>
        .date-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .date-input-group .select-field {
            flex: 1;
        }
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <img src="<?= BASE_PATH ?>/connexion/UPHF_logo.svg.png" class="uphf-logo" alt="UPHF">
        <h1 class="page-title">Déclaration/Justification d'Absences</h1>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert-message <?= strpos($message, 'succès') !== false ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="justif-form">

        <div class="form-row-top date-input-group">
            <label for="dateDebut">**Du :**</label>
            <input type="date" id="dateDebut" name="dateDebut" required class="select-field">

            <label for="dateFin">**Au :**</label>
            <input type="date" id="dateFin" name="dateFin" required class="select-field">

            <select name="raisonSelected" required class="select-field">
                <option value="" disabled selected>Sélectionner un motif</option>
                <option>Maladie</option>
                <option>Transport</option>
                <option>Autre</option>
            </select>

            <label for="fileSelected" class="btn-file-upload">
                Importer un fichier (Optionnel) 📄
            </label>
            <input type="file" name="fileSelected" id="fileSelected" style="display: none;">
        </div>

        <div class="form-row-comment">
            <textarea id="motifSelected" name="motifSelected" placeholder="Ajouter un commentaire (Obligatoire si pas de fichier)..." class="comment-area"></textarea>
        </div>

        <div class="form-row-submit">
            <button type="submit" name="valider" class="btn-primary-valider">VALIDER LA DÉCLARATION</button>
        </div>

    </form>
</div>
</body>
</html>