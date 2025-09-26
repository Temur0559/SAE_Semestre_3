<?php
require_once __DIR__ . '/../Model/UserModel.php';

class RegisterPresenter {
    public function handleRegister($identifiant, $password) {
        $userModel = new UserModel();

        if ($userModel->register($identifiant, $password)) {
            header("Location: ../View/success.php");
        } else {
            header("Location: ../View/error.php");
        }
        exit;
    }
}
