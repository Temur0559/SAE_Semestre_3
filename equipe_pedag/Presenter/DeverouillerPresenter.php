<?php

require_once __DIR__ . '/../model/ActionModel.php';

// recoit la requête du bouton quand on clique sur deverouiller
class DeverrouillerPresenter {

    private PDO $pdo;
    private ActionModel $actionModel;

    public function __construct() {
        require __DIR__ . '/../config/db.php';
        $this->pdo = $pdo;

        $this->actionModel = new ActionModel($this->pdo);
    }

    public function handle() {

        session_start();

        // verification pour accepter uniquement les requetes post
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
        }

        // verif du csrf
        $csrf = $_POST['csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
            http_response_code(400);
            exit("erreur de requête");
        }

        // verif de l'id du justificatif
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: index.php');
            exit;
        }

        // si tout est bon :
        $this->actionModel->deverouille($id); // on appelle la methode dans model qui va executer la requete sql

        //Redirection la page precedente ou vers l'index
        $redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index.php');
        header('Location: ' . $redirect);
        exit;
    }
}
