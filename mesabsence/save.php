<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_role('ETUDIANT');


$qs = isset($_POST['filtre']) ? ('?filtre=' . urlencode($_POST['filtre'])) : '';
header('Location: ./index.php' . $qs);
exit;
