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

        $sql = "SELECT COUNT(*) FROM HistoriqueDecision JOIN Justificatif ON Justificatif.id = HistoriqueDecision.id_Justificatif JOIN Utilisateur ON Utilisateur.id = Justificatif.id_Utilisateur $where";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }




    public function get(string $filtrerTexte,string $filtrerDecision,string $filtrerDate1,string $filtrerDate2,int $elements,int $debut): array { // Renommé 'filtrer_pagination' en 'get' pour correspondre à l'utilisation dans HistoriquePresenter.php

        list($where, $params) = $this->construireFiltres($filtrerTexte, $filtrerDecision, $filtrerDate1, $filtrerDate2);

        $sql = " SELECT hd.id, hd.date_action, hd.action, hd.motif_decision, j.id AS justif_id, j.nom_fichier_original,j.date_soumission, j.date_debut_demande, j.date_fin_demande, u.id AS etu_id,u.prenom AS etu_prenom,u.nom AS etu_nom
 FROM (
 SELECT hd.id, hd.id_justificatif, hd.action, hd.date_action, hd.motif_decision
 FROM HistoriqueDecision hd
INNER JOIN ( 
SELECT id_justificatif, MAX(date_action) AS max_date FROM HistoriqueDecision GROUP BY id_justificatif ) last_decision ON last_decision.id_justificatif = hd.id_justificatif AND last_decision.max_date = hd.date_action) hd
JOIN Justificatif j ON j.id = hd.id_justificatif
JOIN Utilisateur u ON u.id = j.id_utilisateur
LEFT JOIN JustificatifAbsence ja ON ja.id_justificatif = j.id
LEFT JOIN Absence a ON a.id = ja.id_absence
LEFT JOIN Seance s ON s.id = a.id_seance

$where
GROUP BY hd.id, hd.date_action, hd.action, hd.motif_decision, j.id, j.nom_fichier_original, j.date_soumission, j.date_debut_demande, j.date_fin_demande, u.id, u.prenom, u.nom


ORDER BY hd.date_action DESC
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