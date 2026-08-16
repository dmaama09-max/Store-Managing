<?php
/**
 * views/dettes/index.php
 *
 * Recoit de DetteController::handle() :
 *   $dettesAvecDetails -> [['dette' => array, 'paiements' => Paiement[], 'lignes' => array], ...]
 *   $stats              -> ['creances_actives' => float, 'nombre_debiteurs' => int, 'total_recouvrements' => float]
 *   $messageErreur / $messageSucces -> string|null
 */
?>

<div id="view-dettes" class="view-section">

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
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--danger);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Créances Actives</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;">
                    <?= number_format($stats['creances_actives'], 0, ',', ' ') ?> F
                </div>
            </div>
            <span style="font-size: 24px;">💸</span>
        </div>
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--warning);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Clients Débiteurs</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;">
                    <?= $stats['nombre_debiteurs'] ?> client<?= $stats['nombre_debiteurs'] > 1 ? 's' : '' ?>
                </div>
            </div>
            <span style="font-size: 24px;">👥</span>
        </div>
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--success);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Total Recouvrements</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;">
                    <?= number_format($stats['total_recouvrements'], 0, ',', ' ') ?> F
                </div>
            </div>
            <span style="font-size: 24px;">📈</span>
        </div>
    </div>

    <!-- Registre des dettes -->
    <div class="panel-card" style="margin-bottom: 0;">
        <div class="panel-title">
            <span>Registre des Dettes Actives</span>
            <input type="text" id="debt-search" class="search-control" placeholder="Rechercher un client..." onkeyup="filterDebtsTable()">
        </div>
        <table class="debt-table" id="debts-main-table">
            <thead>
                <tr>
                    <th>ID Dette</th>
                    <th>Date Création</th>
                    <th>Client</th>
                    <th>Montant Initial</th>
                    <th>Montant Payé</th>
                    <th>Reste Dû</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dettesAvecDetails)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 16px 0;">
                            Aucune dette active pour le moment.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($dettesAvecDetails as $entree): ?>
                    <?php
                        $d = $entree['dette'];
                        $montantPaye = (float) $d['montant_initial'] - (float) $d['montant_restant'];
                        $nomClient   = htmlspecialchars($d['prenom'] . ' ' . $d['nom']);
                        $dateAffichee = $d['date_vente'] ? date('d M Y H:i', strtotime($d['date_vente'])) : '—';
                    ?>
                    <tr data-client-name="<?= strtolower($nomClient . ' ' . $d['telephone']) ?>">
                        <td style="font-weight: 700; color: var(--text-muted);">
                            #DT-<?= $d['id'] ?>
                            <span style="font-size: 10px; color: var(--text-muted); display: block; font-weight: normal; margin-top: 2px;">
                                #CMD-<?= $d['commande_id'] ?>
                            </span>
                        </td>
                        <td style="font-size: 12px;"><?= $dateAffichee ?></td>
                        <td style="font-weight: 700;">
                            <?= $nomClient ?>
                            <div style="font-size:11px; color:var(--text-muted); font-weight:normal;">Tél : <?= htmlspecialchars($d['telephone']) ?></div>
                        </td>
                        <td style="font-weight: 700; color: var(--text-main);"><?= number_format((float) $d['montant_initial'], 0, ',', ' ') ?> F</td>
                        <td style="font-weight: 700; color: var(--success);"><?= number_format($montantPaye, 0, ',', ' ') ?> F</td>
                        <td style="color: var(--danger); font-weight: 800;"><?= number_format((float) $d['montant_restant'], 0, ',', ' ') ?> F</td>
                        <td>
                            <span class="badge badge-danger">NON SOLDEE</span>
                        </td>
                        <td style="display: flex; gap: 6px;">
                            <button type="button" class="btn-quick-action" onclick="toggleDetails('debt-lines-<?= $d['id'] ?>')">Articles</button>
                            <button type="button" class="btn-quick-action" style="border-color: var(--accent); color: var(--accent);" onclick="toggleDetails('debt-details-<?= $d['id'] ?>')">💳 Paiements</button>
                            <button type="button" class="btn-quick-action" style="border-color: var(--warning); color: var(--warning);" onclick="toggleDetails('debt-repay-drawer-<?= $d['id'] ?>')">Rembourser</button>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" style="padding: 0; border: none;">

                            <!-- Drawer paiements -->
                            <div class="details-drawer" id="debt-details-<?= $d['id'] ?>" style="display:none;">
                                <div style="font-weight: 700; font-size: 12px; color: var(--accent); margin-bottom: 8px;">Paiements enregistrés :</div>
                                <table class="debt-table" style="font-size: 11px;">
                                    <thead>
                                        <tr><th>Date</th><th>Versement</th><th>Mode</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($entree['paiements'])): ?>
                                            <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">Aucun acompte versé.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($entree['paiements'] as $p): ?>
                                                <tr>
                                                    <td><?= $p->getDate()->format('d M Y H:i') ?></td>
                                                    <td style="font-weight:700; color: var(--success);"><?= number_format($p->getMontant(), 0, ',', ' ') ?> F</td>
                                                    <td><?= htmlspecialchars($p->getModePaiement()) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Drawer articles -->
                            <div class="details-drawer" id="debt-lines-<?= $d['id'] ?>" style="display:none;">
                                <div style="font-weight: 700; font-size: 12px; color: var(--accent); margin-bottom: 8px;">Articles de la vente à crédit :</div>
                                <table class="debt-table" style="font-size: 11px;">
                                    <thead>
                                        <tr><th>Produit</th><th>Qté</th><th>P.U.</th><th>Sous-total</th></tr>
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

                            <!-- Drawer remboursement -->
                            <div class="details-drawer" id="debt-repay-drawer-<?= $d['id'] ?>" style="display:none; border: 1px solid rgba(45, 212, 191, 0.25); background: linear-gradient(180deg, rgba(11, 15, 25, 0.95) 0%, rgba(11, 15, 25, 0.98) 100%); border-radius: 14px; padding: 18px 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); max-width: 850px; margin: 12px 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-size: 16px;">💳</span>
                                        <span style="font-weight: 800; font-size: 13px; color: var(--text-main);">
                                            Nouveau Remboursement — <span style="color: var(--accent);"><?= $nomClient ?></span>
                                        </span>
                                    </div>
                                    <div style="background: rgba(244, 63, 94, 0.12); border: 1px solid rgba(244, 63, 94, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; color: var(--danger);">
                                        Reste dû : <?= number_format((float) $d['montant_restant'], 0, ',', ' ') ?> FCFA
                                    </div>
                                </div>

                                <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 16px;">
                                    <span style="font-size: 10px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Raccourcis :</span>
                                    <button type="button" onclick="setRepayAmount(<?= $d['id'] ?>, <?= $d['montant_restant'] ?>)" style="background: rgba(45, 212, 191, 0.1); border: 1px solid var(--accent); color: var(--accent); font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 6px; cursor: pointer;">
                                        Tout solder (<?= number_format((float) $d['montant_restant'], 0, ',', ' ') ?> F)
                                    </button>
                                    <button type="button" onclick="setRepayAmount(<?= $d['id'] ?>, <?= round($d['montant_restant'] / 2) ?>)" style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--border-color); color: var(--text-main); font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 6px; cursor: pointer;">
                                        50% (<?= number_format(round($d['montant_restant'] / 2), 0, ',', ' ') ?> F)
                                    </button>
                                </div>

                                <form method="POST" action="" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                                    <input type="hidden" name="action" value="add_payment">
                                    <input type="hidden" name="dette_id" value="<?= $d['id'] ?>">

                                    <div style="flex: 1; min-width: 200px;">
                                        <label style="font-size: 10px; color: var(--text-muted); display: block; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Montant du Versement (FCFA)</label>
                                        <input type="number" name="montant_verse" id="repay-input-<?= $d['id'] ?>" class="form-control"
                                               max="<?= $d['montant_restant'] ?>" value="<?= $d['montant_restant'] ?>" min="1" required
                                               style="font-size: 13px; font-weight: 700; padding: 10px 12px; background: #0b0f19; border: 1px solid var(--border-color); color: white; width: 100%;">
                                    </div>

                                    <div style="flex: 1; min-width: 200px;">
                                        <label style="font-size: 10px; color: var(--text-muted); display: block; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Canal de Paiement</label>
                                        <select name="mode_paiement" class="form-control" style="font-size: 13px; font-weight: 600; padding: 10px 12px; background: #0b0f19; border: 1px solid var(--border-color); color: white; width: 100%;" required>
                                            <option value="Orange Money">🟠 Orange Money</option>
                                            <option value="Wave">🌊 Wave</option>
                                            <option value="Especes">💵 Espèces (Cash)</option>
                                            <option value="Virement">🏦 Virement Bceao</option>
                                        </select>
                                    </div>

                                    <div>
                                        <button type="submit" class="btn-submit btn-success" style="padding: 11px 24px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 10px; height: 42px;">
                                            ✓ Enregistrer le Remboursement
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
</div>

<script>
function toggleDetails(id) {
    const el = document.getElementById(id);
    el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
}

function setRepayAmount(detteId, montant) {
    document.getElementById('repay-input-' + detteId).value = montant;
}

function filterDebtsTable() {
    const filtre = document.getElementById('debt-search').value.toLowerCase();
    const lignes = document.querySelectorAll('#debts-main-table tbody tr[data-client-name]');

    lignes.forEach(ligne => {
        const nom = ligne.getAttribute('data-client-name');
        ligne.style.display = nom.includes(filtre) ? '' : 'none';
    });
}
</script>