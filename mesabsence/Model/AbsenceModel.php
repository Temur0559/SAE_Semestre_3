<?php

class AbsenceModel {
    /** @var string */
    private $file;

    public function __construct($file = null) {
        $this->file = $file ?: __DIR__ . '/../storage/absences.json';

        if (!file_exists($this->file)) {
            if (!is_dir(dirname($this->file))) {
                mkdir(dirname($this->file), 0777, true);
            }
            file_put_contents($this->file, json_encode([
                "identity" => [
                    "nom"      => "Pequin",
                    "prenom"   => "Arthur",
                    "naissance"=> "Né(e) le 12/02/2005",
                    "ine"      => "INE",
                    "program"  => "BUT INFORMATIQUE - 2ème année - FI"
                ],
                "absences" => []
            ], JSON_PRETTY_PRINT));
        }
    }


    public function getAll(): array {
        $content = @file_get_contents($this->file);
        $data = $content ? json_decode($content, true) : null;


        if (is_array($data) && isset($data[0]) && !isset($data['identity'])) {
            $data = [
                "identity" => [
                    "nom"      => "Pequin",
                    "prenom"   => "Arthur",
                    "naissance"=> "Né(e) le 12/02/2005",
                    "ine"      => "INE",
                    "program"  => "BUT INFORMATIQUE - 2ème année - FI"
                ],
                "absences" => $data
            ];
            $this->save($data);
        }

        if (!is_array($data))               $data = ["identity"=>[], "absences"=>[]];
        if (!isset($data["identity"]))      $data["identity"] = [];
        if (!isset($data["absences"]))      $data["absences"] = [];
        return $data;
    }

    public function save(array $data) {
        file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function getIdentity(): array {
        return $this->getAll()["identity"];
    }

    public function getAbsences(): array {
        return $this->getAll()["absences"];
    }


    public function setJustificatif($index, $filename) {
        $data = $this->getAll();
        if (isset($data["absences"][$index])) {
            $data["absences"][$index]["justificatif"] = (string)$filename;
            $this->save($data);
        }
    }


    public function advanceStatusesOnSave() {
        $data = $this->getAll();
        if (!isset($data['absences']) || !is_array($data['absences'])) return;

        foreach ($data['absences'] as &$a) {
            $statut    = strtolower($a['statut']);
            $hasJustif = !empty($a['justificatif']);

            if ($hasJustif && ($statut === 'rejeté' || $statut === 'rejete')) {
                $a['statut'] = 'En révision';
            }
        }
        unset($a);
        $this->save($data);
    }
}
