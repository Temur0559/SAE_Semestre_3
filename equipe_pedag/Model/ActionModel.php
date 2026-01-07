<?php

class ActionModel
{

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    public function ajouter_decision(int $justifId, string $action, ?string $motif, int $auteur): void
    {

        $sql = "INSERT INTO HistoriqueDecision (action, motif_decision, id_justificatif, id_auteur) VALUES (:action, :motif, :justif, :auteur)";

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':action' => $action,
            ':motif' => ($motif !== '' ? $motif : null),
            ':justif' => $justifId,
            ':auteur' => $auteur
        ]);
    }


    // pour deverouille un justificatif 

    public function deverouille(int $id)
    {

        $sql = "UPDATE Justificatif SET verouille = FALSE, verouille_date = NULL, date_maj = NOW() WHERE id = :id"; // CORRIGÉ
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $id]);
    }


    // pour verrouiller un justificatif (Ajouté)

    public function verrouiller(int $id)
    {

        $sql = "UPDATE Justificatif SET verouille = TRUE, verouille_date = NOW(), date_maj = NOW() WHERE id = :id"; // CORRIGÉ
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $id]);
    }


    // marque une absence justifiée quand on l'accepte dans la bdd (Renommée en marquer_absence_justifiee)
    public function marquer_absence_justifiee(int $justifId)
    { // RENOMMÉ

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

    public function marquer_absence(int $idAbsence, $etat) {
        $etatValide = ['JUSTIFIEE', 'NON_JUSTIFIEE'];

        if(!in_array($etat, $etatValide)) {
            exit();
        }

        $sql = "UPDATE Absence SET justification = :etat WHERE id = :id";

        $st = $this->pdo->prepare($sql);

        $st->execute([':id' => $idAbsence, ':etat' => $etat]);
    }

    // marque une absence rejetée quand on l'accepte dans la bdd (Renommée en marquer_absence_justifiee)
    public function marquer_absence_rejetee(int $justifId)
    {

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

    public function dupliquerJustificatif($idJustificatif) {
        $sql = "INSERT INTO justificatif (fichier, commentaire, motif_libre, id_utilisateur, nom_fichier_original, type_mime, verouille_date, date_debut_demande, date_fin_demande)
                (
                        SELECT fichier, commentaire, motif_libre, id_utilisateur, nom_fichier_original, type_mime, verouille_date, date_debut_demande, date_fin_demande
                        FROM justificatif
                        WHERE id = :id
                ) RETURNING id";

        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $idJustificatif]);

        return $st->fetch()['id'];
    }

    public function deplacerAbsences($nonSelectionnes, $nouveauJustificatifId, $ancienJustificatifId) {
        if (empty($nonSelectionnes)) {
            return;
        }

        $valeurs = implode(',', array_fill(0, count($nonSelectionnes), '?'));

        $sql = "UPDATE justificatifabsence
                SET id_justificatif = ?
                WHERE id_justificatif = ?
                    AND id_absence IN ($valeurs)
                ";

        $st = $this->pdo->prepare($sql);

        $params = [$nouveauJustificatifId, $ancienJustificatifId];
        foreach ($nonSelectionnes as $id) {
            $params[] = $id;
        }

        $st->execute($params);
    }

}