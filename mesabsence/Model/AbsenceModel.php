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
      ORDER BY s.date DESC, a.id DESC
    ";
        $st = db()->prepare($sql);
        $st->execute([':uid' => $userId]);
        $rows = $st->fetchAll();
        $out  = [];

        // TRAITER LES ABSENCES NORMALES
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
                $statut = 'En révision';
                $commentaireAfficher = "Précisions demandées : " . $motifDecisionHistorique;
            } elseif ($lastAction === 'SOUMISSION' || $dbStatus === 'INCONNU' || $dbStatus === 'NON_JUSTIFIEE') {
                $statut = 'En attente';
                $commentaireAfficher = $lastAction === 'SOUMISSION' ? "Soumis : Traitement en cours." : $commentaireAfficher;
            }


            $canUpload = false;

            // CAS 1 : Actions qui déverrouillent (demande de révision, etc.)
            if (in_array($lastAction, ['DEMANDE_PRECISIONS', 'RENVOI_FICHIER', 'AUTORISATION_RENVOI', 'AUTORISATION_HORS_DELAI'], true)) {
                $canUpload = true;
            }

            // CAS 2 : Première soumission (jamais de justificatif envoyé)
            if ($justificatifId === null && $lastAction === null && $dbStatus === 'NON_JUSTIFIEE') {
                $canUpload = true;
            }

            // CAS 3 : Bloqué si verrouillé
            if ($verouilleStatus === 't') {
                $canUpload = false;
            }

            // CAS 4 : Bloqué si justificatif en attente (SOUMISSION)
            if ($lastAction === 'SOUMISSION') {
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

        $pdo = db();
        $sql_decl = "
    SELECT 
        j.id AS justificatif_id,
        j.date_debut_demande AS date_debut,
        j.date_fin_demande AS date_fin,
        j.motif_libre AS raison_demande,
        j.commentaire,
        j.nom_fichier_original, -- AJOUTÉ : Pour vérifier la présence d'un fichier
        (SELECT hd.action FROM HistoriqueDecision hd 
         WHERE hd.id_justificatif = j.id 
         ORDER BY hd.date_action DESC LIMIT 1) as last_action,
        (SELECT COUNT(*) FROM JustificatifAbsence ja WHERE ja.id_justificatif = j.id) as nb_absences_liees
    FROM Justificatif j
    WHERE j.id_utilisateur = :uid
    AND j.date_debut_demande IS NOT NULL
    ORDER BY j.date_debut_demande DESC
";

        $st_decl = $pdo->prepare($sql_decl);
        $st_decl->execute([':uid' => $userId]);
        $declarations = $st_decl->fetchAll(\PDO::FETCH_ASSOC);


        foreach ($declarations as $decl) {

            if (in_array($decl['last_action'], ['ACCEPTATION', 'REJET'])) {
                continue;
            }

            $dateRange = $decl['date_debut'] . ' → ' . $decl['date_fin'];

            // Déterminer le statut exact pour l'étudiant
            $action = $decl['last_action'];
            $statut = 'En attente';
            if (in_array($action, ['DEMANDE_PRECISIONS', 'RENVOI_FICHIER', 'AUTORISATION_RENVOI'])) {
                $statut = 'En révision';
            }

            $motifDisplay = 'DÉCLARATION : ' . ($decl['raison_demande'] ?? 'Absence déclarée');
            $commentaireAffiche = !empty($decl['commentaire']) ? $decl['commentaire'] : ($statut === 'En révision' ? "Précisions demandées" : "Déclaration en attente");

            $out[] = [
                'absence_id'      => 0,
                'date'            => $dateRange,
                'motif'           => $motifDisplay,
                'justificatif_id' => (int)$decl['justificatif_id'],
                'statut'          => $statut,
                'commentaire'     => $commentaireAffiche,
                'is_range'        => true,
                'has_file'        => !empty($decl['nom_fichier_original']),
                'can_upload'      => ($statut === 'En révision') // Permet de renvoyer un fichier
            ];
        }

        return $out;
    }


    public static function insertJustificatif($absenceId, $userId, $originalName, $mime, $binaryContent, string $commentaire = '', string $motifLibre = '')
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("
                INSERT INTO Justificatif (fichier, commentaire, id_utilisateur, nom_fichier_original, type_mime, motif_libre)
                VALUES (decode(:f, 'base64'), :comm, :u, :n, :m, :motifL)
                RETURNING id
            ");

            // Encoder le contenu binaire en base64 pour éviter les problèmes d'encodage
            $st->bindValue(':f', base64_encode($binaryContent), \PDO::PARAM_STR);
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

        // On force l'affichage des erreurs pour ce bloc
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        try {
            // 1. Insertion simple sans transaction complexe pour tester
            $sql = "INSERT INTO Justificatif (fichier, commentaire, id_utilisateur, nom_fichier_original, type_mime, motif_libre, date_debut_demande, date_fin_demande)
                VALUES (:f, :comm, :u, :n, :m, :motifL, :dd, :df)
                RETURNING id";

            $st = $pdo->prepare($sql);


            $st->bindValue(':f', $binaryContent, $binaryContent === null ? \PDO::PARAM_NULL : \PDO::PARAM_LOB);
            $st->bindValue(':comm', $commentaire);
            $st->bindValue(':u', (int)$userId, \PDO::PARAM_INT);
            $st->bindValue(':n', $originalName);
            $st->bindValue(':m', $mime);
            $st->bindValue(':motifL', $motifLibre);
            $st->bindValue(':dd', $dateDebut);
            $st->bindValue(':df', $dateFin);

            $st->execute();
            $jid = (int)$st->fetchColumn();

            // 2. Insertion Historique (séparée)
            $sqlHist = "INSERT INTO HistoriqueDecision (action, id_justificatif, id_auteur, motif_decision) VALUES ('SOUMISSION', ?, ?, ?)";
            $pdo->prepare($sqlHist)->execute([$jid, $userId, "Déclaration du $dateDebut au $dateFin"]);

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

    public static function linkJustificatifToAbsences(int $justifId, int $userId, string $dateDebut, string $dateFin): int {
        $pdo = db();
        // AUCUNE transaction ici, on utilise celle du parent.

        $sql = "SELECT a.id FROM Absence a JOIN Seance s ON s.id = a.id_seance
            WHERE a.id_utilisateur = :uid AND a.justification IN ('INCONNU', 'NON_JUSTIFIEE')
            AND s.date BETWEEN :dd AND :df
            AND NOT EXISTS (SELECT 1 FROM JustificatifAbsence ja JOIN HistoriqueDecision hd ON hd.id_justificatif = ja.id_justificatif
                            WHERE ja.id_absence = a.id AND hd.action IN ('SOUMISSION', 'ACCEPTATION'))";

        $st = $pdo->prepare($sql);
        $st->execute([':uid' => $userId, ':dd' => $dateDebut, ':df' => $dateFin]);
        $ids = $st->fetchAll(\PDO::FETCH_COLUMN);

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
          AND j.date_debut_demande IS NOT NULL
          -- On récupère la DERNIÈRE action
          AND hd.id = (
              SELECT MAX(id) FROM HistoriqueDecision WHERE id_justificatif = j.id
          )
          -- On affiche si c'est en attente (SOUMISSION) ou en révision (DEMANDE_PRECISIONS)
          AND hd.action IN ('SOUMISSION', 'DEMANDE_PRECISIONS', 'RENVOI_FICHIER', 'AUTORISATION_RENVOI')
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
    public static function updateJustificatif($justifId, $userId, $name, $mime, $content): bool
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // 1. Mise à jour du fichier et déverrouillage
            $st = $pdo->prepare("
            UPDATE Justificatif 
            SET fichier = decode(:f, 'base64'), 
                nom_fichier_original = :n, 
                type_mime = :m, 
                verouille = TRUE,
                verouille_date = NOW()
            WHERE id = :id AND id_utilisateur = :u
        ");
            $st->bindValue(':f', base64_encode($content), \PDO::PARAM_STR);
            $st->bindValue(':n', $name, \PDO::PARAM_STR);
            $st->bindValue(':m', $mime, \PDO::PARAM_STR);
            $st->bindValue(':id', $justifId, \PDO::PARAM_INT);
            $st->bindValue(':u', $userId, \PDO::PARAM_INT);
            $st->execute();

            // 2. Ajouter une ligne dans l'historique pour signaler le renvoi
            $st2 = $pdo->prepare("
            INSERT INTO HistoriqueDecision (action, id_justificatif, id_auteur, motif_decision)
            VALUES ('SOUMISSION', :j, :u, 'Nouveau fichier transmis après révision')
        ");
            $st2->execute([':j' => $justifId, ':u' => $userId]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }
}