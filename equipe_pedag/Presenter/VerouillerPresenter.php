<?php

require_once __DIR__ . '/../model/ActionModel.php';

class VerouillerPresenter { // CORRIGÉ

    private PDO $pdo;
    private ActionModel $actionModel;

    public function __construct() {
        require __DIR__ . '/../config/db.php';
        $this->pdo = $pdo;

        $this->actionModel = new ActionModel($this->pdo);
    }

    public function handle() {

        // session_start(); // CORRIGÉ: Appel déplacé dans index.php

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
        }



        // id du justificatif
        $idJustificatif = (int)($_POST['id'] ?? 0);
        if ($idJustificatif <= 0) {
            header('Location: index.php');
            exit;
        }


        $this->actionModel->verrouiller($idJustificatif);

        // redirection
        $redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index.php');
        header('Location: ' . $redirect);
        exit;
    }
}