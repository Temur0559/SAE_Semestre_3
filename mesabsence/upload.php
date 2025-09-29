<?php
require_once __DIR__ . '/Presenter/AbsencePresenter.php';

$presenter = new AbsencePresenter();

$id   = isset($_POST['id']) ? (int)$_POST['id'] : null;
$file = isset($_FILES['justificatif']) ? $_FILES['justificatif'] : null;

if ($id === null || !$file || $file['error'] !== UPLOAD_ERR_OK) {
    header('Location: index.php'); exit;
}

$maxSize  = 10 * 1024 * 1024;
$allowed  = ['pdf','jpg','jpeg','png','gif','webp'];
$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed) || $file['size'] > $maxSize) {
    header('Location: index.php'); exit;
}

$dir = __DIR__ . '/uploads';
if (!is_dir($dir)) mkdir($dir, 0777, true);

$san  = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
$name = 'justif_' . time() . '_' . $san;
$dest = $dir . '/' . $name;

if (move_uploaded_file($file['tmp_name'], $dest)) {
    $presenter->enregistrerJustificatif($id, $name);
}
header('Location: index.php'); exit;
