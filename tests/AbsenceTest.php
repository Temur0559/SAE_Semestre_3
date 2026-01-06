<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../mesabsence/Model/AbsenceModel.php';

#Test de la gestion des absences

class AbsenceTest extends TestCase
{
#Test 1 : Récupération des absences d'un étudiant

    public function testGetStudentAbsences(): void
    {
        // Arrange : ID d'un étudiant de test
        $studentId = 1;

        // Act : Récupérer ses absences
        $absences = AbsenceModel::getAbsencesForStudent($studentId, 'tous');

        // Assert
        $this->assertIsArray($absences, "Le résultat devrait être un tableau");

        // Si des absences existent, vérifier la structure
        if (!empty($absences)) {
            $premiere = $absences[0];
            $this->assertArrayHasKey('absence_id', $premiere);
            $this->assertArrayHasKey('date', $premiere);
            $this->assertArrayHasKey('statut', $premiere);
        }
    }

    #Test 2 : Filtrage des absences par statut

    public function testFilterAbsencesByStatus()
    {
        $userId = 1; // ID de l'étudiant de test
        $filtre = 'en attente';
        $absences = AbsenceModel::getAbsencesForStudent($userId, $filtre);

        foreach ($absences as $absence) {
            $statut = strtolower($absence['statut']);

            // MODIFICATION : On accepte 'en attente' OU 'en révision'
            // car le filtre 'en attente' inclut désormais les dossiers à compléter
            $this->assertTrue(
                strpos($statut, 'attente') !== false || strpos($statut, 'révision') !== false,
                "Le statut devrait être 'en attente' ou 'en révision', reçu : " . $statut
            );
        }
    }

    #Test 3 : Récupération de l'identité d'un étudiant

    public function testGetStudentIdentity(): void
    {
        // Arrange
        $studentId = 1;

        // Act
        $identity = AbsenceModel::getIdentity($studentId);

        // Assert
        $this->assertIsArray($identity, "L'identité devrait être un tableau");
        $this->assertArrayHasKey('nom', $identity);
        $this->assertArrayHasKey('prenom', $identity);
        $this->assertArrayHasKey('ine', $identity);
    }
    public function testSubmitJustificatif() {
        $userId = 1;
        $absenceId = 10; // ID d'absence test
        $fileName = "certificat_test.pdf";
        $binaryContent = "%PDF-1.4 test content";
        $mimeType = "application/pdf";

        $justifId = AbsenceModel::insertJustificatif(
            $absenceId, $userId, $fileName, $mimeType, $binaryContent, "Commentaire test", "Maladie"
        );

        $this->assertIsInt($justifId);
        $this->assertGreaterThan(0, $justifId);
    }
}