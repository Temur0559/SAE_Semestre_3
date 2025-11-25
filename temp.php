<?php
// php temp.php Utiliser ce script pour générer les hachages sécurisés


$mdp_etudiant = 'hash_etudiant';
$mdp_responsable = 'hash_responsable';
$mdp_enseignant = 'hash_enseignant';
$mdp_secretaire = 'hash_secretaire';


$hachage_etudiant = password_hash($mdp_etudiant, PASSWORD_DEFAULT);
$hachage_responsable = password_hash($mdp_responsable, PASSWORD_DEFAULT);
$hachage_enseignant = password_hash($mdp_enseignant, PASSWORD_DEFAULT);
$hachage_secretaire = password_hash($mdp_secretaire, PASSWORD_DEFAULT);


echo "--- HACHAGES SÉCURISÉS POUR LA BASE DE DONNÉES ---\n";
echo "Mot de passe clair ETUDIANT: " . $mdp_etudiant . "\n";
echo "Hachage sécurisé ETUDIANT: " . $hachage_etudiant . "\n\n";

echo "Mot de passe clair RESPONSABLE: " . $mdp_responsable . "\n";
echo "Hachage sécurisé RESPONSABLE: " . $hachage_responsable . "\n\n";

echo "Mot de passe clair ENSEIGNANT: " . $mdp_enseignant . "\n";
echo "Hachage sécurisé ENSEIGNANT: " . $hachage_enseignant . "\n\n";

echo "Mot de passe clair SECRETAIRE: " . $mdp_secretaire . "\n";
echo "Hachage sécurisé SECRETAIRE: " . $hachage_secretaire . "\n\n";


