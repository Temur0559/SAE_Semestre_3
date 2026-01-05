<?php

require_once __DIR__ . '/../model/FichierJustificatifModel.php';

class FichierJustificatifPresenter {

    private PDO $pdo;
    private FichierJustificatifModel $model;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->model = new FichierJustificatifModel($this->pdo);
    }

    public function handle() {
        // Récupération de l'id
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            http_response_code(400);
            exit("ID invalide");
        }

        // Récupère le document/fichier dans model
        $doc = $this->model->fichier_justif($id);

        if (!$doc) {
            http_response_code(404);
            exit("Fichier introuvable");
        }

        // Appel de la vue pour envoyer le fichier
        require __DIR__ . '/../view/FichierJustificatifView.php';
        $vue = new FichierJustificatifView();
        $vue->render($doc);
    }
}