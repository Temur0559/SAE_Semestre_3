<?php
declare(strict_types=1);
require_once __DIR__ . '/../../connexion/config/db.php';

final class RattrapageModel
{
    public static function getAssignedResources(int $profId): array
    {
        $pdo = db();
        $sql = "
            SELECT code_ressource
            FROM EnseignantRessource
            WHERE id_enseignant = :id
            ORDER BY code_ressource;
        ";
        $st = $pdo->prepare($sql);
        $st->execute([':id' => $profId]);

        return $st->fetchAll(\PDO::FETCH_COLUMN);
    }


    public static function getStudentsForRattrapage(int $profId, ?string $ressourceCode = null): array
    {
        $pdo = db();

        $whereClause = "
            AND e.code IN (
                SELECT er.code_ressource 
                FROM EnseignantRessource er 
                WHERE er.id_enseignant = :profId
            )
        ";
        $params = [':profId' => $profId];

        if ($ressourceCode !== null && $ressourceCode !== '') {
            $whereClause .= " AND e.code = :ressourceCode";
            $params[':ressourceCode'] = $ressourceCode;
        }

        $sql = "
            SELECT
                e.code AS ressource_code,
                e.libelle AS enseignement_libelle,
                u.nom AS etudiant_nom,
                u.prenom AS etudiant_prenom,
                s.date AS date_absence,
                'Excusée' AS statut_justif 
                
            FROM Absence a
            JOIN Seance s ON s.id = a.id_seance
            JOIN Enseignement e ON e.id = s.id_enseignement
            JOIN utilisateur u ON u.id = a.id_utilisateur
            
            --Uniquement les absences ACCEPTÉES
            WHERE a.presence = 'ABSENT'
              -- NOUVELLE CONDITION La justification a été officiellement acceptée
              AND EXISTS (
                  SELECT 1 FROM JustificatifAbsence ja
                  JOIN HistoriqueDecision hd ON hd.id_justificatif = ja.id_justificatif
                  WHERE ja.id_absence = a.id AND hd.action = 'ACCEPTATION'
              )
              
              -- Condition d'évaluation: Contrôle = TRUE OU Type = DS
              AND (s.controle = TRUE OR s.type = 'DS')
              
              {$whereClause}
              
            ORDER BY s.date DESC, ressource_code, etudiant_nom
        ";
        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }
}