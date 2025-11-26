<?php

require_once __DIR__ . '/../model/ActionModel.php';

class RevenirDecisionPresenter {

    private PDO $pdo;
    private ActionModel $actionModel;

    public function __construct() {
        require __DIR__ . '/../config/db.php';
        $this->pdo = $pdo;

        $this->actionModel = new ActionModel($this->pdo);
    }

    public function handle() {

        session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: historique.php');
            exit;
        }

        // vérification csrf
        $csrf = $_POST['csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
            exit("Requête invalide (CSRF)");
        }

        // données envoyées
        $idJustificatif = (int)($_POST['id'] ?? 0);
        $actionDemandee = $_POST['action'] ?? '';
        $motif          = trim($_POST['motif'] ?? '');
        $idAuteur       = 3; // responsable connecté
        $redirect       = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'historique.php');

        // liste des actions autorisées
        $actionsAutorisees = [
            'SOUMISSION','DEMANDE_PRECISIONS','ACCEPTATION','REJET','AUTORISATION_RENVOI','AUTORISATION_HORS_DELAI'];

        if ($idJustificatif <= 0 || !in_array($actionDemandee, $actionsAutorisees, true)) {
            header('Location: '.$redirect);
            exit;
        }

        
        $this->actionModel->ajouter_decision(
            $idJustificatif,
            $actionDemandee,
            ($motif !== '' ? $motif : null),
            $idAuteur);

        
        if (
            $actionDemandee === 'DEMANDE_PRECISIONS' ||
            $actionDemandee === 'AUTORISATION_RENVOI' ||
            $actionDemandee === 'AUTORISATION_HORS_DELAI'
        ) {
            $this->actionModel->deverouille($idJustificatif);
        }

        if ($actionDemandee === 'ACCEPTATION') {
            $this->actionModel->marquer_comme_justifiee($idJustificatif);
        }

        // redirection
        header('Location: ' . $redirect);
        exit;
    }
}

