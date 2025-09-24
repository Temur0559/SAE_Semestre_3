<?php
require_once __DIR__ . '/../Model/UserModel.php';

class UserPresenter {
    public function handleLogin($identifiant, $password) {
        $userModel = new UserModel();

        if ($userModel->checkUser($identifiant, $password)) {
            header("Location: ../View/success.php");
        } else {
            header("Location: ../View/error.php");
        }
        exit;
    }
}
