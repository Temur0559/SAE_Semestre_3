<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../connexion/Presenter/require_role.php';
require_role('RESPONSABLE');

require_once __DIR__ . '/Model/StatistiquesModel.php';

$student_id = isset($_GET['student_id']) && $_GET['student_id'] !== '' ? (int)$_GET['student_id'] : null;
$selected_student = null;

$students = StatistiquesModel::getStudents();
$programmes = StatistiquesModel::getProgrammes();
$enseignements = StatistiquesModel::getAllEnseignements();

$periode_rapide = $_GET['periode_rapide'] ?? '';
$start_date = $_GET['start_date'] ?? null;
$end_date   = $_GET['end_date'] ?? null;

$type_seance = $_GET['type_seance'] ?? null;
if ($type_seance === '') $type_seance = null;

$enseignement_id = isset($_GET['enseignement_id']) && $_GET['enseignement_id'] !== '' ? (int)$_GET['enseignement_id'] : null;

if ($periode_rapide === 'S3') {
    $start_date = '2025-09-01';
    $end_date = '2026-01-31';
} elseif ($periode_rapide === 'S4') {
    $start_date = '2026-02-01';
    $end_date = '2026-06-30';
} elseif ($periode_rapide === 'ANN') {
    $start_date = '2025-09-01';
    $end_date = date('Y-m-d');
}

if (isset($_GET['start_date']) && $_GET['start_date'] !== '') {
    $start_date = $_GET['start_date'];
}
if (isset($_GET['end_date']) && $_GET['end_date'] !== '') {
    $end_date = $_GET['end_date'];
}

$absencesParType = [];
$absencesParRessource = [];
$evolutionAbsences = [];
$totalUnexcusedAbsences = 0;

if ($student_id !== null) {
    foreach ($students as $s) {
        if ((int)$s['id'] === $student_id) {
            $selected_student = $s;
            break;
        }
    }

    $absencesParType = StatistiquesModel::getAbsencesByType($start_date, $end_date, $student_id, null, $enseignement_id);
    $absencesParRessource = StatistiquesModel::getAbsencesByRessource($start_date, $end_date, $student_id, $type_seance, null);
    $evolutionAbsences = StatistiquesModel::getAbsencesEvolution($start_date, $end_date, $student_id, $type_seance, null, $enseignement_id);
    $totalUnexcusedAbsences = StatistiquesModel::getTotalUnexcusedAbsences($student_id);
}

require_once __DIR__ . '/View/statistiques_etudiant.php';
?>