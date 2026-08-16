<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Service/SupplyService.php';
require_once __DIR__ . '/../Repository/ApprovisionnementRepository.php';
require_once __DIR__ . '/../Repository/FournisseurRepository.php';
require_once __DIR__ . '/../Repository/ProduitRepository.php';

class SupplyController
{
    private SupplyService $supplyService;
    private ApprovisionnementRepository $approRepository;
    private FournisseurRepository $fournisseurRepository;
    private ProduitRepository $produitRepository;
    private PDO $pdo;

    public function __construct()
    {
        $this->supplyService         = new SupplyService();
        $this->approRepository        = new ApprovisionnementRepository();
        $this->fournisseurRepository  = new FournisseurRepository();
        $this->produitRepository      = new ProduitRepository();
        $this->pdo                     = Database::getInstance();
    }

    public function handle(): void
    {
        $messageErreur = null;
        $messageSucces = null;

        $action = $_POST['action'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'receive_supply') {
            try {
                $approId = (int) ($_POST['approvisionnement_id'] ?? 0);
                // $_POST['quantites_livrees'] arrive deja sous forme
                // [ligne_id => quantite] grace au name="quantites_livrees[X]" du formulaire
                $quantitesRecues = $_POST['quantites_livrees'] ?? [];

                $appro = $this->supplyService->receptionnerBonDeLivraison($approId, $quantitesRecues);
                $messageSucces = "Bon de livraison #{$appro->getId()} réceptionné avec succès, stock mis à jour.";
            } catch (Exception $e) {
                $messageErreur = $e->getMessage();
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'quick_supply_product') {
            try {
                // TODO : remplacer par l'id reel de l'utilisateur connecte (AuthManager, Step 3.3)
                $utilisateurId = (int) ($_SESSION['utilisateur_id'] ?? 1);

                $this->supplyService->creerBonDeLivraison(
                    fournisseurId: (int) ($_POST['fournisseur_id'] ?? 0),
                    utilisateurId: $utilisateurId,
                    produitId: (int) ($_POST['produit_id'] ?? 0),
                    quantite: (int) ($_POST['quantite'] ?? 0),
                    coutAchatUnitaire: (float) ($_POST['cout_achat_unitaire'] ?? 0),
                );
                $messageSucces = "Nouveau bon de livraison créé avec succès (en attente de réception).";
            } catch (Exception $e) {
                $messageErreur = $e->getMessage();
            }
        }

        // ----- Donnees pour l'affichage -----
        $bonsEnAttente = $this->supplyService->listerEnAttente();

        $bonsAvecLignes = [];
        foreach ($bonsEnAttente as $bon) {
            $bonsAvecLignes[] = [
                'bon'    => $bon,
                'lignes' => $this->approRepository->findLignesBrutes((int) $bon['id']),
            ];
        }

        $fournisseurs = $this->fournisseurRepository->findAll();
        $produits     = $this->produitRepository->findAll();
        $stats        = $this->calculerStatistiques();

        require __DIR__ . '/../../views/supplies/index.php';
    }

    private function calculerStatistiques(): array
    {
        $stmt = $this->pdo->query(
            "SELECT COALESCE(SUM(la.quantite * la.prix_achat_unitaire), 0) AS cout_total
             FROM lignes_approvisionnement la
             INNER JOIN approvisionnements a ON a.id = la.approvisionnement_id
             WHERE a.statut = 'RECEPTIONNE'"
        );
        $coutTotal = (float) $stmt->fetch(PDO::FETCH_ASSOC)['cout_total'];

        $stmt = $this->pdo->query("SELECT COUNT(*) AS nb FROM approvisionnements WHERE statut = 'RECEPTIONNE'");
        $nbReceptionnes = (int) $stmt->fetch(PDO::FETCH_ASSOC)['nb'];

        $stmt = $this->pdo->query("SELECT COUNT(*) AS nb FROM fournisseurs");
        $nbFournisseurs = (int) $stmt->fetch(PDO::FETCH_ASSOC)['nb'];

        return [
            'cout_total_entrees'  => $coutTotal,
            'nb_bl_receptionnes'  => $nbReceptionnes,
            'nb_fournisseurs'     => $nbFournisseurs,
        ];
    }
}