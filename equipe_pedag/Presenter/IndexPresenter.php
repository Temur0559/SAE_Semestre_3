<?php

require_once __DIR__ . '/../Model/JustificatifInfos.php';

class IndexPresenter {

    private PDO $pdo;
    private JustificatifInfosModel $model;

    public function __construct(PDO $pdo) {


        $this->pdo = $pdo;

        $this->model = new JustificatifInfosModel($this->pdo);
    }

    public function handle() {

        // session_start() a été déplacé dans index.php

        // crs
        $token = $_SESSION['csrf'] ?? null;

        // recup toutes les absences + dernier justificatif + dernière décision
        $rowsAll = $this->model->AbsencesDetails();

        // onglet actif
        $ongletActif = $_GET['ongletActif'] ?? 'en_attente';

        // compteurs
        $compteurs = [
            'en_attente'  => 0,'en_revision' => 0, 'accepte' => 0, 'rejete' => 0];

        // stock le resultat du filtre
        $justificatifsFiltres = [];


        foreach ($rowsAll as $ligne) {

            if (empty($ligne['id'])) continue;

            $statut = $this->action_en_statut($ligne['action'] ?? null);

            $compteurs[$statut]++;

            if ($statut === $ongletActif) {
                $justificatifsFiltres[] = $ligne;
            }
        }

        // sélection d'une absence
        $idAbs = isset($_GET['abs']) ? (int)$_GET['abs'] : 0;
        $selected = null;

        foreach ($justificatifsFiltres as $ligne) {
            if ($ligne['absence_id'] === $idAbs) {
                $selected = $ligne;
                break;
            }
        }

        // CORRECTION DE LA LIGNE 63 : Changement de 'IndexView.php' à 'index.php'
        require __DIR__ . '/../View/index.php';
        $view = new IndexView();
        $view->render($justificatifsFiltres, $compteurs, $ongletActif, $selected, $token);
    }

    private function action_en_statut(?string $action): string {
        return match($action){
            'ACCEPTATION' => 'accepte',
            'REJET'       => 'rejete',
            'DEMANDE_PRECISIONS',
            'RENVOI_FICHIER',
            'AUTORISATION_RENVOI' => 'en_revision',

            default => 'en_attente'
        };
    }
}