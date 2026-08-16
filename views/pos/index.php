<?php
/**
 * views/pos/index.php
 *
 * Cette vue est INCLUSE par POSController::handle() via require.
 * Elle recoit automatiquement les variables suivantes (definies dans le controller) :
 *   $clients            -> Client[]  (pour le <select> client)
 *   $produits           -> Produit[] (pour le <select> article)
 *   $commandesRecentes  -> tableau ['commande' => array, 'lignes' => array][]
 *   $messageErreur      -> string|null
 *   $messageSucces      -> string|null
 *
 * IMPORTANT : ce fichier reprend la structure de ton HTML/CSS existant
 * (storemanager_pro_app.html, section #view-pos). Le CSS (variables --accent,
 * --success, classes .panel-card, .form-control, .debt-table, etc.) N'EST PAS
 * redefini ici : il doit venir de ton fichier CSS commun (ex: public/assets/style.css),
 * inclus dans le layout principal qui englobe cette vue.
 */
?>

<div id="view-pos" class="view-section">

    <?php if ($messageErreur): ?>
        <div class="panel-card" style="border-left: 4px solid var(--danger); padding: 12px 16px; margin-bottom: 16px; color: #fca5a5;">
            ⚠️ <?= htmlspecialchars($messageErreur) ?>
        </div>
    <?php endif; ?>

    <?php if ($messageSucces): ?>
        <div class="panel-card" style="border-left: 4px solid var(--success); padding: 12px 16px; margin-bottom: 16px; color: #86efac;">
            ✅ <?= htmlspecialchars($messageSucces) ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 600px 1fr; gap: 32px; align-items: start; margin-bottom: 32px;">

        <!-- ===== Formulaire Nouvelle Vente ===== -->
        <div class="panel-card" style="margin-bottom: 0; padding: 24px; border: 1px solid rgba(59, 130, 246, 0.2); position: sticky; top: 24px;">
            <div class="panel-title" style="display: flex; justify-content: space-between; align-items: center;">
                <span>🛒 Nouvelle Vente</span>
                <span style="font-size: 11px; color: var(--text-muted);">Terminal POS</span>
            </div>

            <form method="POST" action="" id="order-creation-form">
                <input type="hidden" name="action" value="create_order">

                <div class="form-group">
                    <label for="client-select">Client Acheteur</label>
                    <select name="client_id" id="client-select" class="form-control" onchange="updateClientLimitInfo()">
                        <option value="">-- Sélectionner un client --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client->getId() ?>" data-limit="<?= $client->getLimiteCredit() ?>">
                                <?= htmlspecialchars($client->getNomComplet()) ?> (<?= htmlspecialchars($client->getTelephone() ?? '—') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span id="credit-limit-info" style="font-size:11px; color:var(--text-muted); font-weight:600; margin-top:4px; display:block;"></span>
                </div>

                <!-- Selection article -->
                <div style="border-top: 1px dashed var(--border-color); padding-top: 16px; margin-top: 16px; margin-bottom: 16px;">
                    <label style="font-size: 12px; font-weight: 700; color: var(--accent); display: block; margin-bottom: 8px;">Sélection des Articles</label>
                    <div style="display: grid; grid-template-columns: 2.2fr 0.8fr auto; gap: 8px; align-items: flex-end; margin-bottom: 16px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="pos-item-select" style="font-size: 10px;">Article</label>
                            <select id="pos-item-select" class="form-control">
                                <?php foreach ($produits as $produit): ?>
                                    <?php
                                        $stock = $produit->getQuantiteStock();
                                        $icone = $produit->estEnRupture() ? ($stock === 0 ? '🔴' : '🟡') : '🟢';
                                    ?>
                                    <option
                                        value="<?= $produit->getId() ?>"
                                        data-price="<?= $produit->getPrixVente() ?>"
                                        data-name="<?= htmlspecialchars($produit->getNom()) ?>"
                                        data-stock="<?= $stock ?>"
                                    >
                                        <?= $icone ?> <?= htmlspecialchars($produit->getNom()) ?> (<?= $stock ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="pos-qty" style="font-size: 10px;">Qté</label>
                            <input type="number" id="pos-qty" class="form-control" value="1" min="1">
                        </div>
                        <button type="button" class="btn-submit" onclick="addToCart(event)" style="height: 38px; width: 38px;">+</button>
                    </div>

                    <!-- Panier -->
                    <table class="debt-table" style="font-size: 11px; margin-top: 16px;">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Qté</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cart-rows">
                            <tr id="empty-cart-row">
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 16px 0;">Panier vide. Ajoutez des articles.</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- IMPORTANT : ici seront injectes dynamiquement (par addToCart(), en JS)
                         des <input type="hidden" name="produit_id[]"> et <input type="hidden" name="quantite[]">
                         pour chaque ligne du panier. C'est CA que POSController lit dans $_POST. -->
                    <div id="hidden-cart-inputs"></div>
                </div>

                <!-- Total -->
                <div style="background: rgba(59, 130, 246, 0.08); border-radius: 16px; padding: 14px; text-align: center; margin-bottom: 20px;">
                    <span style="font-size: 10px; color: var(--text-muted);">Montant Total Net à Payer</span>
                    <div style="font-size: 24px; font-weight: 900; color: #60a5fa;">
                        <span id="montant_total_display_text">0</span> <span style="font-size: 14px;">FCFA</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="mode_reglement" style="font-size: 10px;">Règlement</label>
                        <select name="mode_reglement" id="mode_reglement" class="form-control">
                            <option value="Wave">Wave</option>
                            <option value="Orange Money">OM</option>
                            <option value="Especes">Espèces</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="pos-montant-verse" style="font-size: 10px;">Versé (Avance)</label>
                        <input type="number" name="montant_verse" id="pos-montant-verse" class="form-control" value="0" min="0">
                    </div>
                </div>

                <button type="submit" class="btn-submit btn-success" style="padding: 14px; font-weight: 800; width: 100%;">Valider la Vente</button>
            </form>
        </div>

        <!-- ===== Registre Général des Ventes ===== -->
        <div class="panel-card" style="margin-bottom: 0;">
            <div class="panel-title">Registre Général des Ventes & Commandes</div>
            <table class="debt-table" id="orders-main-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Total Facture</th>
                        <th>Règlement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commandesRecentes)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 16px 0;">
                                Aucune vente enregistrée pour le moment.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($commandesRecentes as $entree): ?>
                        <?php $c = $entree['commande']; ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--text-muted);">#CMD-<?= $c['id'] ?></td>
                            <td style="font-weight: 700;">
                                <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?>
                            </td>
                            <td style="font-weight: 800; color: var(--accent);"><?= number_format((float) $c['montant_total'], 0, ',', ' ') ?> F</td>
                            <td>
                                <?php if ($c['type_reglement'] === 'CREDIT'): ?>
                                    <span class="badge badge-danger">CRÉDIT</span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?= htmlspecialchars($c['type_reglement']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn-quick-action" onclick="toggleDetails('order-details-<?= $c['id'] ?>')">Lignes</button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5" style="padding: 0; border: none;">
                                <div class="details-drawer" id="order-details-<?= $c['id'] ?>" style="display:none;">
                                    <table class="debt-table" style="font-size: 11px;">
                                        <thead>
                                            <tr>
                                                <th>Produit</th><th>Qté</th><th>P.U.</th><th>Sous-total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($entree['lignes'] as $ligne): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($ligne['produit_nom']) ?></td>
                                                    <td><?= $ligne['quantite'] ?></td>
                                                    <td><?= number_format((float) $ligne['prix_unitaire'], 0, ',', ' ') ?> F</td>
                                                    <td style="font-weight: 700; color: var(--accent);">
                                                        <?= number_format($ligne['quantite'] * $ligne['prix_unitaire'], 0, ',', ' ') ?> F
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// ===== Gestion du panier cote client (JS) =====
// Adapte de ton JS existant, avec l'ajout des inputs caches
// produit_id[] / quantite[] necessaires pour que PHP recoive le panier.

let panier = []; // [{id, nom, prix, quantite}]

function addToCart(event) {
    event.preventDefault();
    const select = document.getElementById('pos-item-select');
    const qtyInput = document.getElementById('pos-qty');
    const option = select.options[select.selectedIndex];

    const id = option.value;
    const nom = option.dataset.name;
    const prix = parseFloat(option.dataset.price);
    const stock = parseInt(option.dataset.stock);
    const quantite = parseInt(qtyInput.value);

    if (quantite <= 0 || quantite > stock) {
        alert("Quantité invalide (stock disponible : " + stock + ")");
        return;
    }

    panier.push({ id, nom, prix, quantite });
    render();
}

function retirerDuPanier(index) {
    panier.splice(index, 1);
    render();
}

function render() {
    const tbody = document.getElementById('cart-rows');
    const hiddenContainer = document.getElementById('hidden-cart-inputs');

    if (panier.length === 0) {
        tbody.innerHTML = '<tr id="empty-cart-row"><td colspan="4" style="text-align:center;color:var(--text-muted);padding:16px 0;">Panier vide. Ajoutez des articles.</td></tr>';
        hiddenContainer.innerHTML = '';
        document.getElementById('montant_total_display_text').textContent = '0';
        return;
    }

    let total = 0;
    let rowsHtml = '';
    let hiddenHtml = '';

    panier.forEach((item, index) => {
        const sousTotal = item.prix * item.quantite;
        total += sousTotal;

        rowsHtml += `<tr>
            <td>${item.nom}</td>
            <td>${item.quantite}</td>
            <td>${sousTotal.toLocaleString()} F</td>
            <td><button type="button" class="btn-quick-action" onclick="retirerDuPanier(${index})">✕</button></td>
        </tr>`;

        // Ce sont CES deux inputs caches que POSController lit via $_POST['produit_id'] et $_POST['quantite']
        hiddenHtml += `<input type="hidden" name="produit_id[]" value="${item.id}">`;
        hiddenHtml += `<input type="hidden" name="quantite[]" value="${item.quantite}">`;
    });

    tbody.innerHTML = rowsHtml;
    hiddenContainer.innerHTML = hiddenHtml;
    document.getElementById('montant_total_display_text').textContent = total.toLocaleString();
}

function updateClientLimitInfo() {
    const select = document.getElementById('client-select');
    const option = select.options[select.selectedIndex];
    const info = document.getElementById('credit-limit-info');

    if (option && option.dataset.limit) {
        info.textContent = 'Limite de crédit autorisée : ' + parseInt(option.dataset.limit).toLocaleString() + ' F';
    } else {
        info.textContent = '';
    }
}

function toggleDetails(id) {
    const el = document.getElementById(id);
    el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
}

// Empeche la soumission si le panier est vide (securite cote client,
// la VRAIE verification se fait dans VenteService cote serveur)
document.getElementById('order-creation-form').addEventListener('submit', function (e) {
    if (panier.length === 0) {
        e.preventDefault();
        alert('Ajoutez au moins un article au panier avant de valider.');
    }
});
</script>