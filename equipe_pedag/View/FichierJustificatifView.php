<?php

// Permet d'afficher ou de lancer le téléchargement d'un fichier
class FichierJustificatifView {

    public function render(array $doc) {

        $fileData = $doc['fichier'];
        $mime = $doc['type_mime'] ?? 'application/octet-stream';
        $name = $doc['nom_fichier_original'] ?? 'document';

        // En-têtes HTTP
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . addslashes($name) . '"');
        header('Content-Length: ' . strlen($fileData));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=120');

        // Envoi du fichier binaire
        echo $fileData;
        exit;
    }
}