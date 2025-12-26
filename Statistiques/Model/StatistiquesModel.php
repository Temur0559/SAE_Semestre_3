<?php
declare(strict_types=1);
require_once __DIR__ . '/../../connexion/config/db.php';

final class StatistiquesModel
{

    public static function getStudents(): array
    {
        $pdo = db();
        $sql = "
            SELECT id, nom, prenom, identifiant
            FROM utilisateur
            WHERE role = 'ETUDIANT'
            ORDER BY nom, prenom;
        ";
        $st = $pdo->query($sql);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getAllEnseignements(): array
    {
        $pdo = db();
        $sql = "SELECT id, code, libelle FROM enseignement ORDER BY code;";
        return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getProgrammes(): array
    {
        $pdo = db();
        return $pdo->query("SELECT id, libelle FROM programme ORDER BY libelle")->fetchAll(\PDO::FETCH_ASSOC);
    }

    private static function buildWhereClause(?string $startDate, ?string $endDate, ?int $studentId, ?string $typeSeance, ?int $programmeId, ?int $enseignementId): array
    {
        $where = "WHERE a.presence = 'ABSENT'";
        $params = [];

        if ($studentId === null || $studentId <= 0) {
            return ['where' => $where . " AND FALSE", 'params' => $params];
        }

        $where .= " AND a.id_utilisateur = :studentId";
        $params[':studentId'] = $studentId;

        if ($startDate) { $where .= " AND s.date >= :start_date"; $params[':start_date'] = $startDate; }
        if ($endDate) { $where .= " AND s.date <= :end_date"; $params[':end_date'] = $endDate; }
        if ($typeSeance) { $where .= " AND s.type = :typeSeance"; $params[':typeSeance'] = $typeSeance; }
        if ($enseignementId) { $where .= " AND s.id_enseignement = :enseignementId"; $params[':enseignementId'] = $enseignementId; }

        return ['where' => $where, 'params' => $params];
    }

    public static function getAbsencesByType($startDate, $endDate, $studentId, $programmeId, $enseignementId): array
    {
        $pdo = db();
        $queryData = self::buildWhereClause($startDate, $endDate, $studentId, null, $programmeId, $enseignementId);
        $sql = "SELECT s.type AS type_seance, COUNT(a.id) AS total_absences FROM absence a JOIN seance s ON s.id = a.id_seance {$queryData['where']} GROUP BY s.type ORDER BY total_absences DESC;";
        $st = $pdo->prepare($sql);
        $st->execute($queryData['params']);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getAbsencesByRessource($startDate, $endDate, $studentId, $typeSeance, $programmeId): array
    {
        $pdo = db();
        $queryData = self::buildWhereClause($startDate, $endDate, $studentId, $typeSeance, $programmeId, null);
        $sql = "SELECT e.code AS ressource_code, e.libelle AS ressource_libelle, COUNT(a.id) AS total_absences FROM absence a JOIN seance s ON s.id = a.id_seance JOIN enseignement e ON e.id = s.id_enseignement {$queryData['where']} GROUP BY e.code, e.libelle ORDER BY total_absences DESC;";
        $st = $pdo->prepare($sql);
        $st->execute($queryData['params']);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getAbsencesEvolution($startDate, $endDate, $studentId, $typeSeance, $programmeId, $enseignementId): array
    {
        $pdo = db();
        $queryData = self::buildWhereClause($startDate, $endDate, $studentId, $typeSeance, $programmeId, $enseignementId);
        $sql = "SELECT TO_CHAR(s.date, 'YYYY-MM') AS mois, COUNT(a.id) AS total_absences FROM absence a JOIN seance s ON s.id = a.id_seance {$queryData['where']} GROUP BY mois ORDER BY mois;";
        $st = $pdo->prepare($sql);
        $st->execute($queryData['params']);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getTotalUnexcusedAbsences(?int $studentId): int
    {
        if ($studentId === null) return 0;
        $pdo = db();
        $st = $pdo->prepare("SELECT COUNT(a.id) FROM absence a WHERE a.id_utilisateur = :studentId AND a.presence = 'ABSENT' AND a.justification = 'INCONNU';");
        $st->execute([':studentId' => $studentId]);
        return (int)$st->fetchColumn();
    }
}