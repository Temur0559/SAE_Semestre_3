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

    public function testFilterAbsencesByStatus(): void
    {
        // Arrange
        $studentId = 1;

        // Act : Filtrer uniquement les absences "en attente"
        $absencesEnAttente = AbsenceModel::getAbsencesForStudent($studentId, 'attente');

        // Assert
        $this->assertIsArray($absencesEnAttente);

        // Vérifier que le filtre fonctionne
        foreach ($absencesEnAttente as $absence) {
            $statut = strtolower($absence['statut']);
            $this->assertStringContainsString('attente', $statut,
                "Toutes les absences devraient être 'en attente'");
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
}