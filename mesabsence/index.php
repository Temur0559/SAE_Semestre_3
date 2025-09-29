<?php
require_once __DIR__ . '/Presenter/AbsencePresenter.php';

$presenter = new AbsencePresenter();
$identity  = $presenter->getIdentity();
$absences  = $presenter->getAbsences();


function norm($s){
    $s = strtolower($s);
    $s = str_replace(['é','è','ê'], 'e', $s);
    return trim($s);
}
$filtre = isset($_GET['filtre']) ? $_GET['filtre'] : 'tous';
if ($filtre && norm($filtre) !== 'tous') {
    $f = norm($filtre);
    $absences = array_values(array_filter($absences, function($a) use ($f){
        $st = strtolower($a['statut']);
        $st = str_replace(['é','è','ê'], 'e', $st);
        return $st === $f;
    }));
}

include __DIR__ . '/View/liste.php';
