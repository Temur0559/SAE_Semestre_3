<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../connexion/Model/UserModel.php';

#Test de la fonctionnalité PRINCIPALE : Authentification

class AuthenticationTest extends TestCase
{

     #Test 1 : Connexion réussie avec identifiants valides

    public function testLoginSuccess(): void
    {
        // Arrange : Préparer les données
        $email = 'test.etudiant@uphf.fr';
        $password = 'TestPassword123!';

        // Act : Tenter la connexion
        $user = UserModel::authenticate($email, $password);

        // Assert : Vérifier que ça marche
        $this->assertNotNull($user, "L'utilisateur devrait être connecté");
        $this->assertEquals($email, $user['email'], "L'email devrait correspondre");
        $this->assertArrayHasKey('role', $user, "Le rôle devrait être présent");
    }

    #Test 2 : Connexion échouée avec mauvais mot de passe

    public function testLoginFailsWithWrongPassword(): void
    {
        // Arrange
        $email = 'test.etudiant@uphf.fr';
        $wrongPassword = 'MauvaisMotDePasse';

        // Act
        $user = UserModel::authenticate($email, $wrongPassword);

        // Assert
        $this->assertNull($user, "La connexion devrait échouer");
    }

    #Test 3 : Protection contre l'injection SQL

    public function testSqlInjectionBlocked(): void
    {
        // Arrange : Tentative d'injection SQL classique
        $maliciousEmail = "' OR '1'='1' --";
        $password = 'nimporte';

        // Act
        $user = UserModel::authenticate($maliciousEmail, $password);

        // Assert
        $this->assertNull($user, "L'injection SQL devrait être bloquée");
    }
}