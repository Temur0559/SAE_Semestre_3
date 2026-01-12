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
        WITH derniere_decision AS (
            SELECT DISTINCT ON (HistoriqueDecision.id_justificatif)
                HistoriqueDecision.id_justificatif,
                HistoriqueDecision.action,
                HistoriqueDecision.motif_decision,
                HistoriqueDecision.date_action
            FROM HistoriqueDecision
            ORDER BY HistoriqueDecision.id_justificatif, HistoriqueDecision.date_action DESC, HistoriqueDecision.id DESC
        )
        SELECT DISTINCT 
            Utilisateur.id AS etudiant_id,
            Utilisateur.nom AS etu_nom,
            Utilisateur.prenom AS etu_prenom,

            Justificatif.id AS id,
            Justificatif.date_debut_demande,
            Justificatif.date_fin_demande,
            Justificatif.commentaire,
            Justificatif.motif_libre,

            derniere_decision.action,
            derniere_decision.motif_decision

        FROM Justificatif
        JOIN Utilisateur ON Utilisateur.id = Justificatif.id_utilisateur
        JOIN JustificatifAbsence ON Justificatif.id = JustificatifAbsence.id_justificatif
        JOIN derniere_decision ON derniere_decision.id_justificatif = Justificatif.id
        
        WHERE Justificatif.date_debut_demande IS NOT NULL AND 
            Justificatif.date_fin_demande IS NOT NULL
        
        ORDER BY 
            Justificatif.date_debut_demande DESC NULLS LAST, 
            Justificatif.id DESC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    // Récupère les fichiers et la liste d'absence pour un justificatif
    public function detailsJustificatif(int $id_utilisateur, $dateDebut, $dateFin): array
    {
        $sql = "SELECT DISTINCT 
                    a.id AS absence_id,
                    s.id AS seance_id,
                    s.date,
                    s.heure,
                    s.duree,
                    (s.date + s.heure::interval + s.duree) AS seance_fin,
                    a.justification
                    
                    FROM Justificatif j
                    JOIN JustificatifAbsence ja ON ja.id_justificatif = j.id
                    JOIN Absence a ON a.id = ja.id_absence
                    JOIN Seance s
                        ON s.id = a.id_seance
                        AND s.date BETWEEN j.date_debut_demande AND j.date_fin_demande
            
                    WHERE j.date_debut_demande = :dateDebut
                      AND j.date_fin_demande = :dateFin
                      AND j.id_utilisateur = :idu
                    ORDER BY s.date, s.heure
                ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idu' => $id_utilisateur,
            ':dateDebut' => $dateDebut,
            ':dateFin' => $dateFin
        ]);

        $listAbs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT id, fichier, nom_fichier_original
                FROM justificatif
                WHERE nom_fichier_original IS NOT NULL
                    AND id_utilisateur = :idu
                    AND date_debut_demande = :dateDebut
                    AND date_fin_demande = :dateFin";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idu' => $id_utilisateur,
            ':dateDebut' => $dateDebut,
            ':dateFin' => $dateFin
        ]);

        $listFichiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'listAbs' => $listAbs,
            'listFichiers' => $listFichiers
        ];
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