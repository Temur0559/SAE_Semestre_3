<?php

require_once __DIR__ . '/../model/JustificatifInfosModel.php';

class JustificatifDetailPresenter {

    private PDO $pdo;
    private JustificatifInfosModel $model;

    public function __construct() {
        require __DIR__ . '/../config/db.php';
        $this->pdo = $pdo;

        $this->model = new JustificatifInfosModel($this->pdo);
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

        // affiche la vue
        require __DIR__ . '/../view/JustificatifDetailView.php';
        $vue = new JustificatifDetailView();
        $vue->render($justif);
    }
}