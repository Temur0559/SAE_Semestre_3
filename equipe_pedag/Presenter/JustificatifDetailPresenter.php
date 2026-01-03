<?php

require_once __DIR__ . '/../model/JustificatifInfosModel.php';
require_once __DIR__ . '/../model/detailHistoriqueModel.php';



class JustificatifDetailPresenter {

    private PDO $pdo;
    private JustificatifInfosModel $model;
    private detailHistoriqueModel $detailHistoriqueModel;

    public function __construct() {
        $this->pdo = db();
        $this->model = new JustificatifInfosModel($this->pdo);
        $this->detailHistoriqueModel = new detailHistoriqueModel($this->pdo);
    }

    public function handle() {

        // session_start(); // CORRIGÉ: Appel déplacé dans index.php

        // récup l'id du justificatif
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            die("ID du justificatif invalide.");
        }

        // recupération du justificatif
        $justif = $this->model->justificatif_id($id);

        if (!$justif) {
            die("Justificatif introuvable.");
        }

        $historiquedetail = $this->detailHistoriqueModel->detailParJustificatif($id);

        // affiche la vue
        require __DIR__ . '/../view/JustificatifDetailView.php';
        $vue = new JustificatifDetailView();
        $vue->render($justif, $historiquedetail);


    }
}