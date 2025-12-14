<?php

require_once __DIR__ . '/../model/ActionModel.php';

class RejetPresenter {

    private PDO $pdo;
    private ActionModel $actionModel;

    public function __construct() {
        $this->pdo = db();

        $this->actionModel = new ActionModel($this->pdo);
    }

    public function handle() {

        // session_start(); // CORRIGÉ: Appel déplacé dans index.php


        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
        }


        $idJustificatif = (int)($_POST['id'] ?? 0);
        $motif = trim($_POST['motifDecision'] ?? '');
        $idAuteur = 3; // responsable connecté

        if ($idJustificatif <= 0) {
            header('Location: index.php');
            exit;
        }

        if ($motif === '') {
            $motif = 'Non précisé';
        }

        // ajouter le rejet dans l'historique
        $this->actionModel->ajouter_decision(
            $idJustificatif,'REJET',$motif,$idAuteur);

        // verrouiller le justificatif
        $this->actionModel->verrouiller($idJustificatif);

        // redirection
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header('Location: '.$redirect);
        exit;
    }
}