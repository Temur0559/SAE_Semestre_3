<?php
declare(strict_types=1);


ini_set('display_errors','1'); ini_set('display_startup_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/Presenter/LoginPresenter.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $rawLogin = isset($_POST['identifiant']) ? trim($_POST['identifiant']) : '';
        if ($rawLogin === '' && isset($_POST['email'])) $rawLogin = trim($_POST['email']);

        // normalise : si pas de @ → ajoute @uphf.fr
        $email = $rawLogin;
        if ($email !== '' && strpos($email, '@') === false) $email .= '@uphf.fr';

        $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

        (new LoginPresenter())->handleLogin($email, $password);
    } else {
        header('Location: View/login.php'); exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo "<pre style='padding:16px;background:#111;color:#eee;font-family:monospace'>";
    echo "🔥 ERREUR SERVEUR 🔥\n\n";
    echo "Message : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . "\n";
    echo "Ligne   : " . $e->getLine() . "\n\n";
    echo "Trace :\n" . $e->getTraceAsString();
    echo "</pre>";
    exit;
}
