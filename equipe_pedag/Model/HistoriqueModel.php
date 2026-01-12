<?php

class HistoriqueModel {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Construire les filtres pour les requêtes qui récupérent des informations de l'historique
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
            $conditions[] = "Justificatif.date_debut_demande <= :dateMax";
            $params[':dateMax'] = $filtrerDate1 . " 00:00:00";
        }

        // filtre par la dae max qu'on met
        if ($filtrerDate2 !== '') {
            $conditions[] = "Justificatif.date_fin_demande >= :dateMin";
            $params[':dateMin'] = $filtrerDate2 . " 23:59:59";
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

        $sql = "SELECT COUNT(*) FROM HistoriqueDecision JOIN Justificatif ON Justificatif.id = HistoriqueDecision.id_Justificatif JOIN Utilisateur ON Utilisateur.id = Justificatif.id_Utilisateur $where";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    // Récupère les justificatifs pour les afficher dans l'historique
    public function get(string $filtrerTexte,string $filtrerDecision,string $filtrerDate1,string $filtrerDate2,int $elements,int $debut): array { // Renommé 'filtrer_pagination' en 'get' pour correspondre à l'utilisation dans HistoriquePresenter.php

        list($where, $params) = $this->construireFiltres($filtrerTexte, $filtrerDecision, $filtrerDate1, $filtrerDate2);

        $sql = "WITH derniere_decision AS (
                    SELECT
                        DISTINCT ON (HistoriqueDecision.id_justificatif)
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
                
                    HistoriqueDecision.action,
                    HistoriqueDecision.date_action
                
                FROM Justificatif
                JOIN Utilisateur ON Utilisateur.id = Justificatif.id_utilisateur
                JOIN JustificatifAbsence ON Justificatif.id = JustificatifAbsence.id_justificatif
                JOIN derniere_decision AS HistoriqueDecision ON HistoriqueDecision.id_justificatif = Justificatif.id

                $where
                ORDER BY HistoriqueDecision.date_action DESC NULLS LAST
                LIMIT :lim OFFSET :off";

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