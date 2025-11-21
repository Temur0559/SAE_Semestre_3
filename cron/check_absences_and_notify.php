<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../connexion/config/db.php';
require_once __DIR__ . '/../mesabsence/Model/AbsenceModel.php';
require_once __DIR__ . '/../Notification/NotificationService.php';

final class NotificationCron {

    public function run() {
        echo "Démarrage des tâches de notification...\n";

        $this->processReminders();

        echo "Tâches de notification terminées.\n";
    }

    private function processReminders() {

        // 1. Rappel Initial (48h - pour toutes absences)
        try {
            $this->sendInitialReminders(
                \AbsenceModel::getAbsencesForInitialReminder(),
                "Rappel: Justification d'absence (48h restantes)",
                "Il vous reste moins de 48 heures (délai légal) pour justifier cette absence.",
                "Rappel Initial (48h)"
            );
        } catch (\Throwable $e) {
            error_log("Erreur CRON (Initial): " . $e->getMessage());
        }


        try {
            $this->sendReturnReminders(
                \AbsenceModel::getAbsencesForReturnReminder(),
                "🔔 Rappel : Votre obligation de justifier est réactivée",
                "Vous êtes revenu en cours. La période pour soumettre un justificatif pour votre absence précédente est maintenant active.",
                "Retour en cours (T+1h)"
            );
        } catch (\Throwable $e) {
            error_log("Erreur CRON (Retour): " . $e->getMessage());
        }
    }

    private function sendInitialReminders(array $absences, string $subject, string $baseMessage, string $type) {
        $count = 0;
        if (empty($absences)) {
            echo "Aucun rappel de type '{$type}' à envoyer.\n";
            return;
        }

        foreach ($absences as $absence) {
            $email = $absence['email'];
            $name = trim($absence['prenom'] . ' ' . $absence['nom']);
            $seanceDate = $absence['date'];
            $seanceMotif = $absence['motif_seance'];

            $body = "<p>Bonjour " . htmlspecialchars($name) . ",</p>"
                . "<p>Concernant votre absence au cours '<strong>" . htmlspecialchars($seanceMotif) . "</strong>' le <strong>" . htmlspecialchars($seanceDate) . "</strong>.</p>"
                . "<p>{$baseMessage} Veuillez soumettre votre justificatif via l'application dès que possible.</p>";

            $result = \NotificationService::sendEmail($email, $subject, $body, $name);

            if ($result === true) {
                error_log("Rappel '{$type}' envoyé à: " . $email);
                $count++;
            } else {
                error_log("Échec envoi rappel '{$type}' à " . $email . ". Erreur: " . $result);
            }
        }
        echo "{$count} rappels de type '{$type}' traités.\n";
    }

    private function sendReturnReminders(array $absences, string $subject, string $baseMessage, string $type) {
        $count = 0;
        if (empty($absences)) {
            echo "Aucun rappel de type '{$type}' à envoyer.\n";
            return;
        }

        foreach ($absences as $absence) {
            $email = $absence['email'];
            $name = trim($absence['prenom'] . ' ' . $absence['nom']);
            $absenceDate = $absence['absence_date'];
            $seanceMotif = $absence['motif_seance'];

            // On utilise la date de retour calculée dans la requête SQL
            $returnTime = new DateTime($absence['date_de_retour_effectif']);
            $returnTimeFormatted = $returnTime->format('d/m/Y à H:i');

            $body = "<p>Bonjour " . htmlspecialchars($name) . ",</p>"
                . "<p>{$baseMessage}</p>"
                . "<p>L'absence concernée est celle du cours '<strong>" . htmlspecialchars($seanceMotif) . "</strong>' le <strong>" . htmlspecialchars($absenceDate) . "</strong>.</p>"
                . "<p>Votre retour a été enregistré le <strong>{$returnTimeFormatted}</strong>. Vous pouvez maintenant justifier cette absence.</p>";

            $result = \NotificationService::sendEmail($email, $subject, $body, $name);

            if ($result === true) {
                error_log("Rappel '{$type}' envoyé à: " . $email);
                $count++;
            } else {
                error_log("Échec envoi rappel '{$type}' à " . $email . ". Erreur: " . $result);
            }
        }
        echo "{$count} rappels de type '{$type}' traités.\n";
    }
}


if (php_sapi_name() === 'cli') {
    (new NotificationCron())->run();
}