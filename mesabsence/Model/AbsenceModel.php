<?php
declare(strict_types=1);

require_once __DIR__ . '/../../connexion/config/db.php';

final class AbsenceModel
{

    public static function getIdentity($userId)
    {
        $sql = "
            SELECT u.nom, u.prenom, u.datenaissance, u.ine,
                   COALESCE(p.libelle || ' — ' || p.public, 'BUT INFORMATIQUE — FI') AS programme
            FROM utilisateur u
            LEFT JOIN programme p ON p.id = (
                SELECT p2.id
                FROM programme p2
                ORDER BY p2.id ASC LIMIT 1
            )
            WHERE u.id = :id
        ";
        $st = db()->prepare($sql);
        $st->execute([':id' => $userId]);
        $row = $st->fetch();
        if (!$row) $row = ['nom'=>'','prenom'=>'','datenaissance'=>null,'ine'=>'','programme'=>''];
        // Mise en forme type maquette
        $naissance = $row['datenaissance'] ? ('Né(e) le ' . $row['datenaissance']) : 'Né(e) le —/—/—';
        return [
            'nom'      => $row['nom'],
            'prenom'   => $row['prenom'],
            'naissance'=> $naissance,
            'ine'      => $row['ine'] ?: 'INE',
            'program'  => $row['programme'],
        ];
    }


    public static function getAbsencesForStudent($userId, $filtre /* 'tous'|'accepté'|... */)
    {

        $sql = "
          SELECT a.absence_id, a.date, a.cours,
                 a.justificatif_id, a.last_action, a.last_comment,
                 a.can_redeposer
          FROM v_absences_etudiant a
          WHERE a.utilisateur_id = :uid
          ORDER BY a.date ASC, a.absence_id ASC
        ";
        $st = db()->prepare($sql);
        $st->execute([':uid' => $userId]);

        $rows = $st->fetchAll();
        $out  = [];

        foreach ($rows as $r) {

            $statut = 'En attente';
            if ($r['last_action'] === 'ACCEPTATION')            $statut = 'Accepté';
            elseif ($r['last_action'] === 'REJET')              $statut = 'Rejeté';
            elseif ($r['last_action'] === 'DEMANDE_PRECISIONS') $statut = 'En révision';
            elseif ($r['last_action'] === 'RENVOI_FICHIER')     $statut = 'En révision';


            $f = strtolower($filtre ?: 'tous');
            $s = strtolower($statut);
            if ($f !== 'tous' && $f !== $s) continue;

            $out[] = [
                'absence_id'      => (int)$r['absence_id'],
                'date'            => (string)$r['date'],
                'motif'           => (string)$r['cours'],           // colonne à afficher
                'justificatif_id' => $r['justificatif_id'] ? (int)$r['justificatif_id'] : null,
                'statut'          => $statut,
                'commentaire'     => $r['last_comment'],
                'can_upload'      => (bool)$r['can_redeposer'] || !$r['justificatif_id'],
            ];
        }
        return $out;
    }


    public static function insertJustificatif($absenceId, $userId, $originalName, $mime, $binaryContent)
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("
                INSERT INTO justificatif (fichier, commentaire, id_utilisateur, original_filename, mime_type)
                VALUES (:f, '', :u, :n, :m)
                RETURNING id
            ");
            $st->bindValue(':f', $binaryContent, \PDO::PARAM_LOB);
            $st->bindValue(':u', $userId, \PDO::PARAM_INT);
            $st->bindValue(':n', $originalName, \PDO::PARAM_STR);
            $st->bindValue(':m', $mime, \PDO::PARAM_STR);
            $st->execute();
            $jid = (int)$st->fetchColumn();

            $st2 = $pdo->prepare("INSERT INTO justificatifabsence (justificatif_id, absence_id) VALUES (:j,:a)");
            $st2->execute([':j'=>$jid, ':a'=>$absenceId]);

            $st3 = $pdo->prepare("
                INSERT INTO historiquedecision (action, justificatif_id, auteur_id, motifdecision)
                VALUES ('SOUMISSION', :j, :u, 'Soumission initiale')
            ");
            $st3->execute([':j'=>$jid, ':u'=>$userId]);

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
            SELECT id, fichier, original_filename, mime_type
            FROM justificatif
            WHERE id = :id
        ");
        $st->execute([':id'=>$justifId]);
        $j = $st->fetch();
        return $j ? $j : null;
    }
}
