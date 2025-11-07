<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Changement: Port 465 et chiffrement SSL implicite
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_USERNAME = 'noreplyiutmaubeuge@gmail.com';
const SMTP_PASSWORD = 'xzeg qfxy zhpc hbwg';
const SMTP_PORT = 465; // CHANGEMENT: Port pour SMTPS/SSL
// -----------------------------

final class NotificationService {

    public static function sendEmail(string $recipientEmail, string $subject, string $bodyHtml, string $recipientName = ''): bool|string {

        $mail = new PHPMailer(true);

        try {
            // Lignes de debug conservées pour le cas où l'échec d'authentification apparaît après la connexion
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = 'error_log';

            $mail->CharSet = 'UTF-8';

            // Configuration du serveur SMTP
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            // CHANGEMENT: Utiliser SMTPS (SSL) pour le port 465
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // <-- CHANGEMENT DE CHIFFREMENT
            $mail->Port       = SMTP_PORT;

            // Expéditeur et destinataire
            $mail->setFrom(SMTP_USERNAME, 'GESTION-ABS UPHF');
            $mail->addAddress($recipientEmail, $recipientName);

            // Contenu de l'e-mail
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;
            $mail->AltBody = strip_tags($bodyHtml);

            $mail->send();
            return true;
        } catch (Exception $e) {

            error_log("Email non envoyé à {$recipientEmail}. Erreur Mailer: {$mail->ErrorInfo}");
            return $mail->ErrorInfo;
        }
    }
}