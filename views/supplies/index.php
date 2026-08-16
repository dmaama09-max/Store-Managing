<?php
/**
 * views/supplies/index.php
 *
 * Recoit de SupplyController::handle() :
 *   $bonsAvecLignes -> [['bon' => array, 'lignes' => array], ...]
 *   $fournisseurs, $produits -> pour le formulaire de creation rapide (dashboard)
 *   $stats -> ['cout_total_entrees', 'nb_bl_receptionnes', 'nb_fournisseurs']
 *   $messageErreur / $messageSucces
 */
?>

<div id="view-supplies" class="view-section">

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

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--accent);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Coût Total des Entrées</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;">
                    <?= number_format($stats['cout_total_entrees'], 0, ',', ' ') ?> F
                </div>
            </div>
            <span style="font-size: 24px;">📥</span>
        </div>
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--warning);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Bons de Réception (BL)</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;">
                    <?= $stats['nb_bl_receptionnes'] ?> BL reçus
                </div>
            </div>
            <span style="font-size: 24px;">📄</span>
        </div>
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--success);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Fournisseurs Actifs</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;">
                    <?= $stats['nb_fournisseurs'] ?> entreprise<?= $stats['nb_fournisseurs'] > 1 ? 's' : '' ?>
                </div>
            </div>
            <span style="font-size: 24px;">🤝</span>
        </div>
    </div>

    <!-- Registre -->
    <div class="panel-card" style="padding: 20px; margin-bottom: 0;">
        <div class="panel-title" style="font-size: 15px; margin-bottom: 16px;">Bordereaux de Livraison (Réceptions)</div>
        <table class="debt-table" id="supplies-main-table" style="font-size: 12px;">
            <thead>
                <tr>
                    <th>Réf BL</th>
                    <th>Fournisseur</th>
                    <th>Valeur Lot</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bonsAvecLignes)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 16px 0;">
                            Aucun bon de livraison en attente.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($bonsAvecLignes as $entree): ?>
                    <?php $bon = $entree['bon']; ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--text-muted); padding: 8px 0;">BL-<?= $bon['id'] ?></td>
                        <td style="padding: 8px 0;">
                            <?= htmlspecialchars($bon['fournisseur_nom']) ?>
                            <div style="font-size:10px; color:var(--text-muted);">Tél : <?= htmlspecialchars($bon['fournisseur_telephone']) ?></div>
                        </td>
                        <td style="font-weight: 800; color: var(--accent); padding: 8px 0;">
                            <?= number_format($bon['valeur_totale'], 0, ',', ' ') ?> F
                        </td>
                        <td style="padding: 8px 0;">
                            <span class="badge badge-warning">EN COURS</span>
                        </td>
                        <td style="padding: 8px 0; display: flex; gap: 6px;">
                            <button type="button" class="btn-quick-action" onclick="toggleDetails('supply-details-<?= $bon['id'] ?>')">Lignes</button>
                            <button type="button" class="btn-quick-action" style="border-color: var(--success); color: var(--success); background: rgba(52, 211, 153, 0.05);" onclick="toggleDetails('supply-receive-drawer-<?= $bon['id'] ?>')">Réceptionner</button>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" style="padding: 0; border: none;">

                            <!-- Drawer lignes -->
                            <div class="details-drawer" id="supply-details-<?= $bon['id'] ?>" style="display:none;">
                                <div style="font-weight: 700; font-size: 11px; color: var(--accent); margin-bottom: 6px;">Détails Réception :</div>
                                <table class="debt-table" style="font-size: 10px;">
                                    <thead>
                                        <tr><th>Produit</th><th>Qté Livrée</th><th>Coût Unitaire</th><th>Total</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($entree['lignes'] as $ligne): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($ligne['produit_nom']) ?></td>
                                                <td><?= $ligne['quantite'] ?></td>
                                                <td><?= number_format((float) $ligne['prix_achat_unitaire'], 0, ',', ' ') ?> F</td>
                                                <td style="font-weight: 700; color: var(--accent);">
                                                    <?= number_format($ligne['quantite'] * $ligne['prix_achat_unitaire'], 0, ',', ' ') ?> F
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Drawer reception -->
                            <div class="details-drawer" id="supply-receive-drawer-<?= $bon['id'] ?>" style="display:none; border: 1px solid rgba(52, 211, 153, 0.3); background: linear-gradient(180deg, rgba(11, 15, 25, 0.95) 0%, rgba(11, 15, 25, 0.98) 100%); border-radius: 14px; padding: 18px 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); max-width: 850px; margin: 12px 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-size: 16px;">📦</span>
                                        <span style="font-weight: 800; font-size: 13px; color: var(--text-main);">
                                            Réceptionner le BL — <span style="color: var(--accent);">BL-<?= $bon['id'] ?></span>
                                        </span>
                                    </div>
                                    <div style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; color: var(--warning);">
                                        Fournisseur : <?= htmlspecialchars($bon['fournisseur_nom']) ?>
                                    </div>
                                </div>

                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="receive_supply">
                                    <input type="hidden" name="approvisionnement_id" value="<?= $bon['id'] ?>">

                                    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                                        <?php foreach ($entree['lignes'] as $ligne): ?>
                                            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
                                                <div>
                                                    <div style="font-weight: 700; font-size: 13px; color: white;"><?= htmlspecialchars($ligne['produit_nom']) ?></div>
                                                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                                        Quantité théorique commandée : <strong style="color: var(--text-main);"><?= $ligne['quantite'] ?></strong>
                                                    </div>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <label style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Qté Reçue :</label>
                                                    <input type="number" name="quantites_livrees[<?= $ligne['id'] ?>]" class="form-control"
                                                           value="<?= $ligne['quantite'] ?>" min="0" required
                                                           style="width: 100px; padding: 6px 10px; font-size: 13px; font-weight: 700; text-align: center; background: #0b0f1a;">
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div style="display: flex; justify-content: flex-end;">
                                        <button type="submit" class="btn-submit btn-success" style="padding: 11px 24px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 10px;">
                                            ✓ Valider la Réception en Stock
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Creation rapide d'un BL (normalement sur le Dashboard, place ici temporairement) -->
    <div class="panel-card" style="margin-top: 24px;">
        <div class="panel-title">Créer un Nouveau Bon de Livraison</div>
        <form method="POST" action="" style="display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr auto; gap: 8px; align-items: flex-end;">
            <input type="hidden" name="action" value="quick_supply_product">

            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 10px;">Fournisseur</label>
                <select name="fournisseur_id" class="form-control" required>
                    <?php foreach ($fournisseurs as $f): ?>
                        <option value="<?= $f->getId() ?>"><?= htmlspecialchars($f->getNom()) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 10px;">Produit</label>
                <select name="produit_id" class="form-control" required>
                    <?php foreach ($produits as $p): ?>
                        <option value="<?= $p->getId() ?>"><?= htmlspecialchars($p->getNom()) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 10px;">Qté à Commander</label>
                <input type="number" name="quantite" class="form-control" value="10" min="1" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 10px;">Coût Achat Unitaire (F)</label>
                <input type="number" name="cout_achat_unitaire" class="form-control" value="1000" min="0" required>
            </div>
            <button type="submit" class="btn-submit btn-success">Créer le BL</button>
        </form>
    </div>
</div>

<script>
function toggleDetails(id) {
    const el = document.getElementById(id);
    el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
}
</script>