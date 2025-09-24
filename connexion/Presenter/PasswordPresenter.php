<?php
require_once __DIR__ . '/../Model/UserModel.php';

class PasswordPresenter {
    public function handlePasswordReset($identifiant) {
        $userModel = new UserModel();

        if ($userModel->exists($identifiant)) {

            header("Location: ../View/success.php");
        } else {
            header("Location: ../View/error.php");
        }
        exit;
    }
}
