<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_role('ETUDIANT');
require_once __DIR__ . '/Model/AbsenceModel.php';

$jid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($jid <= 0) { http_response_code(404); exit('Not found'); }

$j = AbsenceModel::getJustificatifFile($jid);
if (!$j) { http_response_code(404); exit('Not found'); }

$mime = $j['mime_type'] ? $j['mime_type'] : 'application/octet-stream';
$name = $j['original_filename'] ? $j['original_filename'] : ('justificatif_' . $j['id']);

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . addslashes($name) . '"');
echo $j['fichier'];
