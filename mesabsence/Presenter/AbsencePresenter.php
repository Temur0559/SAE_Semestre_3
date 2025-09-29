<?php
require_once __DIR__ . '/../Model/AbsenceModel.php';

class AbsencePresenter {
    /** @var AbsenceModel */
    private $model;

    public function __construct() {
        $this->model = new AbsenceModel();
    }

    public function getIdentity(): array {
        return $this->model->getIdentity();
    }

    public function getAbsences(): array {
        return $this->model->getAbsences();
    }

    public function enregistrerJustificatif($index, $filename) {
        $this->model->setJustificatif($index, $filename);
    }

    public function enregistrer() {
        $this->model->advanceStatusesOnSave();
    }
}
