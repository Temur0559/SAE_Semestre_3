<?php
declare(strict_types=1);

require_once __DIR__ . '/../../connexion/config/db.php';

final class AbsenceModel
{

    public static function getIdentity($userId)
    {
        $sql = "
            SELECT u.nom, u.prenom, u.date_naissance, u.ine,
                   COALESCE(p.libelle || ' — ' || p.public, 'BUT INFORMATIQUE — FI') AS programme
            FROM Utilisateur u
            LEFT JOIN Programme p ON p.id = (
                SELECT id FROM Programme ORDER BY id ASC LIMIT 1
            )
            WHERE u.id = :id
        ";
        $st = db()->prepare($sql);
        $st->execute([':id' => $userId]);
        $row = $st->fetch();

        if (!$row) $row = ['nom'=>'','prenom'=>'','date_naissance'=>null,'ine'=>'','programme'=>''];

        $naissance = $row['date_naissance'] ? ('Né(e) le ' . $row['date_naissance']) : 'Né(e) le —/—/—';

        return [
            'nom'      => $row['nom'],
            'prenom'   => $row['prenom'],
            'naissance'=> $naissance,
            'ine'      => $row['ine'] ?: 'INE',
            'program'  => $row['program'] ?? '',
        ];
    }



    public static function getAbsencesForStudent($userId, $filtre )
    {
        $sql = "SELECT
          a.id AS absence_id,
          s.date,
          a.motif AS motif_court,
          a.commentaire AS last_comment,
          a.justification AS db_justification_status
      FROM Absence a
      JOIN Seance s ON s.id = a.id_seance
      JOIN Enseignement e ON e.id = s.id_enseignement
      WHERE a.id_utilisateur = :uid AND NOT EXISTS (
    SELECT 1
    FROM JustificatifAbsence ja
    JOIN HistoriqueDecision hd ON hd.id_justificatif = ja.id_justificatif
    WHERE ja.id_absence = a.id
    AND hd.id = (
        SELECT MAX(hd2.id)
        FROM HistoriqueDecision hd2
        WHERE hd2.id_justificatif = ja.id_justificatif
    )
    AND hd.action IN ('SOUMISSION', 'ACCEPTATION')
)
      ORDER BY s.date DESC, a.id DESC";
        $st = db()->prepare($sql);
        $st->execute([':uid' => $userId]);
        $rows = $st->fetchAll();
        $out  = [];

        // TRAITER LES ABSENCES NORMALES
        foreach ($rows as $r) {
            $dbStatus = $r['db_justification_status'];

            $statut = 'Inconnu';
            $commentaireAfficher;


            if ($dbStatus === 'JUSTIFIEE') {
                $statut = 'Accepté';
                $commentaireAfficher = "";
            } else {
                $statut = 'Non justifié';
                $commentaireAfficher = $r['last_comment'];
            }

            $f = strtolower($filtre ?: 'tous');
            $s = strtolower($statut);
            if ($f !== 'tous' && strpos($s, $f) === false) continue;

            $out[] = [
                'absence_id' => (int)$r['absence_id'],
                'date' => (string)$r['date'],
                'motif' => (string)$r['motif_court'],
                'files_id' => NULL,
                'statut' => $statut,
                'commentaire' => $commentaireAfficher,
                'can_upload' => false,
                'is_range' => false,
            ];
        }

        // AJOUTER LES DÉCLARATIONS (justificatifs de plage)
        $pdo = db();
        $sql_decl = "
        WITH derniere_decision AS (
            SELECT DISTINCT ON (id_justificatif)
                id_justificatif, action, motif_decision
            FROM HistoriqueDecision
            ORDER BY id_justificatif, date_action DESC, id DESC
        )
        SELECT
            j.id as main_id,
            j.date_debut_demande,
            j.date_fin_demande,
            j.motif_libre,
            j.commentaire,
            jh.action AS last_action,
            jh.motif_decision,
            (
                SELECT ARRAY_AGG(id)
                FROM justificatif
                WHERE justificatif.date_debut_demande = j.date_debut_demande AND
                      justificatif.date_fin_demande = j.date_fin_demande AND
                      justificatif.id_utilisateur = j.id_utilisateur
            ) AS ids,
            (
                SELECT ARRAY_AGG(nom_fichier_original)
                FROM justificatif
                WHERE justificatif.date_debut_demande = j.date_debut_demande AND
                      justificatif.date_fin_demande = j.date_fin_demande AND
                      justificatif.id_utilisateur = j.id_utilisateur
            ) AS noms_fichiers
        FROM justificatif j
        JOIN derniere_decision jh ON j.id = jh.id_justificatif
        JOIN justificatifabsence ja ON ja.id_justificatif = j.id
        WHERE j.id_utilisateur = :uid
        GROUP BY j.id, jh.action, jh.motif_decision
        ORDER BY j.date_debut_demande DESC
        ";

        $st_decl = $pdo->prepare($sql_decl);
        $st_decl->execute([':uid' => $userId]);
        $declarations = $st_decl->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($declarations as $decl) {
            $dateRange = $decl['date_debut_demande'] . ' → ' . $decl['date_fin_demande'];

            $statut = match($decl['last_action']) {
                'JUSTIFIEE', 'ACCEPTATION' => 'Accepté',
                'DEMANDE_PRECISIONS' => 'À préciser',
                'REJET' => 'rejeté',
                default => 'En attente'
            };

            $motifDisplay = 'DÉCLARATION : ' . ($decl['commentaire'] ?? 'Absence déclarée');

            $commentaireAffiche = !empty($decl['motif_decision']) ? $decl['motif_decision'] : "Déclaration en attente";

            $ids = explode(',', trim($decl['ids'], '{}'));
            $noms_fichiers = explode(',', trim($decl['noms_fichiers'], '{}'));

            $fichiers = [];

            for($i = 0; $i < count($ids); $i++) {
                if($noms_fichiers[$i] != 'NULL') {
                    $fichiers[] = $ids[$i];
                }
            }

            $out[] = [
                'absence_id' => 0,
                'date' => $dateRange,
                'motif' => $motifDisplay,
                'main_id' => $decl['main_id'],
                'files_id' => $fichiers,
                'statut' => $statut,
                'commentaire' => $commentaireAffiche,
                'is_range' => true
            ];
        }

        return $out;
    }


