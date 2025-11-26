<?php

require_once __DIR__ . '/../model/ActionModel.php';

class VerrouillerPresenter {

    private PDO $pdo;
    private ActionModel $actionModel;

    public function __construct() {
        require __DIR__ . '/../config/db.php';
        $this->pdo = $pdo;

        $this->actionModel = new ActionModel($this->pdo);
    }

    public function handle() {

        session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
        }

        // vérification CSRF
        $csrf = $_POST['csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
            exit("erreur");
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
