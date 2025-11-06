<?php
declare(strict_types=1);


require_once __DIR__ . '/../connexion/config/db.php';

final class JustificationModel
{
    /**
     * Récupère la liste des absences qui peuvent être justifiées par l'étudiant.
     */
    public static function getJustifiableAbsences(int $userId): array
    {
        $sql = "
            SELECT
                a.id AS id_absence,
                s.date,
                e.libelle AS motif,
                a.justification
            FROM Absence a
            JOIN Seance s ON s.id = a.id_seance
            JOIN Enseignement e ON e.id = s.id_enseignement
            WHERE a.id_utilisateur = :uid
              AND a.justification IN ('INCONNU', 'NON_JUSTIFIEE') 
              -- Exclure les absences qui ont déjà un justificatif en attente/soumis
              AND NOT EXISTS (
                  SELECT 1 FROM JustificatifAbsence ja
                  JOIN HistoriqueDecision h ON h.id_justificatif = ja.id_justificatif
                  WHERE ja.id_absence = a.id AND h.action = 'SOUMISSION'
              )
            ORDER BY s.date DESC
        ";
        $st = db()->prepare($sql);
        $st->execute([':uid' => $userId]);

        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            if (isset($row['date'])) {
                $row['date'] = (new DateTime($row['date']))->format('d/m/Y');
            }
        }
        return $rows;
    }
}