<?php
require_once __DIR__ . '/../Model/HelpModel.php';

class HelpPresenter {
    private $model;

    public function __construct() {
        $this->model = new HelpModel();
    }

    public function getHelpMessage() {
        return $this->model->getHelp();
    }
}
