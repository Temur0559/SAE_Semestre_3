<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_role('ETUDIANT');
require_once __DIR__ . '/Model/AbsenceModel.php';

$userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
if ($userId <= 0) { header('Location: /connexion/View/login.php'); exit; }

$absenceId = isset($_POST['absence_id']) ? (int)$_POST['absence_id'] : 0;
$file      = isset($_FILES['justificatif']) ? $_FILES['justificatif'] : null;

if ($absenceId <= 0 || !$file || $file['error'] !== UPLOAD_ERR_OK) {
    header('Location: ./index.php'); exit;
}


$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) { header('Location: ./index.php'); exit; }

$original = (string)$file['name'];
$mime     = (string)($file['type'] ?: 'application/octet-stream');
$blob     = file_get_contents($file['tmp_name']);

try {
    AbsenceModel::insertJustificatif($absenceId, $userId, $original, $mime, $blob);
} catch (Throwable $e) {

    http_response_code(500);
    echo "<pre>Upload error: " . $e->getMessage() . "</pre>";
    exit;
}


$qs = isset($_GET['filtre']) ? '?filtre=' . urlencode($_GET['filtre']) : '';
header('Location: ./index.php' . $qs);
exit;
