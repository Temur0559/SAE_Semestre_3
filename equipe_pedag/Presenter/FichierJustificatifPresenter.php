<?php

require_once __DIR__ . '/../model/FichierJustificatifModel.php';



class FichierJustificatifPresenter {

    private PDO $pdo;
    private FichierJustificatifModel $model;

    public function __construct() {
        require __DIR__ . '/../config/db.php';
        $this->pdo = $pdo;

        $this->model = new FichierJustificatifModel($this->pdo);
    }


    public function handle() {

        // recupération de l'id
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            exit("id invalide");
        }

        // recupère le document/fichier dans model
        $doc = $this->model->fichier_justif($id);
        if (!$doc) {
            exit("fichier introuvable");
        }

        // appel de la vue pr envoyer le fichier
        require __DIR__ . '/../view/FichierJustificatifView.php';
        $vue = new FichierJustificatifView();
        $vue->render($doc);
    }
}