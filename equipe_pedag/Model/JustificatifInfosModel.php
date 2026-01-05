<?php

// affiche les infos liés à un justificatif en détail

class JustificatifInfosModel {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // recupere les absences + justificatif le plus récent + dernière décision
    public function AbsencesDetails(): array {

        $sql = "
        WITH dernier_justificatif AS (
        SELECT DISTINCT ON (JustificatifAbsence.id_absence) 
        JustificatifAbsence.id_absence,
        Justificatif.id AS id_justificatif,
        Justificatif.nom_fichier_original,
        Justificatif.type_mime,
        Justificatif.date_soumission,
                Justificatif.verouille,       -- CORRIGÉ: verouille (un seul 'r')
                Justificatif.verouille_date,   -- CORRIGÉ: verouille_date (un seul 'r')
                Justificatif.date_debut_demande,
                Justificatif.date_fin_demande

            FROM JustificatifAbsence
            JOIN Justificatif ON Justificatif.id = JustificatifAbsence.id_justificatif
            ORDER BY
            JustificatifAbsence.id_absence,
            Justificatif.date_soumission DESC,
            Justificatif.id DESC
        ),

        derniere_decision AS (

            SELECT DISTINCT ON (HistoriqueDecision.id_justificatif)
                HistoriqueDecision.id_justificatif,
                HistoriqueDecision.action,
                HistoriqueDecision.motif_decision,
                HistoriqueDecision.date_action

            FROM HistoriqueDecision

            ORDER BY
                HistoriqueDecision.id_justificatif,
                HistoriqueDecision.date_action DESC,
                HistoriqueDecision.id DESC
        )

        SELECT

    Absence.id AS absence_id,

    Utilisateur.id        AS etudiant_id,
    Utilisateur.identifiant   AS etu_identifiant,
    Utilisateur.nom           AS etu_nom,
    Utilisateur.prenom        AS etu_prenom,
    Utilisateur.date_naissance,

    Seance.date AS cours_date,
    Seance.heure AS cours_heure,
    (Seance.date + Seance.heure::interval + Seance.duree) AS cours_fin,

    dernier_justificatif.id_justificatif AS id,
    dernier_justificatif.nom_fichier_original,
    dernier_justificatif.type_mime,
    dernier_justificatif.date_soumission,
    dernier_justificatif.verouille,     -- CORRIGÉ
    dernier_justificatif.verouille_date,  -- CORRIGÉ
    dernier_justificatif.date_debut_demande,
    dernier_justificatif.date_fin_demande,

    derniere_decision.action,
    derniere_decision.motif_decision


        FROM Absence
        JOIN Utilisateur
            ON Utilisateur.id = Absence.id_utilisateur

        LEFT JOIN Seance
            ON Seance.id = Absence.id_seance

        LEFT JOIN dernier_justificatif
            ON dernier_justificatif.id_absence = Absence.id

        LEFT JOIN derniere_decision
            ON derniere_decision.id_justificatif = dernier_justificatif.id_justificatif

        ORDER BY
            Seance.date DESC NULLS LAST,
            Absence.id DESC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }
    public function detailsJustificatif(int $idJustificatif): array
    {
        $sql = "SELECT
        j.id,
            j.nom_fichier_original,
            j.type_mime,
            j.date_soumission,
            j.date_debut_demande,
            j.date_fin_demande,
            j.motif_libre,
            j.verouille,
            j.verouille_date,

            u.id AS etudiant_id,
            u.nom,
            u.prenom,
            u.identifiant,

            a.id AS absence_id,
            s.id AS seance_id,
            s.date,
            s.heure,
            s.duree,
            (s.date + s.heure::interval + s.duree) AS seance_fin

        FROM Justificatif j
        JOIN Utilisateur u ON u.id = j.id_utilisateur

        LEFT JOIN JustificatifAbsence ja ON ja.id_justificatif = j.id
        LEFT JOIN Absence a ON a.id = ja.id_absence
        LEFT JOIN Seance s 
            ON s.id = a.id_seance
            AND s.date BETWEEN j.date_debut_demande AND j.date_fin_demande

        WHERE j.id = :id
        ORDER BY s.date, s.heure
    ";



        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $idJustificatif]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    // récupère un justificatif par son id
    public function justificatif_id(int $id): ?array {

        $sql = "SELECT * FROM Justificatif WHERE id = :id";

        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $id]);

        $row = $st->fetch();
        return $row ?: null;
    }
}