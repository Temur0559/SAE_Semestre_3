<?php
class PasswordModel {
    public function resetPassword($email) {

        return "Un email de réinitialisation a été envoyé à " . htmlspecialchars($email);
    }
}
