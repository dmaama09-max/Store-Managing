<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/AuthManager.php';
require_once __DIR__ . '/../Service/VenteService.php';
require_once __DIR__ . '/../Repository/ProduitRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';

/**
 * POSController
 *
 * Fait le lien entre le formulaire de la vue POS (views/pos/index.php)
 * et la logique metier (VenteService). Ne contient PAS de SQL et PAS
 * de regle metier lui-meme : il se contente de lire $_POST, appeler
 * le Service, et preparer les variables pour l'affichage.
 */
class POSController
{
    private VenteService $venteService;
    private ProduitRepository $produitRepository;
    private ClientRepository $clientRepository;
    private PDO $pdo;

    public function __construct()
    {
        $this->venteService      = new VenteService();
        $this->produitRepository = new ProduitRepository();
        $this->clientRepository  = new ClientRepository();
        $this->pdo                = Database::getInstance();
    }

    /**
     * Point d'entree principal. A appeler depuis public/pos.php (ou index.php).
     */
    public function handle(): void
    {
        $auth = new AuthManager();
        $auth->checkAccess(Role::ADMIN, Role::VENTE);

        $messageErreur = null;
        $messageSucces = null;

        // ----- Traitement du formulaire, uniquement si on recoit un POST -----
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_order') {
            try {
                $commande = $this->traiterFormulaireVente($_POST);
                $messageSucces = "Vente #{$commande->getId()} enregistrée avec succès ({$commande->calculerTotal()} FCFA).";
            } catch (Exception $e) {
                $messageErreur = $e->getMessage();
            }
        }

        // ----- Donnees necessaires a l'affichage (GET ou apres un POST) -----
        $clients  = $this->clientRepository->findAll();
        $produits = $this->produitRepository->findAll();
        $commandesRecentes = $this->listerCommandesAvecLignes();

        // ----- Rendu de la vue -----
        // On passe les variables a la vue via "extract" implicite :
        // le fichier views/pos/index.php peut directement utiliser
        // $clients, $produits, $commandesRecentes, $messageErreur, $messageSucces.
        require __DIR__ . '/../../views/pos/index.php';
    }

    /**
     * Lit les donnees POST du formulaire, reconstruit un panier exploitable,
     * et delegue toute la logique metier a VenteService.
     */
    private function traiterFormulaireVente(array $post): Commande
    {
        $clientId      = (int) ($post['client_id'] ?? 0);
        $modePaiement  = (string) ($post['mode_reglement'] ?? 'Especes');
        $montantVerse  = (float) ($post['montant_verse'] ?? 0);

        // Utilisateur reellement connecte (AuthManager, Step 3.3), fini le TODO d'hier
        $auth = new AuthManager();
        $utilisateurId = $auth->utilisateurConnecte()->getId();

        if ($clientId <= 0) {
            throw new Exception("Veuillez sélectionner un client.");
        }

        // Le panier arrive sous forme de 2 tableaux paralleles :
        // produit_id[] = [3, 5, 1]  et  quantite[] = [2, 1, 4]
        // (voir la modification JS necessaire dans la vue, cf. commentaire dans index.php)
        $produitIds = $post['produit_id'] ?? [];
        $quantites  = $post['quantite'] ?? [];

        if (empty($produitIds) || count($produitIds) !== count($quantites)) {
            throw new Exception("Le panier est vide ou invalide.");
        }

        $panier = [];
        foreach ($produitIds as $index => $produitId) {
            $panier[] = [
                'produit_id' => (int) $produitId,
                'quantite'   => (int) $quantites[$index],
            ];
        }

        return $this->venteService->validerVente(
            clientId: $clientId,
            utilisateurId: $utilisateurId,
            modePaiement: $modePaiement,
            montantVerse: $montantVerse,
            panier: $panier
        );
    }

    /**
     * Recupere les commandes recentes avec leurs lignes, pour le
     * "Registre Général des Ventes & Commandes" de la vue.
     *
     * NOTE : requete SQL directe ici, en attendant un CommandeRepository
     * dedie (non prevu avant dimanche dans le planning). A factoriser
     * plus tard si le temps le permet.
     *
     * @return array Tableau de ['commande' => array, 'lignes' => array]
     */
    private function listerCommandesAvecLignes(int $limite = 10): array
    {
        $sql = "SELECT c.*, cl.prenom, cl.nom
                FROM commandes c
                INNER JOIN clients cl ON cl.id = c.client_id
                ORDER BY c.date_vente DESC
                LIMIT :limite";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultat = [];
        foreach ($commandes as $commandeRow) {
            $stmtLignes = $this->pdo->prepare(
                "SELECT lc.*, p.nom AS produit_nom
                 FROM lignes_commande lc
                 INNER JOIN produits p ON p.id = lc.produit_id
                 WHERE lc.commande_id = :commande_id"
            );
            $stmtLignes->execute(['commande_id' => $commandeRow['id']]);

            $resultat[] = [
                'commande' => $commandeRow,
                'lignes'   => $stmtLignes->fetchAll(PDO::FETCH_ASSOC),
            ];
        }

        return $resultat;
    }
}