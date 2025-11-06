<?php
$motDePasseClair = 'hash_etudiant';
$hachage = password_hash($motDePasseClair, PASSWORD_DEFAULT);

echo "Mot de passe clair : " . $motDePasseClair . "\n";
echo "Hachage sécurisé : " . $hachage . "\n";
// Copiez la valeur du hachage affichée dans le terminal