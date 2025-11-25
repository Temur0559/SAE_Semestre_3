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
// Variables pour garder l'état du formulaire après une erreur
$dateDebut = $_POST["dateDebut"] ?? '';
$dateFin = $_POST["dateFin"] ?? '';
$commentaire = $_POST["motifSelected"] ?? '';
$raison = $_POST["raisonSelected"] ?? '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["valider"])) {

    $dateDebut       = isset($_POST["dateDebut"]) ? (string)$_POST["dateDebut"] : '';
    $dateFin         = isset($_POST["dateFin"]) ? (string)$_POST["dateFin"] : '';
    $commentaire     = isset($_POST["motifSelected"]) ? trim((string)$_POST["motifSelected"]) : '';
    $raison          = isset($_POST["raisonSelected"]) ? (string)$_POST["raisonSelected"] : 'Autre';
    $files           = isset($_FILES['fileSelected']) ? $_FILES['fileSelected'] : null;

    $hasComment = !empty($commentaire);

    // Vérification de la présence de fichiers uploadés sans erreur
    $uploadedFiles = [];
    $isMultiFile = isset($files['tmp_name']) && is_array($files['tmp_name']);

    if ($isMultiFile) {
        for ($i = 0; $i < count($files['tmp_name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $uploadedFiles[] = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                ];
            }
        }
    }

    $hasAnyProof = $hasComment || !empty($uploadedFiles);

    // --- DÉBUT DES VÉRIFICATIONS ---
    if (empty($dateDebut) || empty($dateFin)) {
        $message = "Erreur: Veuillez sélectionner une date de début et une date de fin.";
    } elseif (strtotime($dateDebut) === false || strtotime($dateFin) === false) {
        $message = "Erreur: Format de date invalide.";
    } elseif (strtotime($dateDebut) > strtotime($dateFin)) {
        $message = "Erreur: La date de début ne peut pas être postérieure à la date de fin.";
    } elseif (!$hasAnyProof) {
        $message = "Erreur: Veuillez ajouter un commentaire OU importer au moins un fichier justificatif.";
    } else {
        // --- FIN DES VÉRIFICATIONS ---

        try {
            $justifIds = [];

            // 1. Gérer l'upload du/des fichier(s) (chaque fichier génère un justificatif)
            foreach ($uploadedFiles as $file) {
                $original = (string)$file['name'];
                $mime     = (string)($file['type'] ?: 'application/octet-stream');
                $blob     = file_get_contents($file['tmp_name']);

                $justifIds[] = AbsenceModel::insertDemandeJustification(
                        $userId,
                        $dateDebut,
                        $dateFin,
                        $original,
                        $mime,
                        $blob,
                        $commentaire,
                        $raison
                );
            }

            // 2. Si AUCUN fichier n'a été uploadé mais qu'un commentaire est présent, on insère une déclaration simple (fichier NULL)
            if (empty($justifIds) && $hasComment) {
                $justifIds[] = AbsenceModel::insertDemandeJustification(
                        $userId,
                        $dateDebut,
                        $dateFin,
                        null, // originalName
                        null, // mime
                        null, // binaryContent
                        $commentaire,
                        $raison
                );
            }

            if (empty($justifIds)) {
                throw new \Exception("Aucun justificatif n'a pu être enregistré.");
            }

            $justifId = $justifIds[0]; // ID du premier justificatif pour l'email/redirection
            $nbFichiers = count($justifIds);

            // 3. Liaison automatique des absences dans la plage (déjà fait dans AbsenceModel::insertDemandeJustification)

            // 4. Notification et Redirection
            $subject = "Confirmation de soumission de justificatif d'absence - UPHF";
            $file_message = ($nbFichiers > 1) ? "($nbFichiers fichiers)" : (empty($uploadedFiles) ? "(Déclaration simple)" : "(1 fichier)");

            $body = "<p>Bonjour " . htmlspecialchars($userName) . ",</p>"
                    . "<p>Votre demande de justification $file_message (ID #$justifId) couvrant la période du <strong>" . htmlspecialchars($dateDebut) . "</strong> au <strong>" . htmlspecialchars($dateFin) . "</strong> a été enregistrée.</p>"
                    . "<p>Motif déclaré: <strong>" . htmlspecialchars($raison) . "</strong></p>"
                    . "<p>Statut actuel: <strong>En attente de traitement</strong>.</p>";

            // Envoi d'email
            if (!NotificationService::sendEmail($userEmail, $subject, $body, $userName)) {
                error_log("Échec de l'envoi de l'email de confirmation à $userEmail pour justif #$justifId.");
            }

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
        /* Styles ajoutés pour les fichiers multiples/suppression */
        .file-list-preview {
            max-width: 500px;
            margin: 10px auto;
            text-align: left;
            padding: 10px;
            border: 1px solid #cfd9dd;
            border-radius: 8px;
            background: #f7fbfc;
        }
        .file-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            font-size: 14px;
            color: #555;
            border-bottom: 1px dashed #eee;
        }
        .remove-file-btn {
            background-color: #d9534f;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            line-height: 1;
            cursor: pointer;
            font-weight: bold;
            font-size: 10px;
            transition: transform 0.1s;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .remove-file-btn:hover {
            transform: scale(1.1);
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
        <div class="alert-message <?= strpos($message, 'succès') !== false || strpos($message, 'enregistrée') !== false ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="justif-form">

        <div class="form-row-top date-input-group">
            <label for="dateDebut">**Du :**</label>
            <input type="date" id="dateDebut" name="dateDebut" required class="select-field" value="<?= htmlspecialchars($dateDebut ?? '') ?>">

            <label for="dateFin">**Au :**</label>
            <input type="date" id="dateFin" name="dateFin" required class="select-field" value="<?= htmlspecialchars($dateFin ?? '') ?>">

            <select name="raisonSelected" required class="select-field">
                <option value="" disabled selected>Sélectionner un motif</option>
                <option value="Maladie" <?= ($raison === 'Maladie' ? 'selected' : '') ?>>Maladie</option>
                <option value="Transport" <?= ($raison === 'Transport' ? 'selected' : '') ?>>Transport</option>
                <option value="Autre" <?= ($raison === 'Autre' ? 'selected' : '') ?>>Autre</option>
            </select>

            <label for="fileSelected" class="btn-file-upload">
                Importer des fichiers (Optionnel) 📄
            </label>
            <input type="file" name="fileSelected[]" id="fileSelected" style="display: none;" multiple onchange="displayFileNames(this)">
        </div>

        <div id="file-list-preview" class="file-list-preview" style="display: none;">
        </div>

        <div class="form-row-comment">
            <textarea id="motifSelected" name="motifSelected" placeholder="Ajouter un commentaire (Obligatoire si pas de fichier)..." class="comment-area"><?= htmlspecialchars($commentaire ?? '') ?></textarea>
        </div>

        <div class="form-row-submit">
            <button type="submit" name="valider" class="btn-primary-valider">VALIDER LA DÉCLARATION</button>
        </div>

    </form>
</div>
<script>
    // Variable globale pour stocker la liste cumulative des fichiers sélectionnés
    let fileStore = new DataTransfer();

    const displayFileNames = (input) => {
        const preview = document.getElementById('file-list-preview');

        // 1. Fusionner la nouvelle sélection (input.files) dans le store
        Array.from(input.files).forEach(file => {
            // Vérification simple de duplication (nom + taille)
            let isDuplicate = false;
            for (let i = 0; i < fileStore.items.length; i++) {
                if (fileStore.items[i].getAsFile().name === file.name && fileStore.items[i].getAsFile().size === file.size) {
                    isDuplicate = true;
                    break;
                }
            }
            if (!isDuplicate) {
                fileStore.items.add(file);
            }
        });

        // 2. Mettre à jour l'input avec la liste complète pour l'envoi au serveur
        input.files = fileStore.files;

        const updatePreview = () => {
            preview.innerHTML = '';
            const currentFiles = fileStore.files;

            if (currentFiles.length > 0) {
                preview.style.display = 'block';


                Array.from(currentFiles).forEach((file, i) => {
                    const item = document.createElement('div');
                    item.className = 'file-list-item';
                    item.innerHTML = `
                        <span>${file.name}</span>
                        <button type="button" class="remove-file-btn" data-filename="${file.name}" data-filesize="${file.size}">X</button>
                    `;
                    preview.appendChild(item);
                });

                // Réaffecter les écouteurs d'événements pour les boutons de suppression
                document.querySelectorAll('.remove-file-btn').forEach(button => {
                    button.addEventListener('click', removeFile);
                });

            } else {
                preview.style.display = 'none';
            }
        };

        const removeFile = (event) => {
            const target = event.target;
            const fileNameToRemove = target.dataset.filename;
            const fileSizeToRemove = parseInt(target.dataset.filesize);

            const newFileStore = new DataTransfer();

            // Parcourir l'ancienne liste et ajouter seulement les fichiers qui NE correspondent PAS au fichier à retirer
            let removed = false;

            for (let i = 0; i < fileStore.files.length; i++) {
                const currentFile = fileStore.files[i];
                // On s'assure de ne retirer qu'une seule occurrence du fichier
                if (currentFile.name === fileNameToRemove && currentFile.size === fileSizeToRemove && !removed) {
                    removed = true;
                } else {
                    newFileStore.items.add(currentFile);
                }
            }

            fileStore = newFileStore; // Remplacer l'ancien store par le nouveau
            input.files = fileStore.files; // Mettre à jour l'input pour l'envoi

            updatePreview();
        };

        updatePreview();
    };

    // Affichage des messages d'alerte/succès
    document.addEventListener('DOMContentLoaded', () => {
        const alertDiv = document.querySelector('.alert-message');
        if (alertDiv) {
            alertDiv.style.opacity = '1';
            setTimeout(() => {
                alertDiv.style.opacity = '0';
            }, 7000);
            alertDiv.addEventListener('transitionend', () => {
                if (alertDiv.style.opacity === '0') {
                    alertDiv.remove();
                }
            });
        }
    });

</script>
</body>
</html>