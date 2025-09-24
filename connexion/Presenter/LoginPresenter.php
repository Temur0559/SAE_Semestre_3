<?php
require_once __DIR__ . '/../Model/UserModel.php';

class LoginPresenter {
    public function handleLogin($identifiant, $password) {
        $userModel = new UserModel();

        if ($userModel->login($identifiant, $password)) {
            header("Location: ../View/success.php");
        } else {
            header("Location: ../View/error.php");
        }
        exit;
    }
}
