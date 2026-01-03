<?php

class detailHistoriqueModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function detailParJustificatif(int $idJustificatif): array {
        $sql = "SELECT hd.id, hd.date_action, hd.action, hd.motif_decision, u.prenom AS etu_prenom, u.nom AS etu_nom, s.date AS date_seance FROM HistoriqueDecision hd JOIN Justificatif j ON j.id = hd.id_justificatif JOIN Utilisateur u ON u.id = j.id_utilisateur LEFT JOIN JustificatifAbsence ja ON ja.id_justificatif = j.id LEFT JOIN Absence a ON a.id = ja.id_absence LEFT JOIN Seance s ON s.id = a.id_seance WHERE hd.id_justificatif = :id ORDER BY hd.date_action DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id'=>$idJustificatif]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}