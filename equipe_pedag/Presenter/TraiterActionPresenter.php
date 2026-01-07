<?php

require_once __DIR__ . '/../model/ActionModel.php';

class TraiterActionPresenter {

    private PDO $pdo;
    private ActionModel $actionModel;
    private JustificatifInfosModel $justificatifInfosModel;

    public function __construct() {
        $this->pdo = db();

        $this->actionModel = new ActionModel($this->pdo);
        $this->justificatifInfosModel = new JustificatifInfosModel($this->pdo);
    }

    public function handle() {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: historique.php');
            exit;
        }

        // Données envoyées
        $idJustificatif = (int)($_POST['id'] ?? 0);
        $actionDemandee = $_POST['action'] ?? '';
        $selectionner = $_POST['selectionner'] ?? [];
        $selectionner = array_map('intval', $selectionner);

        $redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index.php');

        // Liste des actions autorisées
        $actionsAutorisees = ['SOUMISSION','DEMANDE_PRECISIONS','ACCEPTATION','REJET','AUTORISATION_RENVOI','AUTORISATION_HORS_DELAI'];

        if ($idJustificatif <= 0 || !in_array($actionDemandee, $actionsAutorisees, true)) {
            header('Location: ' . $redirect);
            exit;
        }

        $idAuteur = 3; // ID responsable connecté

        // --- Récupérer toutes les absences liées au justificatif ---
        $absences = $this->justificatifInfosModel->detailsJustificatif($idJustificatif);
        $absencesIds = [];
        foreach ($absences as $a) {
            $absencesIds[] = $a['absence_id'];
        }

        // Sélectionner uniquement les absences valides
        $selectionnes = [];
        $nonSelectionnes = [];
        foreach ($absencesIds as $a) {
            if(in_array($a, $selectionner)) {
                $selectionnes[] = $a;
            }
            else {
                $nonSelectionnes[] = $a;
            }
        }

        // --- Créer un nouveau justificatif pour les absences non sélectionnées ---
        if (!empty($nonSelectionnes)) {
            $nouveauJustificatifId = $this->actionModel->dupliquerJustificatif($idJustificatif);

            // Déplacer les absences non sélectionnées vers ce nouveau justificatif
            $this->actionModel->deplacerAbsences($nonSelectionnes, $nouveauJustificatifId, $idJustificatif);

            $action = $actionDemandee == 'ACCEPTATION' ? 'REJET' : 'ACCEPTATION';
            $this->actionModel->ajouter_decision(
                $nouveauJustificatifId,
                $action,
                'Absences non sélectionnées',
                $idAuteur
            );

            $this->actionModel->verrouiller($nouveauJustificatifId);
        }

        // Composer le motif pour le justificatif de base
        $motif = null;
        if ($actionDemandee === 'ACCEPTATION') {
            $motifPredefini = trim($_POST['motif_predefini'] ?? '');
            $commentaireAcc = trim($_POST['commentaire_acceptation'] ?? '');

            if ($motifPredefini === '') {
                header('Location: ' . $redirect);
                exit;
            }

            $motif = $motifPredefini;
            if ($commentaireAcc !== '') {
                $motif .= ' - ' . $commentaireAcc;
            }

        } elseif ($actionDemandee === 'REJET') {
            $motifDecision = trim($_POST['motifDecision'] ?? '');
            $motif = ($motifDecision !== '' ? $motifDecision : null);
        }

        // Appliquer la décision sur les absences sélectionnées
        foreach ($selectionnes as $absenceId) {
            if ($actionDemandee === 'ACCEPTATION') {
                $this->actionModel->marquer_absence($absenceId, 'JUSTIFIEE');
            } elseif ($actionDemandee === 'REJET') {
                $this->actionModel->marquer_absence($absenceId, 'NON_JUSTIFIEE');
            }
        }
        // Appliquer la décision inverse sur les non sélectionnées
        foreach($nonSelectionnes as $absenceId) {
            if($actionDemandee === 'ACCEPTATION') {
                $this->actionModel->marquer_absence($absenceId, 'NON_JUSTIFIEE');
            } else { // actionDemandee === 'REJET'
                $this->actionModel->marquer_absence($absenceId, 'JUSTIFIEE');
            }
        }


        // Ajouter la décision globale sur le justificatif original
        $this->actionModel->ajouter_decision(
            $idJustificatif,
            $actionDemandee,
            $motif,
            $idAuteur
        );

        // Verrouiller le justificatif original
        if (in_array($actionDemandee, ['ACCEPTATION', 'REJET'], true)) {
            $this->actionModel->verrouiller($idJustificatif);
        }

        // Déverrouiller pour certaines actions
        if (in_array($actionDemandee, ['DEMANDE_PRECISIONS', 'AUTORISATION_RENVOI', 'AUTORISATION_HORS_DELAI'], true)) {
            $this->actionModel->deverouille($idJustificatif);
        }

        // Redirection
        header('Location: ' . $redirect);
        exit;
    }


}