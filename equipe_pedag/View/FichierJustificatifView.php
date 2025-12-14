<?php

class FichierJustificatifView {

    public function render(array $doc) {

        // En-têtes HTTP
        header('Content-Type: ' . $doc['type_mime']);
        header('Content-Disposition: inline; filename="' . addslashes($doc['nom_fichier_original']) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=120');

        // Envoi du fichier binaire
        echo $doc['fichier'];
        exit;
    }
}
