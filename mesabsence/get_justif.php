<?php
declare(strict_types=1);

// Télécharger un fichier lié a un justificatif

session_start();
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_role('ETUDIANT');
require_once __DIR__ . '/../connexion/config/db.php';

$jid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($jid <= 0) {
    http_response_code(404);
    exit('Not found');
}

// RÉCUPÉRATION AVEC BASE64
$pdo = db();
$st = $pdo->prepare("
    SELECT encode(fichier, 'base64') AS fichier_base64,
           type_mime,
           nom_fichier_original
    FROM Justificatif
    WHERE id = :id
");
$st->execute([':id' => $jid]);
$j = $st->fetch();

if (!$j) {
    http_response_code(404);
    exit('Not found');
}

// Décoder le base64
$fileData = base64_decode($j['fichier_base64']);
$mime = $j['type_mime'] ?? 'application/octet-stream';
$name = $j['nom_fichier_original'] ?? 'justificatif_' . $jid;

// Envoyer les headers
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . addslashes($name) . '"');
header('Content-Length: ' . strlen($fileData));
header('Cache-Control: private, max-age=120');

// Envoyer le fichier
echo $fileData;
exit;