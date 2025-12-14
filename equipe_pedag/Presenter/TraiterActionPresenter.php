<?php

require_once __DIR__ . '/../model/ActionModel.php';

class TraiterActionPresenter {

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


        // données envoyées
        $idJustificatif = (int)($_POST['id'] ?? 0);
        $actionDemandee = $_POST['action'] ?? '';
        $motif          = trim($_POST['motifDecision'] ?? ''); // Correction pour utiliser 'motifDecision' comme dans index.php
        $idAuteur       = 3; // responsable connecté
        $redirect       = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index.php');

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
            $this->actionModel->marquer_absence_justifiee($idJustificatif); // CORRIGÉ: Ancien nom était marquer_comme_justifiee
        }

        // Si l'action est REJET, le Presenter RejetPresenter est censé être appelé, mais par sécurité on pourrait ajouter le verrouillage ici, bien que l'action TraiterActionPresenter soit utilisée uniquement pour l'ACCEPTATION dans la vue IndexView fournie.
        if ($actionDemandee === 'REJET') {
            $this->actionModel->verrouiller($idJustificatif);
        }

        // redirection
        header('Location: ' . $redirect);
        exit;
    }
}