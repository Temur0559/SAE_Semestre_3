<?php
declare(strict_types=1);

final class UploadValidator {

    // Taille maximale : 5 MB
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;

    // Types MIME autorisés
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/jpg'
    ];

    // Extensions autorisées
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

    /**
     * Valide un fichier uploadé
     * @param array $file Le tableau $_FILES['nom_du_champ']
     * @return array Liste des erreurs (vide si tout est OK)
     */
    public static function validate(array $file): array {
        $errors = [];

        // Vérifier qu'un fichier a été uploadé
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Erreur lors de l'upload du fichier.";
            return $errors;
        }

        // Vérifier la taille
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = "Le fichier est trop volumineux. Taille maximale : 5 MB.";
        }

        if ($file['size'] === 0) {
            $errors[] = "Le fichier est vide.";
        }

        // Vérifier le type MIME réel du fichier
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $errors[] = "Type de fichier non autorisé. Seuls PDF, JPG et PNG sont acceptés. Détecté : {$mimeType}";
        }

        // Vérifier l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $errors[] = "Extension de fichier non autorisée : .{$extension}";
        }

        // Vérifier que le fichier est bien uploadé (pas une tentative d'attaque)
        if (!is_uploaded_file($file['tmp_name'])) {
            $errors[] = "Le fichier n'est pas un fichier uploadé valide.";
        }

        return $errors;
    }

    /**
     * Retourne un nom de fichier sécurisé
     */
    public static function sanitizeFilename(string $filename): string {
        // Garder uniquement l'extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Générer un nom unique
        return uniqid('justif_', true) . '.' . $extension;
    }
}