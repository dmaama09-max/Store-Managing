<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Model/Entity/Commande.php';
require_once __DIR__ . '/../Model/Entity/LigneCommande.php';
require_once __DIR__ . '/../Model/Entity/Dette.php';
require_once __DIR__ . '/../Repository/ProduitRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';
require_once __DIR__ . '/../Repository/DetteRepository.php';

/**
 * VenteService
 *
 * Contient TOUTE la logique metier d'une vente au comptoir (POS) :
 * - verifier le stock disponible pour chaque article du panier
 * - calculer le total de la vente
 * - comparer le montant verse (avance) au total :
 *      - montant_verse >= total  -> vente payee comptant (pas de dette)
 *      - montant_verse < total   -> le reste devient une Dette
 *        (montant_verse = 0 -> "credit total", montant_verse > 0 -> "avance")
 * - enregistrer le tout en base sous UNE SEULE transaction SQL
 */
class VenteService
{
    private PDO $pdo;
    private ProduitRepository $produitRepository;
    private ClientRepository $clientRepository;
    private DetteRepository $detteRepository;

    public function __construct()
    {
        $this->pdo               = Database::getInstance();
        $this->produitRepository = new ProduitRepository();
        $this->clientRepository  = new ClientRepository();
        $this->detteRepository   = new DetteRepository();
    }

