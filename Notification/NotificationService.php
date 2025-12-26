<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Charger les variables d'environnement si pas déjà fait
if (!isset($_ENV['SMTP_HOST'])) {
    require_once __DIR__ . '/../vendor/autoload.php';
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    } catch (\Exception $e) {
        error_log("Impossible de charger .env pour les emails: " . $e->getMessage());
        throw new \RuntimeException("Configuration email manquante");
    }
}

final class NotificationService
{
    public static function sendEmail(string $recipientEmail, string $subject, string $bodyHtml, string $recipientName = ''): bool|string
    {
        $mail = new PHPMailer(true);

        try {
            // Debug SMTP
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = 'error_log';
            $mail->CharSet = 'UTF-8';

            // Configuration SMTP depuis .env
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'] ?? throw new \RuntimeException('SMTP_HOST manquant');
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'] ?? throw new \RuntimeException('SMTP_USERNAME manquant');
            $mail->Password = $_ENV['SMTP_PASSWORD'] ?? throw new \RuntimeException('SMTP_PASSWORD manquant');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = (int)($_ENV['SMTP_PORT'] ?? 465);

            // Options SSL sécurisées
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false
                )
            );

            $mail->SMTPKeepAlive = true;

            // Expéditeur et destinataire
            $mail->setFrom($_ENV['SMTP_USERNAME'], 'GESTION-ABS UPHF');
            $mail->addAddress($recipientEmail, $recipientName);

            // Contenu de l'e-mail
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = strip_tags($bodyHtml);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email non envoyé à {$recipientEmail}. Erreur Mailer: {$mail->ErrorInfo}");
            return $mail->ErrorInfo;
        }
    }
}