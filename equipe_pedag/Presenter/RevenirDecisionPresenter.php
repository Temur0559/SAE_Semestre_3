<?php

require_once __DIR__ . '/../model/ActionModel.php';

class RevenirDecisionPresenter {

    private PDO $pdo;
    private ActionModel $actionModel;

    public function __construct() {
        $this->pdo = db();

        $this->actionModel = new ActionModel($this->pdo);
    }

    public function handle() {

        // session_start(); // CORRIGÉ: Appel déplacé dans index.php


        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: historique.php');
            exit;
        }




        $idJustificatif = (int)($_POST['id'] ?? 0);
        $nouvelleAction = $_POST['action'] ?? '';
        $motif = trim($_POST['motif'] ?? '');
        $idAuteur = 3; // responsable connecté
        $redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index.php');

        // actions autorisées
        $possibles = [
            'SOUMISSION','DEMANDE_PRECISIONS','ACCEPTATION','REJET','AUTORISATION_RENVOI','AUTORISATION_HORS_DELAI'];

        if ($idJustificatif <= 0 || !in_array($nouvelleAction, $possibles, true)) {
            header('Location: '.$redirect);
            exit;
        }

        // mettre la decision dans l'historique
        $this->actionModel->ajouter_decision(
            $idJustificatif,
            $nouvelleAction,
            ($motif !== '' ? $motif : null),
            $idAuteur
        );


        if (in_array($nouvelleAction, [
            'DEMANDE_PRECISIONS',
            'AUTORISATION_RENVOI',
            'AUTORISATION_HORS_DELAI'
        ], true)) {
            // deverrouiller justificatif
            $this->actionModel->deverouille($idJustificatif);
        }

        if ($nouvelleAction === 'ACCEPTATION') {
            // marquer absence comme justifiée
            $this->actionModel->marquer_absence_justifiee($idJustificatif); // Utilisation de la méthode corrigée
        }

        if ($nouvelleAction === 'REJET') {
            // re verrouiller
            $this->actionModel->verrouiller($idJustificatif);
        }

        // 3) redirection
        header('Location: ' . $redirect);
        exit;
    }
}