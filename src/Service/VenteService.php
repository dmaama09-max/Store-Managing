<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Model/Entity/Commande.php';
require_once __DIR__ . '/../Model/Entity/LigneCommande.php';
require_once __DIR__ . '/../Model/Entity/Dette.php';
require_once __DIR__ . '/../Repository/ProduitRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';

/**
 * VenteService
 *
 * Contient TOUTE la logique metier d'une vente au comptoir (POS) :
 * - construire le panier (lignes produit + quantite)
 * - verifier le stock disponible
 * - verifier la limite de credit si paiement a credit
 * - enregistrer la vente en base sous UNE SEULE transaction SQL
 *
 * Pourquoi une transaction ? Une vente touche PLUSIEURS tables
 * (commandes, lignes_commande, produits, eventuellement dettes).
 * Si une erreur survient a la 3e ligne sur 5, on ne veut PAS se
 * retrouver avec une commande a moitie enregistree et un stock
 * decremente pour rien. La transaction garantit : tout ou rien.
 */
class VenteService
{
    private PDO $pdo;
    private ProduitRepository $produitRepository;
    private ClientRepository $clientRepository;

    public function __construct()
    {
        $this->pdo               = Database::getInstance();
        $this->produitRepository = new ProduitRepository();
        $this->clientRepository  = new ClientRepository();
    }

    /**
     * Valide une vente complete a partir d'un panier.
     *
     * @param int    $clientId
     * @param int    $utilisateurId   L'utilisateur (vendeur) qui enregistre la vente
     * @param string $typeReglement   Commande::TYPE_ESPECES | TYPE_CREDIT | TYPE_MOBILE_MONEY
     * @param array  $panier          Ex: [['produit_id' => 3, 'quantite' => 2], ...]
     *
     * @return Commande La commande creee, avec son id et ses lignes
     *
     * @throws Exception Si le stock est insuffisant, si la limite de credit
     *                    est depassee, ou si le panier est vide.
     */
    public function validerVente(int $clientId, int $utilisateurId, string $typeReglement, array $panier): Commande
    {
        if (empty($panier)) {
            throw new Exception("Le panier est vide, impossible de valider la vente.");
        }

        // ===== DEBUT DE LA TRANSACTION =====
        // A partir d'ici, aucune modification n'est definitive en base
        // tant qu'on n'a pas appele commit().
        $this->pdo->beginTransaction();

        try {
            // ----- 1. Construire la Commande (en memoire pour l'instant) -----
            $commande = new Commande(
                clientId: $clientId,
                utilisateurId: $utilisateurId,
                typeReglement: $typeReglement,
            );

            $produitsConcernes = []; // on garde les objets Produit pour les decrementer plus tard

            // ----- 2. Verifier le stock et construire les lignes -----
            foreach ($panier as $item) {
                $produit = $this->produitRepository->findById($item['produit_id']);

                if ($produit === null) {
                    throw new Exception("Produit introuvable (id: {$item['produit_id']}).");
                }

                // decrementerStock() leve une Exception automatiquement
                // si la quantite demandee depasse le stock disponible.
                // On la teste ici SANS l'appliquer definitivement, pour
                // detecter le probleme avant de toucher a quoi que ce soit.
                if ($item['quantite'] > $produit->getQuantiteStock()) {
                    throw new Exception("Stock insuffisant pour '{$produit->getNom()}' (disponible: {$produit->getQuantiteStock()}, demande: {$item['quantite']}).");
                }

                $ligne = new LigneCommande(
                    produitId: $produit->getId(),
                    quantite: $item['quantite'],
                    prixUnitaire: $produit->getPrixVente(), // prix FIGE au moment de la vente
                );

                $commande->ajouterLigne($ligne);
                $produitsConcernes[] = ['produit' => $produit, 'quantite' => $item['quantite']];
            }

            $totalVente = $commande->calculerTotal();

            // ----- 3. Si vente a credit : verifier la limite du client -----
            $client = null;
            if ($typeReglement === Commande::TYPE_CREDIT) {
                $client = $this->clientRepository->findById($clientId);

                if ($client === null) {
                    throw new Exception("Client introuvable (id: {$clientId}).");
                }

                if (!$client->peutAcheterACredit($totalVente)) {
                    throw new Exception(
                        "Limite de credit depassee pour {$client->getNomComplet()} "
                        . "(encours actuel: {$client->getEncoursActuel()}, limite: {$client->getLimiteCredit()}, "
                        . "montant demande: {$totalVente})."
                    );
                }
            }

            // ----- 4. Valider la commande (regle metier : au moins 1 ligne) -----
            $commande->validerVente();

            // ----- 5. Persister la commande -----
            $commandeId = $this->creerCommande($commande, $totalVente);
            $commande->setId($commandeId);

            // ----- 6. Persister les lignes + decrementer le stock -----
            foreach ($commande->getLignes() as $index => $ligne) {
                $this->creerLigneCommande($commandeId, $ligne);

                $produit = $produitsConcernes[$index]['produit'];
                $produit->decrementerStock($produitsConcernes[$index]['quantite']);
                $this->produitRepository->update($produit);
            }

            // ----- 7. Si vente a credit : creer la Dette + mettre a jour l'encours client -----
            if ($typeReglement === Commande::TYPE_CREDIT && $client !== null) {
                $dette = new Dette(
                    clientId: $clientId,
                    montantInitial: $totalVente,
                    commandeId: $commandeId,
                );
                $this->creerDette($dette);

                $client->augmenterEncours($totalVente);
                $this->clientRepository->update($client);
            }

            // ===== TOUT S'EST BIEN PASSE : on valide definitivement =====
            $this->pdo->commit();

            return $commande;

        } catch (Exception $e) {
            // ===== UNE ERREUR EST SURVENUE : on annule TOUT =====
            // Aucune des ecritures faites depuis beginTransaction() n'est appliquee.
            $this->pdo->rollBack();

            // On relance l'exception pour que le Controller (Step 2.4) sache
            // qu'il doit afficher un message d'erreur a l'utilisateur.
            throw $e;
        }
    }

    // ===== Méthodes privées de persistance =====
    // (des mini-repositories "maison" en attendant CommandeRepository/DetteRepository,
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

    private function creerDette(Dette $dette): int
    {
        $sql = "INSERT INTO dettes (commande_id, client_id, montant_initial, montant_restant, statut, date_echeance)
                VALUES (:commande_id, :client_id, :montant_initial, :montant_restant, :statut, :date_echeance)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'commande_id'     => $dette->getCommandeId(),
            'client_id'       => $dette->getClientId(),
            'montant_initial' => $dette->getMontantInitial(),
            'montant_restant' => $dette->getMontantRestant(),
            'statut'          => $dette->getStatut(),
            'date_echeance'   => $dette->getDateEcheance()?->format('Y-m-d'),
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}