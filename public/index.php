<?php
/**
 * public/index.php
 *
 * LE point d'entree unique de l'application. C'est le fichier que le
 * navigateur charge (ex: http://localhost/index.php?view=pos).
 *
 * Deroulement :
 * 1. Demarre la session et verifie qui est connecte (AuthManager)
 * 2. Si personne n'est connecte -> affiche le formulaire de login, stop
 * 3. Sinon -> determine quelle vue afficher (?view=xxx), verifie que le
 *    role connecte y a droit, appelle le bon Controller, et insere le
 *    resultat dans le layout commun (nav + CSS)
 */

require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/AuthManager.php';
require_once __DIR__ . '/../src/Controller/AuthController.php';
require_once __DIR__ . '/../src/Controller/POSController.php';
require_once __DIR__ . '/../src/Controller/DetteController.php';
require_once __DIR__ . '/../src/Controller/SupplyController.php';
require_once __DIR__ . '/../src/Model/Enum/Role.php';

$auth = new AuthManager();

// ----- Deconnexion -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    (new AuthController())->handleLogout(); // redirige et fait exit lui-meme
}

// ----- Pas connecte : login uniquement -----
if (!$auth->estConnecte()) {
    (new AuthController())->handleLogin(); // affiche le formulaire, ou traite le POST login
    exit;
}

// ----- A partir d'ici, un utilisateur est connecte -----

function vueParDefautPourRole(Role $role): string
{
    return match ($role) {
        Role::ADMIN      => 'dashboard',
        Role::VENTE      => 'pos',
        Role::STOCK      => 'supplies',
        Role::INVENTAIRE => 'catalog',
    };
}

// Quelle vue demande-t-on ? (?view=pos dans l'URL, sinon la vue par defaut du role)
$vueActuelle = $_GET['view'] ?? vueParDefautPourRole($auth->roleConnecte());

// Qui a le droit de voir quoi (le meme mapping que celui deja pense en JS
// dans le HTML d'origine, maintenant applique cote SERVEUR, la ou ca compte
// vraiment pour la securite).
$rolesAutorisesParVue = [
    'dashboard' => [Role::ADMIN],
    'pos'       => [Role::ADMIN, Role::VENTE],
    'dettes'    => [Role::ADMIN, Role::VENTE],
    'supplies'  => [Role::ADMIN, Role::STOCK],
    'catalog'   => [Role::ADMIN, Role::STOCK, Role::INVENTAIRE],
];

if (!isset($rolesAutorisesParVue[$vueActuelle])) {
    $vueActuelle = vueParDefautPourRole($auth->roleConnecte());
}

try {
    $auth->checkAccess(...$rolesAutorisesParVue[$vueActuelle]);
} catch (Exception $e) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;padding:40px;">⛔ ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// ----- Appel du bon Controller, dont la sortie est capturee -----
// (ob_start/ob_get_clean = "capture tout ce qui serait affiche" dans une
// variable, plutot que de l'envoyer directement au navigateur, pour pouvoir
// l'inserer PROPREMENT a l'interieur du layout commun juste apres.)
$controllersParVue = [
    'pos'      => POSController::class,
    'dettes'   => DetteController::class,
    'supplies' => SupplyController::class,
];

ob_start();

if (isset($controllersParVue[$vueActuelle])) {
    $controllerClass = $controllersParVue[$vueActuelle];
    (new $controllerClass())->handle();
} else {
    // dashboard / catalog : pas encore de Controller dedie, vue statique pour l'instant
    require __DIR__ . '/../views/' . $vueActuelle . '/index.php';
}

$contenuHtml = ob_get_clean();

// ----- Rendu final : layout commun (nav + CSS) + contenu de la vue -----
$utilisateur = $auth->utilisateurConnecte();
require __DIR__ . '/../views/layout.php';