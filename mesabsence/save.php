<?php
require_once __DIR__ . '/Presenter/AbsencePresenter.php';

$presenter = new AbsencePresenter();
$presenter->enregistrer();


$filtre = isset($_POST['filtre']) ? $_POST['filtre'] : '';
$qs = $filtre ? ('?filtre=' . urlencode($filtre)) : '';
header('Location: index.php' . $qs);
exit;
