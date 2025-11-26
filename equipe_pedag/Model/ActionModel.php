<?php

class ActionModel {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }


    public function ajouter_decision(int $justifId, string $action, ?string $motif, int $auteur) {

        $sql = "INSERT INTO HistoriqueDecision (action, motif_decision, id_justificatif, id_auteur) VALUES (:action, :motif, :justif, :auteur)";

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':action' => $action,
            ':motif'  => ($motif !== '' ? $motif : null),
            ':justif' => $justifId,
            ':auteur' => $auteur
        ]);
    }

   
    // pour deverouille un justificatif 

    public function deverouille(int $id) {

        $sql = "UPDATE Justificatif SET verrouille = FALSE, verrouille_date = NULL, date_maj = NOW() WHERE id = :id";
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $id]);
    }



    // marque une absence justifiée quand on l'accepte dans la bdd
    public function absence_acceptee(int $justifId) {

        $sql = "SELECT JustificatifAbsence.id_absence FROM JustificatifAbsence WHERE JustificatifAbsence.id_justificatif = :id";

        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $justifId]);

        $abs = $st->fetch();

        if (!$abs) {
            return; 
        }

        $sql2 = "UPDATE Absence SET justification = 'JUSTIFIEE', commentaire = NULL WHERE id = :id";

        $st2 = $this->pdo->prepare($sql2);
        $st2->execute([':id' => $abs['id_absence']]);
    }
}

