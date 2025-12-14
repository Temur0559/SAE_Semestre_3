<?php

require_once __DIR__ . '/../model/ActionModel.php';

class PrecisionsPresenter {

    private PDO $pdo;
    private ActionModel $actionModel;

    public function __construct() {
        $this->pdo = db();

        $this->actionModel = new ActionModel($this->pdo);
    }

    public function handle() {

        // session_start(); // CORRIGÉ: Appel déplacé dans index.php

        // accepte uniquement POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
        }


        // données envoyées par le formulaire
        $idJustificatif = (int)($_POST['id'] ?? 0);
        $messagePrecisions = trim($_POST['message'] ?? '');
        $idAuteur = 3;   // responsable connecté (à améliorer plus tard)

        if ($idJustificatif <= 0) {
            header('Location: index.php');
            exit;
        }

        if ($messagePrecisions === '') {
            $messagePrecisions = 'Ajouter des précisions';
        }

        // enregistrer l'action dans l'historique
        $this->actionModel->ajouter_decision(
            $idJustificatif,'DEMANDE_PRECISIONS',$messagePrecisions,$idAuteur);

        // deverrouiller le justificatif pour permettre renvoi
        $this->actionModel->deverouille($idJustificatif);

        // redirection
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header('Location: ' . $redirect);
        exit;
    }
}