<?php
declare(strict_types=1);

// NOTE: L'autoload de PHPMailer est maintenant géré par le point d'entrée (justification.php)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- CONFIGURATION CRITIQUE ---
// À des fins de sécurité, cette configuration devrait être dans un fichier
// non accessible ou dans des variables d'environnement.
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_USERNAME = 'noreplyiutmaubeuge@gmail.com';
const SMTP_PASSWORD = 'xzeg qfxy zhpc hbwg'; // Clé d'application
const SMTP_PORT = 587; // Port TLS
// -----------------------------

final class NotificationService {

    public static function sendEmail(string $recipientEmail, string $subject, string $bodyHtml, string $recipientName = ''): bool {

        $mail = new PHPMailer(true);

        try {
            // CORRECTION: AJOUT de la ligne CharSet pour forcer l'UTF-8 et corriger les accents.
            $mail->CharSet = 'UTF-8';

            // Configuration du serveur SMTP
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Utilisation de TLS
            $mail->Port       = SMTP_PORT;

            // Expéditeur et destinataire
            $mail->setFrom(SMTP_USERNAME, 'GESTION-ABS UPHF');
            $mail->addAddress($recipientEmail, $recipientName);

            // Contenu de l'e-mail
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;
            $mail->AltBody = strip_tags($bodyHtml); // Version texte brut

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Enregistre l'erreur dans les logs au lieu d'afficher
            error_log("Email non envoyé à {$recipientEmail}. Erreur Mailer: {$mail->ErrorInfo}");
            return false;
        }
    }
}