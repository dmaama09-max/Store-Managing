<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Model/Entity/Approvisionnement.php';
require_once __DIR__ . '/../Model/Entity/LigneApprovisionnement.php';
require_once __DIR__ . '/../Repository/ApprovisionnementRepository.php';
require_once __DIR__ . '/../Repository/ProduitRepository.php';

/**
 * SupplyService
 *
 * Deux responsabilites :
 * 1. creerBonDeLivraison() : enregistrer un nouveau BL "EN_ATTENTE"
 *    (correspond au formulaire "Commande d'Approvisionnement Rapide" du Dashboard)
 * 2. receptionnerBonDeLivraison() : LE coeur du Step 3.2, qui incremente
 *    reellement le stock des produits et fait passer le BL a "RECEPTIONNE"
 */
class SupplyService
{
    private PDO $pdo;
    private ApprovisionnementRepository $approRepository;
    private ProduitRepository $produitRepository;

    public function __construct()
    {
        $this->pdo             = Database::getInstance();
        $this->approRepository  = new ApprovisionnementRepository();
        $this->produitRepository = new ProduitRepository();
    }

    /**
     * Cree un nouveau bon de livraison EN_ATTENTE avec UNE ligne
     * (le formulaire "Commande Rapide" du dashboard ne gere qu'un produit
     * a la fois). Rien n'est encore ajoute au stock a ce stade : le stock
     * n'augmente qu'a la RECEPTION reelle du BL.
     */
    public function creerBonDeLivraison(
        int $fournisseurId,
        int $utilisateurId,
        int $produitId,
        int $quantite,
        float $coutAchatUnitaire
    ): Approvisionnement {
        $this->pdo->beginTransaction();

        try {
            $appro = new Approvisionnement(
                fournisseurId: $fournisseurId,
                utilisateurId: $utilisateurId,
            );

            $approId = $this->approRepository->create($appro);
            $appro->setId($approId);

            $ligne = new LigneApprovisionnement(
                produitId: $produitId,
                quantite: $quantite,
                prixAchatUnitaire: $coutAchatUnitaire,
            );
            $this->approRepository->createLigne($approId, $ligne);
            $appro->ajouterLigne($ligne);

            $this->pdo->commit();

            return $appro;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Réceptionne un BL : pour chaque ligne, incrémente le stock du produit
     * concerné (avec recalcul du prix d'achat moyen pondéré, cf. Produit::
     * incrementerStock() codée au Step 2.1), puis passe le BL à RECEPTIONNE.
     *
     * @param int   $approId
     * @param array $quantitesRecues Tableau [ligne_id => quantité réellement reçue].
     *                                Si une ligne n'y figure pas, la quantité
     *                                initialement commandée est utilisée par défaut.
     *
     * @throws Exception Si le BL n'existe pas ou est déjà réceptionné.
     */
    public function receptionnerBonDeLivraison(int $approId, array $quantitesRecues): Approvisionnement
    {
        $this->pdo->beginTransaction();

        try {
            $appro = $this->approRepository->findById($approId);

            if ($appro === null) {
                throw new Exception("Bon de livraison introuvable (id: {$approId}).");
            }
            if ($appro->getStatut() === Approvisionnement::STATUT_RECEPTIONNE) {
                throw new Exception("Ce bon de livraison a déjà été réceptionné.");
            }

            $lignesBrutes = $this->approRepository->findLignesBrutes($approId);

            if (empty($lignesBrutes)) {
                throw new Exception("Ce bon de livraison ne contient aucune ligne.");
            }

            foreach ($lignesBrutes as $ligneBrute) {
                $ligneId = (int) $ligneBrute['id'];
                $qteRecue = isset($quantitesRecues[$ligneId])
                    ? (int) $quantitesRecues[$ligneId]
                    : (int) $ligneBrute['quantite'];

                if ($qteRecue < 0) {
                    throw new Exception("La quantité reçue ne peut pas être négative (ligne {$ligneId}).");
                }

                // Si la quantite reellement livree differe de celle commandee,
                // on met a jour la ligne pour que l'historique reflete la realite.
                if ($qteRecue !== (int) $ligneBrute['quantite']) {
                    $this->approRepository->updateQuantiteLigne($ligneId, $qteRecue);
                }

                if ($qteRecue > 0) {
                    $produit = $this->produitRepository->findById((int) $ligneBrute['produit_id']);

                    if ($produit === null) {
                        throw new Exception("Produit introuvable (id: {$ligneBrute['produit_id']}).");
                    }

                    // C'est ICI que la moyenne ponderee codee au Step 2.1 sert enfin :
                    // le stock augmente ET le prixAchat moyen du produit se recalcule.
                    $produit->incrementerStock($qteRecue, (float) $ligneBrute['prix_achat_unitaire']);
                    $this->produitRepository->update($produit);
                }

                $appro->ajouterLigne(new LigneApprovisionnement(
                    produitId: (int) $ligneBrute['produit_id'],
                    quantite: $qteRecue,
                    prixAchatUnitaire: (float) $ligneBrute['prix_achat_unitaire'],
                    id: $ligneId,
                ));
            }

            // receptionner() (codee au Step 2.1) verifie qu'il y a au moins
            // une ligne, puis passe le statut a RECEPTIONNE.
            $appro->receptionner();
            $this->approRepository->updateStatut($appro);

            $this->pdo->commit();

            return $appro;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * BL en attente, avec details fournisseur/valeur, pour le registre.
     */
    public function listerEnAttente(): array
    {
        return $this->approRepository->findEnAttenteAvecDetails();
    }
}