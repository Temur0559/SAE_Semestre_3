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
    public function marquer_absence_justifiee(int $justifId, $etat)
    { // RENOMMÉ

        $sql = "SELECT JustificatifAbsence.id_absence FROM JustificatifAbsence WHERE JustificatifAbsence.id_justificatif = :id";

        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $justifId]);

        $abs = $st->fetch();

        if (!$abs) {
            return;
        }

        $sql2 = "UPDATE Absence SET justification = :etat, commentaire = NULL WHERE id = :id";

        $st2 = $this->pdo->prepare($sql2);
        $st2->execute([':id' => $abs['id_absence'], ':etat' => $etat]);
    }

    public function marquer_absence(array $idsAbsence, $etat, $motif, $commentaire) {
        $etatValide = ['JUSTIFIEE', 'NON_JUSTIFIEE'];

        if(!in_array($etat, $etatValide)) {
            exit();
        }

        $valeurs = implode(',', array_fill(0, count($idsAbsence), '?'));

        $sql = "UPDATE Absence 
                SET justification = ?,
                    motif = ?,
                    commentaire = ?
                WHERE id in ($valeurs)";

        $st = $this->pdo->prepare($sql);

        $params = [$etat, $motif, $commentaire];
        foreach ($idsAbsence as $id) {
            $params[] = $id;
        }

        $st->execute($params);
    }

    // Insérer un nouveau fichier quand un justificatif est déverrouillé, cad, en révision (statut : à préciser)
    public function insertionNouveauFichier($idJustificatif, $fichier, $fichier_nom, $type_mime) {
        // Cloner le justificatif actuel
        $sql = "INSERT INTO justificatif (fichier, commentaire, motif_libre, id_utilisateur, nom_fichier_original, type_mime, verouille_date, date_debut_demande, date_fin_demande)
                (
                        SELECT fichier, commentaire, motif_libre, id_utilisateur, nom_fichier_original, type_mime, verouille_date, date_debut_demande, date_fin_demande
                        FROM justificatif
                        WHERE id = :id
                ) RETURNING id";

        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $idJustificatif]);

        $idJustificatif = $st->fetch()['id'];

        // Mettre a jour le fichier dans le clone
        $sql = "UPDATE justificatif
                SET fichier = :fichier,
                    nom_fichier_original = :fichier_nom,
                    type_mime = :type_mime
                WHERE id = :id";

        $st = $this->pdo->prepare($sql);

        $st->bindValue(':fichier', $fichier,$fichier === null ? \PDO::PARAM_NULL : \PDO::PARAM_LOB);
        $st->bindValue(':fichier_nom', $fichier_nom);
        $st->bindValue(':type_mime', $type_mime);
        $st->bindValue(':id', $idJustificatif);

        $st->execute();
    }

    public function cloneJustificatif($id) {
        $sql = "INSERT INTO justificatif (fichier, commentaire, motif_libre, id_utilisateur, nom_fichier_original, type_mime, verouille_date, date_debut_demande, date_fin_demande)
                (
                        SELECT fichier, commentaire, motif_libre, id_utilisateur, nom_fichier_original, type_mime, verouille_date, date_debut_demande, date_fin_demande
                        FROM justificatif
                        WHERE id = :id
                ) RETURNING id";

        $st = $this->pdo->prepare($sql);

        $st->execute(['id'=>$id]);

        return $st->fetch()['id'];
    }

    public function deplacerAbsenceJustificatifs($nonSelectionnes, $idJustificatif, $motif, $commentaire, $actuelID) {
        if (empty($nonSelectionnes)) {
            return;
        }

        $valeurs = implode(',', array_fill(0, count($nonSelectionnes), '?'));

        $sql = "UPDATE justificatifAbsence
                SET id_justificatif = ?
                WHERE id_justificatif = ? AND id_absence in ($valeurs)";

        $params = [$idJustificatif, $actuelID];
        foreach ($nonSelectionnes as $id) {
            $params[] = $id;
        }

        $st = $this->pdo->prepare($sql);

        $st->execute($params);
    }

}