    public static function insertJustificatif($absenceId, $userId, $originalName, $mime, $binaryContent, string $commentaire = '', string $motifLibre = '')
    {
        $pdo = db();

        try {
            $st = $pdo->prepare("
            INSERT INTO Justificatif (fichier, commentaire, id_utilisateur, nom_fichier_original, type_mime, motif_libre)
            VALUES (decode(:f, 'base64'), :comm, :u, :n, :m, :motifL)
            RETURNING id
        ");

            $st->bindValue(':f', base64_encode($binaryContent), \PDO::PARAM_STR);
            $st->bindValue(':comm', $commentaire, \PDO::PARAM_STR);
            $st->bindValue(':u', $userId, \PDO::PARAM_INT);
            $st->bindValue(':n', $originalName, \PDO::PARAM_STR);
            $st->bindValue(':m', $mime, \PDO::PARAM_STR);
            $st->bindValue(':motifL', $motifLibre, \PDO::PARAM_STR);
            $st->execute();

            return (int)$st->fetchColumn();

        } catch (\Throwable $e) {
            echo "<h1>ERREUR SQL RÉELLE (insertJustificatif)</h1>";
            echo "<pre>";
            var_dump($e->getMessage());
            echo "</pre>";
            die();
        }
    }


    public static function getJustificatifFile($justifId)
    {
        // Récupérer le fichier encodé en base64 depuis PostgreSQL
        $st = db()->prepare("
            SELECT id, 
                   encode(fichier, 'base64') AS fichier_base64,
                   nom_fichier_original AS original_filename, 
                   type_mime AS mime_type
            FROM Justificatif
            WHERE id = :id
        ");
        $st->execute([':id'=>$justifId]);
        $j = $st->fetch();

        if (!$j) return null;

        // Décoder le base64 en données binaires
        $j['fichier'] = base64_decode($j['fichier_base64']);
        unset($j['fichier_base64']); // Nettoyer

        return $j;
    }

    public static function insertDemandeJustification($userId, string $dateDebut, string $dateFin, $originalName, $mime, $binaryContent, string $commentaire = '', string $motifLibre = '') {
        $pdo = db();
        //$pdo->beginTransaction();

        // On force l'affichage des erreurs pour ce bloc
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        try {
            // 1. Insertion simple sans transaction complexe pour tester
            $sql = "INSERT INTO Justificatif (fichier, commentaire, id_utilisateur, nom_fichier_original, type_mime, motif_libre, date_debut_demande, date_fin_demande)
                VALUES (:f, :comm, :u, :n, :m, :motifL, :dd, :df)
                RETURNING id";

            $st = $pdo->prepare($sql);


            $st->bindValue(
                ':f',
                $binaryContent === null ? '' : $binaryContent,
                \PDO::PARAM_LOB);
            $st->bindValue(':comm', $commentaire);
            $st->bindValue(':u', (int)$userId, \PDO::PARAM_INT);
            $st->bindValue(':n', $originalName ?? 'DECLARATION_SANS_FICHIER');
            $st->bindValue(':m', $mime ?? 'text/plain');

            $st->bindValue(':motifL', $motifLibre);
            $st->bindValue(':dd', $dateDebut);
            $st->bindValue(':df', $dateFin);

            $st->execute();
            $jid = (int)$st->fetchColumn();


            $action = 'SOUMISSION';

            $allowedActions = [
                'SOUMISSION',
                'DEMANDE_PRECISIONS',
                'RENVOI_FICHIER',
                'ACCEPTATION',
                'REJET',
                'AUTORISATION_RENVOI',
                'AUTORISATION_HORS_DELAI'
            ];

            if (!in_array($action, $allowedActions, true)) {
                throw new Exception("Action ENUM invalide pour HistoriqueDecision : " . $action);
            }

            // 2. Insertion Historique (séparée)
            $sqlHist = "INSERT INTO HistoriqueDecision (action, id_justificatif, id_auteur, motif_decision)
            VALUES (?, ?, ?, ?)";
            $pdo->prepare($sqlHist)->execute([$action, $jid, $userId, "Déclaration du $dateDebut au $dateFin"]);

            // 3. Liaison (uniquement si le reste a marché)
            // On commente la liaison pour vérifier si l'insertion de base fonctionne enfin
            self::linkJustificatifToAbsences($jid, $userId, $dateDebut, $dateFin);


            return $jid;

        } catch (\Exception $e) {
            // Si ça rate, on arrête tout et on affiche l'erreur en GROS
            echo "<h1>ERREUR SQL DETECTEE</h1>";
            echo "<pre>" . $e->getMessage() . "</pre>";

            die();
        }
    }

    public static function getAbsenceForUserBetweenPeriod(int $userId, string $dateDebut, string $dateFin): array {
        $pdo = db();

        $sql = "SELECT a.id FROM Absence a JOIN Seance s ON s.id = a.id_seance
            WHERE a.id_utilisateur = :uid AND a.justification IN ('INCONNU', 'NON_JUSTIFIEE')
            AND s.date BETWEEN :dd AND :df
            AND NOT EXISTS (SELECT 1
    FROM JustificatifAbsence ja
    JOIN HistoriqueDecision hd ON hd.id_justificatif = ja.id_justificatif
    WHERE ja.id_absence = a.id
    AND hd.id = (
        SELECT MAX(hd2.id)
        FROM HistoriqueDecision hd2
        WHERE hd2.id_justificatif = ja.id_justificatif
    )
    AND hd.action IN ('SOUMISSION', 'ACCEPTATION'))";

        $st = $pdo->prepare($sql);
        $st->execute([':uid' => $userId, ':dd' => $dateDebut, ':df' => $dateFin]);

        return $st->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function linkJustificatifToAbsences(int $justifId, int $userId, string $dateDebut, string $dateFin): int {
        $ids = self::getAbsenceForUserBetweenPeriod($userId, $dateDebut, $dateFin);

        $pdo = db();

        if (empty($ids)) return 0;

        $stLink = $pdo->prepare("INSERT INTO JustificatifAbsence (id_justificatif, id_absence) VALUES (:j, :a) ON CONFLICT DO NOTHING");
        foreach ($ids as $id) {
            $stLink->execute([':j' => $justifId, ':a' => (int)$id]);
        }
        return count($ids);
    }

    public static function getPendingRangeJustifications(int $userId): array
    {

        $pdo = db();
        $sql = "
            SELECT 
                j.id AS justificatif_id,
                j.date_debut_demande AS date_debut,
                j.date_fin_demande AS date_fin,
                j.motif_libre AS raison_demande,
                j.commentaire,
                hd.action AS last_action 
            FROM Justificatif j
            JOIN HistoriqueDecision hd ON hd.id_justificatif = j.id
            WHERE j.id_utilisateur = :uid
            AND hd.action = 'SOUMISSION' 
            AND NOT EXISTS (SELECT 1 FROM JustificatifAbsence ja WHERE ja.id_justificatif = j.id)
            ORDER BY j.date_debut_demande DESC;
        ";

        $st = $pdo->prepare($sql);
        $st->execute([':uid' => $userId]);

        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }


    public static function logRpDecision(int $justifId, int $rpId, string $action, string $motif): bool
    {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            // 1. Loguer la nouvelle décision
            $st_log = $pdo->prepare("
                INSERT INTO HistoriqueDecision (action, id_justificatif, id_auteur, motif_decision)
                VALUES (:action, :j, :auteur, :motif_decision)
            ");
            $st_log->execute([
                ':action'         => $action,
                ':j'              => $justifId,
                ':auteur'         => $rpId,
                ':motif_decision' => $motif
            ]);

            // 2. Mettre à jour le statut 'justification' de l'Absence
            $newJustifStatus = match ($action) {
                'ACCEPTATION' => 'JUSTIFIEE',
                'REJET' => 'NON_JUSTIFIEE',
                // DEMANDE_PRECISIONS, AUTORISATION_RENVOI, etc. conservent un statut INCONNU pour l'étudiant
                default => 'INCONNU',
            };

            // Appliquer le statut à toutes les absences liées à ce justificatif
            $st_update_abs = $pdo->prepare("
                UPDATE Absence a
                SET justification = :new_status
                FROM JustificatifAbsence ja
                WHERE ja.id_absence = a.id AND ja.id_justificatif = :j
            ");
            $st_update_abs->execute([':new_status' => $newJustifStatus, ':j' => $justifId]);

            // 3. Gestion du verrouillage (US4)
            if ($action === 'DEMANDE_PRECISIONS' || $action === 'AUTORISATION_RENVOI' || $action === 'AUTORISATION_HORS_DELAI') {
                // Déverrouiller/Autoriser le renvoi
                $st_unlock = $pdo->prepare("UPDATE Justificatif SET verouille = FALSE, verouille_date = NULL WHERE id = :j");
                $st_unlock->execute([':j' => $justifId]);
            } else {
                // Verrouiller (car traité ou soumis sans nécessité de renvoi)
                $st_lock = $pdo->prepare("UPDATE Justificatif SET verouille = TRUE, verouille_date = NOW() WHERE id = :j");
                $st_lock->execute([':j' => $justifId]);
            }

            $pdo->commit();
            return true;

        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw new \Exception("Erreur de log de décision RP: " . $e->getMessage(), 0, $e);
        }
    }


    public static function getAbsencesForInitialReminder(): array
    {
        $sql = "
            SELECT u.email, u.nom, u.prenom, s.date, e.libelle AS motif_seance
            FROM Absence a
            JOIN Utilisateur u ON u.id = a.id_utilisateur
            JOIN Seance s ON s.id = a.id_seance
            JOIN Enseignement e ON e.id = s.id_enseignement
            WHERE a.justification IN ('INCONNU', 'NON_JUSTIFIEE')
              AND s.date < CURRENT_DATE - INTERVAL '1 day' -- Absence d'il y a plus de 24h
              AND s.date > CURRENT_DATE - INTERVAL '3 days' -- Limite pour éviter d'envoyer des rappels trop anciens
              AND NOT EXISTS (
                  SELECT 1 FROM JustificatifAbsence ja
                  JOIN HistoriqueDecision hd ON hd.id_justificatif = ja.id_justificatif
                  WHERE ja.id_absence = a.id
              )
        ";
        $st = db()->prepare($sql);
        $st->execute();
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }


    public static function getAbsencesForReturnReminder(): array
    {

        return [];
    }
}