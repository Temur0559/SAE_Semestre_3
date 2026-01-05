<?php

class FichierJustificatifModel {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Pour récupération du fichier d'un justificatif
    public function fichier_justif(int $id): ?array {
        // Récupérer le fichier encodé en base64
        $sql = "SELECT encode(fichier, 'base64') AS fichier_base64, 
                   type_mime, 
                   nom_fichier_original 
            FROM Justificatif 
            WHERE id = :id";

        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $id]);
        $row = $st->fetch();

        if (!$row) return null;

        // Décoder le base64 en données binaires
        $row['fichier'] = base64_decode($row['fichier_base64']);
        unset($row['fichier_base64']); // Nettoyer

        return $row;
    }
}