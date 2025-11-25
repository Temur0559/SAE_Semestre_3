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
        $sql = "
          SELECT
              a.id AS absence_id,
              s.date,
              a.motif AS motif_court,
              a.commentaire AS last_comment,
              a.justification AS db_justification_status, 
              
              
              (
                  SELECT ja.id_justificatif
                  FROM JustificatifAbsence ja
                  JOIN HistoriqueDecision hd ON hd.id_justificatif = ja.id_justificatif
                  WHERE ja.id_absence = a.id
                  ORDER BY hd.date_action DESC, hd.id DESC
                  LIMIT 1
              ) AS justificatif_id,
              
              
              (
                  SELECT hd.action
                  FROM JustificatifAbsence ja
                  JOIN HistoriqueDecision hd ON hd.id_justificatif = ja.id_justificatif
                  WHERE ja.id_absence = a.id
                  ORDER BY hd.date_action DESC, hd.id DESC
                  LIMIT 1
              ) AS last_action,
              
              
              (
                  SELECT hd.motif_decision
                  FROM JustificatifAbsence ja
                  JOIN HistoriqueDecision hd ON hd.id_justificatif = ja.id_justificatif
                  WHERE ja.id_absence = a.id
                  ORDER BY hd.date_action DESC, hd.id DESC
                  LIMIT 1
              ) AS motif_decision_historique,
              
              
              (
                  SELECT j.verouille
                  FROM Justificatif j
                  JOIN JustificatifAbsence ja ON ja.id_justificatif = j.id
                  WHERE ja.id_absence = a.id
                  ORDER BY j.date_soumission DESC
                  LIMIT 1
              ) AS verouille_status
              
          FROM Absence a
          JOIN Seance s ON s.id = a.id_seance
          JOIN Enseignement e ON e.id = s.id_enseignement
          WHERE a.id_utilisateur = :uid
          ORDER BY s.date ASC, a.id ASC
        ";
        $st = db()->prepare($sql);
        $st->execute([':uid' => $userId]);
        $rows = $st->fetchAll();
        $out  = [];

        foreach ($rows as $r) {
            $lastAction = $r['last_action'];
            $justificatifId = $r['justificatif_id'];
            $dbStatus = $r['db_justification_status'];
            $motifDecisionHistorique  = $r['motif_decision_historique'];
            $verouilleStatus = $r['verouille_status'];

            $statut = 'Inconnu';
            $commentaireAfficher = $r['last_comment'];


            if ($lastAction === 'ACCEPTATION') {
                $statut = 'Accepté';
                $commentaireAfficher = "Justifié : " . $motifDecisionHistorique;
            } elseif ($lastAction === 'REJET') {
                $statut = 'Rejeté';
                $commentaireAfficher = "Rejeté : " . $motifDecisionHistorique;
            } elseif (in_array($lastAction, ['DEMANDE_PRECISIONS', 'RENVOI_FICHIER', 'AUTORISATION_RENVOI', 'AUTORISATION_HORS_DELAI'], true)) {
                $statut = 'En révision'; // Statut de déblocage/re-upload
                $commentaireAfficher = "Précisions demandées : " . $motifDecisionHistorique;
            } elseif ($lastAction === 'SOUMISSION' || $dbStatus === 'INCONNU' || $dbStatus === 'NON_JUSTIFIEE') {
                $statut = 'En attente';
                $commentaireAfficher = $lastAction === 'SOUMISSION' ? "Soumis : Traitement en cours." : $commentaireAfficher;
            }


            $canUpload = false;


            if (in_array($lastAction, ['DEMANDE_PRECISIONS', 'RENVOI_FICHIER', 'AUTORISATION_RENVOI', 'AUTORISATION_HORS_DELAI'], true)) {
                $canUpload = true;
            }


            if ($justificatifId === null && $dbStatus === 'NON_JUSTIFIEE') {
                $canUpload = true;
            }


            if ($verouilleStatus === 't') {
                $canUpload = false;
            }


            $f = strtolower($filtre ?: 'tous');
            $s = strtolower($statut);
            if ($f !== 'tous' && strpos($s, $f) === false) continue;

            $out[] = [
                'absence_id'      => (int)$r['absence_id'],
                'date'            => (string)$r['date'],
                'motif'           => (string)$r['motif_court'],
                'justificatif_id' => $justificatifId ? (int)$justificatifId : null,
                'statut'          => $statut,
                'commentaire'     => $commentaireAfficher,
                'can_upload'      => (bool)$canUpload,
                'is_range'        => false,
            ];
        }
        return $out;
    }


    public static function insertJustificatif($absenceId, $userId, $originalName, $mime, $binaryContent, string $commentaire = '', string $motifLibre = '')
    {
        // Cette fonction est utilisée pour l'upload d'un justificatif pour une SEULE absence (via mesabsence/upload.php)
        $pdo = db();
        $pdo->beginTransaction();
        try {

            $st = $pdo->prepare("
                INSERT INTO Justificatif (fichier, commentaire, id_utilisateur, nom_fichier_original, type_mime, motif_libre)
                VALUES (:f, :comm, :u, :n, :m, :motifL)
                RETURNING id
            ");
            $st->bindValue(':f', $binaryContent, \PDO::PARAM_LOB);
            $st->bindValue(':comm', $commentaire, \PDO::PARAM_STR);
            $st->bindValue(':u', $userId, \PDO::PARAM_INT);
            $st->bindValue(':n', $originalName, \PDO::PARAM_STR);
            $st->bindValue(':m', $mime, \PDO::PARAM_STR);
            $st->bindValue(':motifL', $motifLibre, \PDO::PARAM_STR);
            $st->execute();
            $jid = (int)$st->fetchColumn();


            $st2 = $pdo->prepare("INSERT INTO JustificatifAbsence (id_justificatif, id_absence) VALUES (:j,:a)");
            $st2->execute([':j'=>$jid, ':a'=>$absenceId]);


            $historiqueMotif = 'Soumission initiale par l\'étudiant';
            $st3 = $pdo->prepare("
                INSERT INTO HistoriqueDecision (action, id_justificatif, id_auteur, motif_decision)
                VALUES ('SOUMISSION', :j, :u, :motif_hist)
            ");
            $st3->execute([
                ':j' => $jid,
                ':u' => $userId,
                ':motif_hist' => $historiqueMotif
            ]);


            $st4 = $pdo->prepare("UPDATE Absence SET justification = 'INCONNU' WHERE id = :a");
            $st4->execute([':a' => $absenceId]);


            $pdo->commit();
            return $jid;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }


    public static function getJustificatifFile($justifId)
    {

        $st = db()->prepare("
            SELECT id, fichier, nom_fichier_original, type_mime
            FROM Justificatif
            WHERE id = :id
        ");
        $st->execute([':id'=>$justifId]);
        $j = $st->fetch();
        return $j ? $j : null;
    }



    public static function insertDemandeJustification(
        $userId,
        string $dateDebut,
        string $dateFin,
        $originalName,
        $mime,
        $binaryContent,
        string $commentaire = '',
        string $motifLibre = ''
    ) {

        $pdo = db();
        $pdo->beginTransaction();
        try {

            $st = $pdo->prepare("
                INSERT INTO Justificatif (
                    fichier, commentaire, id_utilisateur, nom_fichier_original, type_mime, motif_libre, 
                    date_debut_demande, date_fin_demande
                )
                VALUES (:f, :comm, :u, :n, :m, :motifL, :dd, :df)
                RETURNING id
            ");

            $st->bindValue(':f', $binaryContent, $binaryContent === null ? \PDO::PARAM_NULL : \PDO::PARAM_LOB);
            $st->bindValue(':comm', $commentaire, \PDO::PARAM_STR);
            $st->bindValue(':u', $userId, \PDO::PARAM_INT);
            $st->bindValue(':n', $originalName, $originalName === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);
            $st->bindValue(':m', $mime, $mime === null ? \PDO::PARAM_NULL : \PDO::PARAM_STR);

            $st->bindValue(':motifL', $motifLibre, \PDO::PARAM_STR);
            $st->bindValue(':dd', $dateDebut, \PDO::PARAM_STR);
            $st->bindValue(':df', $dateFin, \PDO::PARAM_STR);
            $st->execute();
            $jid = (int)$st->fetchColumn();


            $historiqueMotif = 'Déclaration d\'absence/justification pour la plage du ' . $dateDebut . ' au ' . $dateFin;
            $st3 = $pdo->prepare("
                INSERT INTO HistoriqueDecision (action, id_justificatif, id_auteur, motif_decision)
                VALUES ('SOUMISSION', :j, :u, :motif_hist)
            ");
            $st3->execute([
                ':j' => $jid,
                ':u' => $userId,
                ':motif_hist' => $historiqueMotif
            ]);

            // US2: Tente de lier toutes les absences passées dans la plage au nouveau justificatif
            self::linkJustificatifToAbsences($jid, $userId, $dateDebut, $dateFin);

            $pdo->commit();
            return $jid;

        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }


    public static function linkJustificatifToAbsences(int $justifId, int $userId, string $dateDebut, string $dateFin): int
    {
        $pdo = db();
        // Utilise la même transaction que insertDemandeJustification ou en ouvre une nouvelle si nécessaire
        $inTransaction = $pdo->inTransaction();
        if (!$inTransaction) $pdo->beginTransaction();

        try {
            // 1. Trouver toutes les absences concernées (INCONNUES ou NON_JUSTIFIEE)
            // L'intervalle de temps s'étend jusqu'à l'absence la plus récente.
            $sql_select = "
                SELECT a.id AS absence_id
                FROM Absence a
                JOIN Seance s ON s.id = a.id_seance
                WHERE a.id_utilisateur = :uid
                  AND a.justification IN ('INCONNU', 'NON_JUSTIFIEE')
                  AND s.date BETWEEN :dd AND :df
                  -- Exclure celles déjà couvertes par une SOUMISSION acceptée/en attente pour éviter les doublons
                  AND NOT EXISTS (
                      SELECT 1 FROM JustificatifAbsence ja
                      JOIN HistoriqueDecision hd ON hd.id_justificatif = ja.id_justificatif
                      WHERE ja.id_absence = a.id AND hd.action IN ('SOUMISSION', 'ACCEPTATION')
                  );
            ";
            $st_select = $pdo->prepare($sql_select);
            $st_select->execute([
                ':uid' => $userId,
                ':dd'  => $dateDebut,
                ':df'  => $dateFin,
            ]);
            $absenceIds = $st_select->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($absenceIds)) {
                if (!$inTransaction) $pdo->commit();
                return 0; // Aucune absence à lier
            }

            // 2. Lier le justificatif et mettre à jour le statut de l'absence
            $count = 0;
            $st_link = $pdo->prepare("INSERT INTO JustificatifAbsence (id_justificatif, id_absence) VALUES (:j,:a) ON CONFLICT DO NOTHING");
            $st_update_abs = $pdo->prepare("UPDATE Absence SET justification = 'INCONNU' WHERE id = :a AND id_utilisateur = :u");

            foreach ($absenceIds as $absenceId) {
                $st_link->execute([':j' => $justifId, ':a' => (int)$absenceId]);
                $st_update_abs->execute([':a' => (int)$absenceId, ':u' => $userId]);
                $count++;
            }

            if (!$inTransaction) $pdo->commit();
            return $count;

        } catch (\Throwable $e) {
            if (!$inTransaction) $pdo->rollBack();
            // Si c'était déjà en transaction, l'exception sera propagée et gérée par l'appelant
            throw $e;
        }
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

    /**
     * US6 - Log la décision du RP et met à jour le statut de l'absence liée.
     * @param int $justifId L'ID du justificatif concerné.
     * @param int $rpId L'ID de l'auteur (RP).
     * @param string $action L'action ('ACCEPTATION', 'REJET', etc.)
     * @param string $motif La raison de la décision.
     * @return bool
     * @throws \Exception
     */
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
}