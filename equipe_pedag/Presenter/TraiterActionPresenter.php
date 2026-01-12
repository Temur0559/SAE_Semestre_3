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
        $redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index.php');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $redirect);
            exit;
        }

        // Données envoyées
        $idJustificatif = (int)$_POST['main_id'] ?? -1;
        $etudiant_id = $_POST['etudiant_id'] ?? '';
        $date_debut_demande = $_POST['date_debut_demande'] ?? '';
        $date_fin_demande = $_POST['date_fin_demande'] ?? '';

        $commentaire_acceptation = $_POST['commentaire_acceptation'] ?? '';
        $commentaire_rejet = $_POST['commentaire_rejet'] ?? '';
        $motif_accept = $_POST['motif_accept'] ?? '';
        $motif_refus = $_POST['motif_refus'] ?? '';



        $actionDemandee = $_POST['action'] ?? '';
        $selectionner = $_POST['selectionner'] ?? [];
        $selectionner = array_map('intval', $selectionner);

        // Liste des actions autorisées
        $actionsAutorisees = ['DEMANDE_PRECISIONS','ACCEPTATION','REJET'];

        if ($idJustificatif < 0 ||
            $etudiant_id == '' ||
            $date_debut_demande == '' ||
            $date_fin_demande == '' ||
            !in_array($actionDemandee, $actionsAutorisees, true)
        ) {
            header('Location: ' . $redirect);
            exit;
        }

        $idAuteur = $_SESSION['user']['id']; // ID responsable connecté

        if($actionDemandee == 'DEMANDE_PRECISIONS') {
            $this->actionModel->deverouille($idJustificatif);
        }
        else {
            // --- Récupérer toutes les absences liées au justificatif ---
            $absences = $this->justificatifInfosModel->detailsJustificatif($etudiant_id, $date_debut_demande, $date_fin_demande);

            $absencesIds = [];
            foreach ($absences['listAbs'] as $a) {
                $absencesIds[] = $a['absence_id'];
            }

            // Obtenir les absences selectionnées et non selectionnées
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

            $accepter = $actionDemandee == 'ACCEPTATION' ? $selectionnes : $nonSelectionnes;
            $refuser = $actionDemandee == 'ACCEPTATION' ? $nonSelectionnes : $selectionnes;

            if(count($accepter) != 0 && $motif_accept == '') {
                header('Location: ' . $redirect);
                exit;
            }
            if(count($refuser) != 0 && $motif_refus == '') {
                header('Location: ' . $redirect);
                exit;
            }

            if(count($refuser) != 0) {
                $this->actionModel->marquer_absence($refuser, 'NON_JUSTIFIEE', $motif_refus, $commentaire_rejet);
                $id = $this->actionModel->cloneJustificatif($idJustificatif);

                var_export($id);

                $this->actionModel->deplacerAbsenceJustificatifs($refuser, $id, $motif_refus, $commentaire_rejet, $idJustificatif);

                if($commentaire_rejet != '') {
                    $commentaire_rejet = $motif_refus . ' - ' . $commentaire_rejet;
                }
                else {
                    $commentaire_rejet = $motif_refus;
                }

                $this->actionModel->ajouter_decision(
                    $id,
                    'REJET',
                    $commentaire_rejet,
                    $idAuteur
                );

                $this->actionModel->verrouiller($id);
            }
            if(count($accepter) != 0) {
                $this->actionModel->marquer_absence($accepter, 'JUSTIFIEE', $motif_accept, $commentaire_acceptation);
            }

            if($commentaire_acceptation != '') {
                $commentaire_acceptation = $motif_accept . ' - ' . $commentaire_acceptation;
            }
            else {
                $commentaire_acceptation = $motif_accept;
            }

            // Ajouter la décision globale sur le justificatif original
            $this->actionModel->ajouter_decision(
                $idJustificatif,
                'ACCEPTATION',
                $commentaire_acceptation,
                $idAuteur
            );

            $this->actionModel->verrouiller($idJustificatif);
        }

        // Redirection
        header('Location: ' . $redirect);
        exit;
    }


}