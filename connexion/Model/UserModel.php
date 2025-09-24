<?php

class UserModel {
    private $users = [];

    public function __construct() {

        $this->users = [
            "admin" => "admin123"
        ];
    }

    public function register($identifiant, $password) {

        if (!isset($this->users[$identifiant])) {
            $this->users[$identifiant] = $password;
            return true;
        }
        return false;
    }

    public function login($identifiant, $password) {
        return isset($this->users[$identifiant]) && $this->users[$identifiant] === $password;
    }
}
