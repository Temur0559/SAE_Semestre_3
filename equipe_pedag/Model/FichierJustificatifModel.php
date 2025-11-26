<?php

class FichierJustificatifModel {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }



    // pour récuperation du fichier d'un justificatif

    public function fichier_justif(int $id) {
        $sql = " SELECT fichier, type_mime, nom_fichier_original FROM Justificatif WHERE id = :id";

        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $id]);
        return $st->fetch();
    }
}