    /**
     * Valide une vente complete a partir d'un panier.
     *
     * @param int    $clientId
     * @param int    $utilisateurId  Le vendeur qui enregistre la vente
     * @param string $modePaiement   Libelle venant du formulaire : "Wave", "Orange Money", "Especes"
     * @param float  $montantVerse   Montant deja paye par le client (0 si credit total)
     * @param array  $panier         Ex: [['produit_id' => 3, 'quantite' => 2], ...]
     *
     * @return Commande
     * @throws Exception Si stock insuffisant, limite de credit depassee, ou panier vide.
     */
    public function validerVente(
        int $clientId,
        int $utilisateurId,
        string $modePaiement,
        float $montantVerse,
        array $panier
    ): Commande {
        if (empty($panier)) {
            throw new Exception("Le panier est vide, impossible de valider la vente.");
        }
        if ($montantVerse < 0) {
            throw new Exception("Le montant versé ne peut pas être négatif.");
        }

        $this->pdo->beginTransaction();

        try {
            // ----- 1. Verifier le stock et calculer le total, SANS rien modifier encore -----
            $lignesAConstruire = []; // [['produit' => Produit, 'quantite' => int], ...]
            $totalVente = 0.0;

            foreach ($panier as $item) {
                $produit = $this->produitRepository->findById((int) $item['produit_id']);

                if ($produit === null) {
                    throw new Exception("Produit introuvable (id: {$item['produit_id']}).");
                }

                $quantite = (int) $item['quantite'];

                if ($quantite > $produit->getQuantiteStock()) {
                    throw new Exception("Stock insuffisant pour '{$produit->getNom()}' (disponible: {$produit->getQuantiteStock()}, demandé: {$quantite}).");
                }

                $lignesAConstruire[] = ['produit' => $produit, 'quantite' => $quantite];
                $totalVente += $produit->getPrixVente() * $quantite;
            }

            // ----- 2. Determiner le type de reglement selon le montant verse -----
            $montantRestant = round($totalVente - $montantVerse, 2);

            if ($montantRestant <= 0) {
                // Paye integralement : le type depend juste du canal utilise
                $typeReglement = in_array($modePaiement, ['Wave', 'Orange Money'], true)
                    ? Commande::TYPE_MOBILE_MONEY
                    : Commande::TYPE_ESPECES;
                $montantRestant = 0.0;
            } else {
                // Il reste un solde impaye -> vente a credit (totale ou partielle)
                $typeReglement = Commande::TYPE_CREDIT;
            }

            // ----- 3. Si un reste est du : verifier la limite de credit du client -----
            $client = null;
            if ($typeReglement === Commande::TYPE_CREDIT) {
                $client = $this->clientRepository->findById($clientId);

                if ($client === null) {
                    throw new Exception("Client introuvable (id: {$clientId}).");
                }

                // Seul le RESTE (pas le total) impacte la limite de credit,
                // puisque le montant verse est deja regle.
                if (!$client->peutAcheterACredit($montantRestant)) {
                    throw new Exception(
                        "Limite de crédit dépassée pour {$client->getNomComplet()} "
                        . "(encours actuel: {$client->getEncoursActuel()}, limite: {$client->getLimiteCredit()}, "
                        . "reste à devoir: {$montantRestant})."
                    );
                }
            }

            // ----- 4. Construire la Commande (en memoire) -----
            $commande = new Commande(
                clientId: $clientId,
                utilisateurId: $utilisateurId,
                typeReglement: $typeReglement,
            );

            foreach ($lignesAConstruire as $donnees) {
                $commande->ajouterLigne(new LigneCommande(
                    produitId: $donnees['produit']->getId(),
                    quantite: $donnees['quantite'],
                    prixUnitaire: $donnees['produit']->getPrixVente(),
                ));
            }

            $commande->validerVente();

            // ----- 5. Persister la commande -----
            $commandeId = $this->creerCommande($commande, $totalVente);
            $commande->setId($commandeId);

            // ----- 6. Persister les lignes + decrementer le stock -----
            foreach ($commande->getLignes() as $index => $ligne) {
                $this->creerLigneCommande($commandeId, $ligne);

                $produit = $lignesAConstruire[$index]['produit'];
                $produit->decrementerStock($lignesAConstruire[$index]['quantite']);
                $this->produitRepository->update($produit);
            }

            // ----- 7. Si un reste est du : creer la Dette (avec le versement deja applique) -----
            if ($typeReglement === Commande::TYPE_CREDIT && $client !== null) {
                $dette = new Dette(
                    clientId: $clientId,
                    montantInitial: $totalVente,
                    commandeId: $commandeId,
                );

                // Si le client a deja verse une avance, on l'applique tout de suite :
                // montantRestant de la Dette refletera alors le VRAI reste a payer.
                if ($montantVerse > 0) {
                    $dette->enregistrerPaiement($montantVerse);
                }

                $this->detteRepository->create($dette);

                // L'encours du client n'augmente que du montant reellement du
                $client->augmenterEncours($montantRestant);
                $this->clientRepository->update($client);
            }

            $this->pdo->commit();

            return $commande;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // ===== Méthodes privées de persistance =====
    // (SQL temporairement ici en attendant CommandeRepository/DetteRepository,
    //  qui seront extraits proprement dimanche - Step 3.1)

    private function creerCommande(Commande $commande, float $total): int
    {
        $sql = "INSERT INTO commandes (client_id, utilisateur_id, date_vente, type_reglement, montant_total, statut)
                VALUES (:client_id, :utilisateur_id, :date_vente, :type_reglement, :montant_total, :statut)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'client_id'      => $commande->getClientId(),
            'utilisateur_id' => $commande->getUtilisateurId(),
            'date_vente'     => $commande->getDateVente()->format('Y-m-d H:i:s'),
            'type_reglement' => $commande->getTypeReglement(),
            'montant_total'  => $total,
            'statut'         => $commande->getStatut(),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function creerLigneCommande(int $commandeId, LigneCommande $ligne): int
    {
        $sql = "INSERT INTO lignes_commande (commande_id, produit_id, quantite, prix_unitaire)
                VALUES (:commande_id, :produit_id, :quantite, :prix_unitaire)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'commande_id'   => $commandeId,
            'produit_id'    => $ligne->getProduitId(),
            'quantite'      => $ligne->getQuantite(),
            'prix_unitaire' => $ligne->getPrixUnitaire(),
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}