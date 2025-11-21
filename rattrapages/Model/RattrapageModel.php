<?php
declare(strict_types=1);
require_once __DIR__ . '/../../connexion/config/db.php';

final class RattrapageModel
{

    public static function getStudentsForRattrapage(): array
    {
        $sql = "
            SELECT
                s.date,
                e.libelle AS enseignement,
                u.nom AS etudiant_nom,
                u.prenom AS etudiant_prenom,
                a.motif AS motif_absence,
                (
                    SELECT hd.motif_decision
                    FROM JustificatifAbsence ja
                    JOIN HistoriqueDecision hd ON hd.id_justificatif = ja.id_justificatif
                    WHERE ja.id_absence = a.id AND hd.action = 'ACCEPTATION'
                    ORDER BY hd.date_action DESC, hd.id DESC
                    LIMIT 1
                ) AS motif_justif_accepte
            FROM Absence a
            JOIN Seance s ON s.id = a.id_seance
            JOIN Enseignement e ON e.id = s.id_enseignement
            JOIN Utilisateur u ON u.id = a.id_utilisateur
            -- On filtre sur une absence acceptée lors d'une évaluation
            WHERE a.presence = 'ABSENT'
              AND s.controle = TRUE 
              AND EXISTS (
                  SELECT 1 FROM JustificatifAbsence ja
                  JOIN HistoriqueDecision hd ON hd.id_justificatif = ja.id_justificatif
                  WHERE ja.id_absence = a.id AND hd.action = 'ACCEPTATION'
              )
            ORDER BY s.date DESC, enseignement, etudiant_nom
        ";
        $st = db()->prepare($sql);
        $st->execute();

        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }
}