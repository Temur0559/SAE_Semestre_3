<?php

require_once __DIR__ . '/../model/ActionModel.php';

// recoit la requête du bouton quand on clique sur deverouiller
class DeverouillerPresenter {

    private PDO $pdo;
    private ActionModel $actionModel;

    public function __construct() {
        $this->pdo = db();

        $this->actionModel = new ActionModel($this->pdo);
    }

    public function handle() {

        // session_start(); // CORRIGÉ: Appel déplacé dans index.php

        // verification pour accepter uniquement les requetes post
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
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