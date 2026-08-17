<?php
/**
 * views/layout.php
 *
 * Layout commun a toutes les pages APRES connexion : head (CSS), nav laterale,
 * et un point d'insertion pour le contenu de la vue courante.
 *
 * Recoit :
 *   $vueActuelle   -> string (ex: 'pos', 'dettes', ...) pour surligner le bon lien nav
 *   $utilisateur   -> Utilisateur connecte (pour afficher son nom/role)
 *   $contenuHtml   -> HTML deja genere par le Controller de la vue courante
 *
 * Les liens de nav ne sont affiches QUE si le role connecte y a droit,
 * fidele au rolePermissions deja pense dans le HTML/JS d'origine :
 *   admin      -> dashboard, pos, dettes, supplies, catalog
 *   vente      -> pos, dettes
 *   stock      -> supplies, catalog
 *   inventaire -> catalog
 */

require_once __DIR__ . '/../src/Model/Enum/Role.php';

$role = $utilisateur->getRole();

$permissions = [
    Role::ADMIN->value      => ['dashboard', 'pos', 'dettes', 'supplies', 'catalog'],
    Role::VENTE->value      => ['pos', 'dettes'],
    Role::STOCK->value      => ['supplies', 'catalog'],
    Role::INVENTAIRE->value => ['catalog'],
];

$vuesAutorisees = $permissions[$role->value] ?? [];

function navLink(string $vue, string $label, string $vueActuelle, array $vuesAutorisees): string
{
    if (!in_array($vue, $vuesAutorisees, true)) {
        return '';
    }
    $actif = $vue === $vueActuelle ? 'active' : '';
    return "<a href=\"?view={$vue}\" class=\"nav-item {$actif}\" style=\"text-decoration:none; display:block;\">{$label}</a>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StoreManager Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="app-container">
        <div class="sidebar" style="width: 220px; padding: 20px 12px;">
            <div style="font-weight: 800; font-size: 15px; color: var(--accent); padding: 0 12px 20px;">StoreManager Pro</div>

            <div class="nav-menu" style="display: flex; flex-direction: column; gap: 4px;">
                <?= navLink('dashboard', 'Tableau de Bord', $vueActuelle, $vuesAutorisees) ?>
                <?= navLink('pos', 'Ventes / POS', $vueActuelle, $vuesAutorisees) ?>
                <?= navLink('dettes', 'Gestion Dettes', $vueActuelle, $vuesAutorisees) ?>
                <?= navLink('supplies', 'Approvisionnements', $vueActuelle, $vuesAutorisees) ?>
                <?= navLink('catalog', 'Produits & Tiers', $vueActuelle, $vuesAutorisees) ?>
            </div>

            <div style="margin-top: 32px; padding: 12px; border-top: 1px solid var(--border-color);">
                <div style="font-size: 11px; color: var(--text-muted);">Connecté en tant que</div>
                <div style="font-weight: 700; font-size: 13px; margin: 4px 0;"><?= htmlspecialchars($utilisateur->getLogin()) ?></div>
                <div style="font-size: 10px; color: var(--accent); font-weight: 700; text-transform: uppercase;"><?= $role->libelle() ?></div>
                <form method="POST" action="/index.php" style="margin-top: 10px;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" style="background: none; border: 1px solid var(--border-color); color: var(--text-muted); font-size: 11px; padding: 6px 10px; border-radius: 8px; cursor: pointer; width: 100%;">Se déconnecter</button>
                </form>
            </div>
        </div>

        <main style="flex: 1; padding: 24px;">
            <?= $contenuHtml ?>
        </main>
    </div>
</body>
</html>