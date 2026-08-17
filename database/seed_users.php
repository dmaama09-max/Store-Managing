<?php
/**
 * database/seed_users.php
 *
 * A executer UNE SEULE FOIS, en ligne de commande, pour creer les 4 comptes
 * de demo (un par role) avec le mot de passe "demo1234" :
 *
 *   php database/seed_users.php
 *
 * Si un compte existe deja (meme login), il n'est PAS recree (evite les
 * doublons si tu relances le script par erreur).
 */

require_once __DIR__ . '/../src/Model/Enum/Role.php';
require_once __DIR__ . '/../src/Model/Entity/Utilisateur.php';
require_once __DIR__ . '/../src/Repository/UtilisateurRepository.php';

$repository = new UtilisateurRepository();

$comptesDemo = [
    ['login' => 'admin@storemanager.sn',      'role' => Role::ADMIN],
    ['login' => 'vente@storemanager.sn',      'role' => Role::VENTE],
    ['login' => 'stock@storemanager.sn',      'role' => Role::STOCK],
    ['login' => 'inventaire@storemanager.sn', 'role' => Role::INVENTAIRE],
];

$motDePasseParDefaut = 'demo1234';

foreach ($comptesDemo as $compte) {
    $existant = $repository->findByLogin($compte['login']);

    if ($existant !== null) {
        echo "- {$compte['login']} existe déjà, ignoré.\n";
        continue;
    }

    $hash = password_hash($motDePasseParDefaut, PASSWORD_DEFAULT);

    $utilisateur = new Utilisateur(
        login: $compte['login'],
        motDePasseHache: $hash,
        role: $compte['role'],
    );

    $repository->create($utilisateur);
    echo "✓ Créé : {$compte['login']} ({$compte['role']->libelle()})\n";
}

echo "\nTerminé. Tous les comptes utilisent le mot de passe : {$motDePasseParDefaut}\n";