<?php
declare(strict_types=1);
session_start();


require_once __DIR__ . '/../vendor/autoload.php';


require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_once __DIR__ . '/../connexion/config/base_path.php';


require_once __DIR__ . '/../mesabsence/Model/AbsenceModel.php';
require_once __DIR__ . '/../Notification/NotificationService.php';



$userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
if ($userId <= 0) { header('Location: ' . BASE_PATH . '/connexion/View/login.php'); exit; }
$userEmail = $_SESSION['user']['email'] ?? 'inconnu@uphf.fr';
$userName = trim(($_SESSION['user']['prenom'] ?? '') . ' ' . ($_SESSION['user']['nom'] ?? ''));

// Simuler la récupération des données de session pour le header (si le fichier n'est pas inclus)
$identifiant = htmlspecialchars($_SESSION['identifiant'] ?? $userEmail, ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'] ?? 'ETUDIANT', ENT_QUOTES, 'UTF-8');


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
            // Seuls les fichiers qui n'ont pas d'erreur d'upload sont pris en compte
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $uploadedFiles[] = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                ];
            }
        }
    }

    //Doit avoir soit un commentaire, soit un fichier.
    $hasAnyProof = $hasComment || !empty($uploadedFiles);

    if (empty($dateDebut) || empty($dateFin)) {
        $message = "Erreur: Veuillez sélectionner une date de début et une date de fin.";
    } elseif (strtotime($dateDebut) === false || strtotime($dateFin) === false) {
        $message = "Erreur: Format de date invalide.";
    } elseif (strtotime($dateDebut) > strtotime($dateFin)) {
        $message = "Erreur: La date de début ne peut pas être postérieure à la date de fin.";
    } elseif (!$hasAnyProof) {
        $message = "Erreur: Veuillez ajouter un commentaire OU importer au moins un fichier justificatif.";
    } else {

        try {
            $justifIds = [];

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
        .header-nav-links a.btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .header-nav-links a.btn.active-btn {
            background-color: var(--uphf-blue-light);
            border-bottom: none;
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
            border-radius: 0;
            padding: 8px 15px;
            color: white;
            cursor: pointer;
            box-sizing: border-box;
        }

        .main-content-area {
            width: 90%;
            max-width: var(--content-max-width);
            margin: 20px auto;
            padding: 20px;
            flex-grow: 1;
            background-color: white;
            border: 1px solid var(--uphf-border-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-radius: 0;
            box-sizing: border-box;
        }

        .alert-message {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 0;
            border: 1px solid;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            position: fixed;
            top: 60px;
            width: 100%;
            text-align: center;
            z-index: 1000;
        }
        .alert-message.success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .alert-message.error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .date-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        .date-input-group .select-field {
            flex: 1;
            padding: 10px;
            border-radius: 0;
            border: 1px solid var(--uphf-border-color);
        }

        .btn-file-upload {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 0;
            cursor: pointer;
            font-weight: bold;
            white-space: nowrap;
        }
        .justif-form {
            border: 1px solid var(--uphf-border-color);
            padding: 20px;
            border-radius: 0;
            background-color: var(--uphf-bg-light);
        }

        .comment-area {
            width: 100%;
            padding: 10px;
            border-radius: 0;
            border: 1px solid var(--uphf-border-color);
            box-sizing: border-box;
            resize: vertical;
        }
        .btn-primary-valider {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 0;
            cursor: pointer;
            font-weight: bold;
        }

        .file-list-preview {
            max-width: 100%;
            margin: 10px 0 20px 0;
            text-align: left;
            padding: 10px;
            border: 1px solid #cfd9dd;
            border-radius: 0;
            background: #ffffff;
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
    </style>
</head>
<body>

<header class="app-header-nav">
    <div class="header-inner-content">
        <div class="header-logo-container">
            <img src="<?= BASE_PATH ?>/connexion/UPHF_logo.svg.png" class="header-logo" alt="UPHF">
        </div>

        <div class="header-nav-links">
            <a href="<?= BASE_PATH ?>/connexion/View/dashboard_etudiant.php" class="btn">Accueil Étudiant</a>
            <a href="<?= BASE_PATH ?>/mesabsence/index.php" class="btn">Consulter Mes Absences</a>
            <a href="<?= BASE_PATH ?>/soum_justif/justification.php" class="btn active-btn">Justifier une Absence</a>
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
    <h1 class="title" style="text-align: left;">Soumettre un Justificatif</h1>

    <?php if (!empty($message)): ?>
        <div class="alert-message <?= strpos($message, 'Erreur') !== false ? 'error' : 'success' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" action="justification.php" enctype="multipart/form-data" class="justif-form">
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

    // Fonction appelée lorsque l'utilisateur sélectionne des fichiers
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

        // 3. Mettre à jour l'affichage
        updatePreview();
    };

    // Fonction pour retirer un fichier du store
    const removeFile = (event) => {
        const target = event.target.closest('.remove-file-btn');
        if (!target) return;

        const fileNameToRemove = target.dataset.filename;
        const fileSizeToRemove = parseInt(target.dataset.filesize);

        const input = document.getElementById('fileSelected');
        const newFileStore = new DataTransfer();

        let removed = false;

        // Parcourir l'ancienne liste et ajouter seulement les fichiers qui NE correspondent PAS au fichier à retirer
        for (let i = 0; i < fileStore.files.length; i++) {
            const currentFile = fileStore.files[i];

            // On s'assure de ne retirer qu'une seule occurrence du fichier basé sur nom/taille
            if (currentFile.name === fileNameToRemove && currentFile.size === fileSizeToRemove && !removed) {
                removed = true;
            } else {
                newFileStore.items.add(currentFile);
            }
        }

        fileStore = newFileStore; // Remplacer l'ancien store par le nouveau
        input.files = fileStore.files; // Mettre à jour l'input pour l'envoi

        updatePreview(); // Rafraîchir l'affichage
    };

    // Fonction pour mettre à jour l'affichage de la liste des fichiers
    const updatePreview = () => {
        const preview = document.getElementById('file-list-preview');
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

    // Affichage des messages d'alerte/succès
    document.addEventListener('DOMContentLoaded', () => {
        const alertDiv = document.querySelector('.alert-message');
        if (alertDiv) {
            // Utiliser la classe pour vérifier l'état
            const isError = alertDiv.classList.contains('error');

            // S'assurer que le formulaire récupère les fichiers en cas d'erreur de soumission
            if (isError && typeof lastSubmittedFiles !== 'undefined' && lastSubmittedFiles.length > 0) {
                // Reconstituer le fileStore si nécessaire (logique complexe à implémenter sans le PHP backend)
                // Pour l'instant, on se concentre sur l'affichage et la soumission
            }

            alertDiv.style.opacity = '1';
            setTimeout(() => {
                alertDiv.style.opacity = '0';
            }, 7000);
            alertDiv.addEventListener('transitionend', (e) => {
                if (e.propertyName === 'opacity' && alertDiv.style.opacity === '0') {
                    alertDiv.remove();
                }
            });
        }


        document.getElementById('fileSelected').addEventListener('click', (e) => {
            // Effacer l'ancien store uniquement si la sélection n'est pas vide
            if (fileStore.files.length > 0) {
                // Ne pas effacer, laisser le onchange gérer l'ajout
            }
        });

    });

</script>
</body>
</html>