<?php

require_once __DIR__ . '/../model/ActionModel.php';

class TraiterActionPresenter {

    private PDO $pdo;
    private ActionModel $actionModel;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->actionModel = new ActionModel($this->pdo);
    }

    public function handle() {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: historique.php');
            exit;
        }

        // données envoyées
        $idJustificatif = (int)($_POST['id'] ?? 0);
        $absenceId = (int)($_POST['absence_id'] ?? 0);
        $actionDemandee = $_POST['action'] ?? '';
        $motif          = trim($_POST['motifDecision'] ?? '');
        $idAuteur       = 3; // responsable connecté
        $redirect       = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index.php');

        // TRAITEMENT SPÉCIAL POUR PASSER_EN_REVISION_SPECIAL
        if ($actionDemandee === 'PASSER_EN_REVISION_SPECIAL') {
            if ($idJustificatif > 0) {
                // Déverrouiller le justificatif pour permettre à l'étudiant de renvoyer
                $this->actionModel->deverouille($idJustificatif);

                // Enregistrer l'action DEMANDE_PRECISIONS dans l'historique
                // Cela fera automatiquement apparaître l'absence dans l'onglet "En révision"
                $this->actionModel->ajouter_decision(
                    $idJustificatif,
                    'DEMANDE_PRECISIONS',
                    ($motif !== '' ? $motif : 'Demande de révision du justificatif'),
                    $idAuteur
                );
            }
            header('Location: ' . $redirect);
            exit;
        }

        // TRAITEMENT NORMAL POUR LES AUTRES ACTIONS

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
            $idAuteur
        );

        if (
            $actionDemandee === 'DEMANDE_PRECISIONS' ||
            $actionDemandee === 'AUTORISATION_RENVOI' ||
            $actionDemandee === 'AUTORISATION_HORS_DELAI'
        ) {
            $this->actionModel->deverouille($idJustificatif);
        }

        if ($actionDemandee === 'ACCEPTATION') {
            $this->actionModel->marquer_absence_justifiee($idJustificatif);
        }

        if ($actionDemandee === 'REJET') {
            $this->actionModel->verrouiller($idJustificatif);
        }

        // redirection
        header('Location: ' . $redirect);
        exit;
    }
}