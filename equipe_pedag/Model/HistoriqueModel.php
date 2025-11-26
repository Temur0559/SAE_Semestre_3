<?php

class HistoriqueModel {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }


    private function construireFiltres(string $filtrerTexte,string $filtrerDecision,string $filtrerDate1,string $filtrerDate2): array {

        $conditions = [];
        $params = [];

        // filtre par nom/prenom
        if ($filtrerTexte !== '') {
            $conditions[] = "(Utilisateur.nom ILIKE :filtreTexte OR Utilisateur.prenom ILIKE :filtreTexte)";
            $params[':filtreTexte'] = "%$filtrerTexte%";
        }

        // filtrer par la décisionq qu'on selectionne
        if ($filtrerDecision !== 'toutes') {
            $conditions[] = "HistoriqueDecision.action = :filtreDecision";
            $params[':filtreDecision'] = $filtrerDecision;
        }

        // filtre par la date de debut qu'on met
        if ($filtrerDate1 !== '') {
            $conditions[] = "HistoriqueDecision.date_action >= :dateMin";
            $params[':dateMin'] = $filtrerDate1 . " 00:00:00";
        }

        // filtre par la dae max qu'on met
        if ($filtrerDate2 !== '') {
            $conditions[] = "HistoriqueDecision.date_action <= :dateMax";
            $params[':dateMax'] = $filtrerDate2 . " 23:59:59";
        }

        // requete de notre filtre
        $where = "";
        if (!empty($conditions)) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        return [$where, $params];
    }




    // on compte y a cmbien de lignes filtrés avec notre sélection
    public function count(string $filtrerTexte, string $filtrerDecision, string $filtrerDate1, string $filtrerDate2): int {

        list($where, $params) = $this->construireFiltres($filtrerTexte, $filtrerDecision, $filtrerDate1, $filtrerDate2);

        $sql = " SELECT COUNT(*) FROM HistoriqueDecision
        JOIN Justificatif ON Justificatif.id = HistoriqueDecision.id_justificatif
        JOIN Utilisateur ON Utilisateur.id = Justificatif.id_utilisateur
        LEFT JOIN JustificatifAbsence ON JustificatifAbsence.id_justificatif = Justificatif.id
        LEFT JOIN Absence ON Absence.id = JustificatifAbsence.id_absence
        LEFT JOIN Seance ON Seance.id = Absence.id_seance
        $where";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }




    public function get(string $filtrerTexte,string $filtrerDecision,string $filtrerDate1,string $filtrerDate2,int $elements,int $debut): array { // Renommé 'filtrer_pagination' en 'get' pour correspondre à l'utilisation dans HistoriquePresenter.php

        list($where, $params) = $this->construireFiltres($filtrerTexte, $filtrerDecision, $filtrerDate1, $filtrerDate2);

        $sql = "
            SELECT
                HistoriqueDecision.id,
                HistoriqueDecision.date_action,
                HistoriqueDecision.action,
                HistoriqueDecision.motif_decision,

                Justificatif.id AS justif_id,
                Justificatif.nom_fichier_original,
                Justificatif.date_soumission,

                Utilisateur.id AS etu_id,
                Utilisateur.prenom AS etu_prenom,
                Utilisateur.nom AS etu_nom,

                Seance.date AS date_seance

            FROM HistoriqueDecision
            JOIN Justificatif ON Justificatif.id = HistoriqueDecision.id_justificatif
            JOIN Utilisateur ON Utilisateur.id = Justificatif.id_utilisateur
            LEFT JOIN JustificatifAbsence ON JustificatifAbsence.id_justificatif = Justificatif.id
            LEFT JOIN Absence ON Absence.id = JustificatifAbsence.id_absence
            LEFT JOIN Seance ON Seance.id = Absence.id_seance

            $where
            ORDER BY HistoriqueDecision.date_action DESC
            LIMIT :lim OFFSET :off
        ";

        $stmt = $this->pdo->prepare($sql);

        // Pagination
        $stmt->bindValue(':lim', $elements, PDO::PARAM_INT);
        $stmt->bindValue(':off', $debut, PDO::PARAM_INT);

        // application des filtres
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}