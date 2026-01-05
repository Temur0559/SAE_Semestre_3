<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../mesabsence/UploadValidator.php';

#Test de la validation des fichiers uploadés

class FileValidationTest extends TestCase
{
    #Test 1 : Fichier trop gros est rejeté

    public function testRejectOversizedFile(): void
    {
        // Arrange : Simuler un fichier de 10 MB (limite = 5 MB)
        $file = [
            'name' => 'grosfichier.pdf',
            'tmp_name' => '/tmp/fake',
            'size' => 10 * 1024 * 1024, // 10 MB
            'error' => UPLOAD_ERR_OK,
        ];

        // Act
        $errors = UploadValidator::validate($file);

        // Assert
        $this->assertNotEmpty($errors, "Un fichier trop gros devrait être rejeté");
        $this->assertStringContainsString('volumineux', implode(' ', $errors));
    }

    #Test 2 : Extension dangereuse est rejetée

    public function testRejectDangerousExtension(): void
    {
        // Arrange : Fichier .exe (dangereux)
        $file = [
            'name' => 'virus.exe',
            'tmp_name' => '/tmp/fake',
            'size' => 1000,
            'error' => UPLOAD_ERR_OK,
        ];

        // Act
        $errors = UploadValidator::validate($file);

        // Assert
        $this->assertNotEmpty($errors, "Un .exe devrait être rejeté");
    }

    #Test 3 : Génération de nom sécurisé

    public function testSecureFilenameGeneration(): void
    {
        // Arrange : Nom de fichier malveillant
        $dangerousName = '../../../etc/passwd.pdf';

        // Act
        $safeName = UploadValidator::sanitizeFilename($dangerousName);

        // Assert
        $this->assertStringStartsWith('justif_', $safeName, "Le nom devrait commencer par 'justif_'");
        $this->assertStringNotContainsString('..', $safeName, "Ne devrait pas contenir '..'");
        $this->assertStringNotContainsString('/', $safeName, "Ne devrait pas contenir '/'");
    }
}