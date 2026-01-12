<?php
declare(strict_types=1);

// Télécharger les fichiers dans un ZIP

session_start();

require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_role('ETUDIANT');

require_once __DIR__ . '/../connexion/config/db.php';
require_once __DIR__ . '/Model/AbsenceModel.php';

if (!isset($_GET['ids'])) {
    http_response_code(400);
    exit('Aucun justificatif');
}

$rawIds = explode(',', $_GET['ids']);

$ids = [];

foreach ($rawIds as $id) {
    $id = (int) $id;
    if ($id > 0) {
        $ids[] = $id;
    }
}
$pdo = db();

$zip = new ZipArchive();
$tmpZip = tempnam(sys_get_temp_dir(), 'justifs_');

if ($zip->open($tmpZip, ZipArchive::CREATE) !== true) {
    http_response_code(500);
    exit('ZIP error');
}

foreach ($ids as $id) {
    $j = AbsenceModel::getJustificatifFile($id);

    if (!$j) continue;

    // Ajout direct depuis la base (aucun fichier temporaire)
    $zip->addFromString($j['original_filename'], $j['fichier']);
}

$zip->close();

header('Content-Type: application/zip');
header(
    'Content-Disposition: attachment; filename="justificatifs.zip"'
);
header('Content-Length: ' . filesize($tmpZip));

readfile($tmpZip);
unlink($tmpZip);
exit;
