<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Model/Entity/Approvisionnement.php';
require_once __DIR__ . '/../Model/Entity/LigneApprovisionnement.php';

class ApprovisionnementRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function create(Approvisionnement $appro): int
    {
        $sql = "INSERT INTO approvisionnements (fournisseur_id, utilisateur_id, date_reception, statut)
                VALUES (:fournisseur_id, :utilisateur_id, :date_reception, :statut)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'fournisseur_id' => $appro->getFournisseurId(),
            'utilisateur_id' => $appro->getUtilisateurId(),
            'date_reception'  => $appro->getDateReception()->format('Y-m-d H:i:s'),
            'statut'          => $appro->getStatut(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $appro->setId($id);

        return $id;
    }

    public function createLigne(int $approId, LigneApprovisionnement $ligne): int
    {
        $sql = "INSERT INTO lignes_approvisionnement (approvisionnement_id, produit_id, quantite, prix_achat_unitaire)
                VALUES (:appro_id, :produit_id, :quantite, :prix_achat_unitaire)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'appro_id'             => $approId,
            'produit_id'           => $ligne->getProduitId(),
            'quantite'             => $ligne->getQuantite(),
            'prix_achat_unitaire'  => $ligne->getPrixAchatUnitaire(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $ligne->setId($id);

        return $id;
    }

    public function findById(int $id): ?Approvisionnement
    {
        $stmt = $this->pdo->prepare("SELECT * FROM approvisionnements WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne ? $this->hydrater($ligne) : null;
    }

    /**
     * Lignes brutes d'un BL, avec le nom du produit (utile pour l'affichage).
     * On garde ici un tableau associatif plutôt que des objets pour rester
     * simple : SupplyService reconstruira les vrais objets LigneApprovisionnement
     * uniquement quand il en a besoin (lors de la réception).
     */
    public function findLignesBrutes(int $approId): array
    {
        $sql = "SELECT la.*, p.nom AS produit_nom
                FROM lignes_approvisionnement la
                INNER JOIN produits p ON p.id = la.produit_id
                WHERE la.approvisionnement_id = :appro_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['appro_id' => $approId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * BL en attente de réception, avec le nom/téléphone du fournisseur
     * et la valeur totale du lot (pour le registre de la vue Supplies).
     */
    public function findEnAttenteAvecDetails(): array
    {
        $sql = "SELECT a.*, f.nom AS fournisseur_nom, f.telephone AS fournisseur_telephone,
                       COALESCE((
                           SELECT SUM(la.quantite * la.prix_achat_unitaire)
                           FROM lignes_approvisionnement la
                           WHERE la.approvisionnement_id = a.id
                       ), 0) AS valeur_totale
                FROM approvisionnements a
                INNER JOIN fournisseurs f ON f.id = a.fournisseur_id
                WHERE a.statut = 'EN_ATTENTE'
                ORDER BY a.date_reception DESC";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatut(Approvisionnement $appro): void
    {
        $stmt = $this->pdo->prepare("UPDATE approvisionnements SET statut = :statut WHERE id = :id");
        $stmt->execute([
            'statut' => $appro->getStatut(),
            'id'     => $appro->getId(),
        ]);
    }

    /**
     * Met a jour la quantite reellement livree sur une ligne existante
     * (peut differer de la quantite initialement commandee, en cas de
     * livraison partielle).
     */
    public function updateQuantiteLigne(int $ligneId, int $nouvelleQuantite): void
    {
        $stmt = $this->pdo->prepare("UPDATE lignes_approvisionnement SET quantite = :quantite WHERE id = :id");
        $stmt->execute(['quantite' => $nouvelleQuantite, 'id' => $ligneId]);
    }

    private function hydrater(array $ligne): Approvisionnement
    {
        return new Approvisionnement(
            fournisseurId: (int) $ligne['fournisseur_id'],
            utilisateurId: (int) $ligne['utilisateur_id'],
            dateReception: new DateTime($ligne['date_reception']),
            statut: $ligne['statut'],
            id: (int) $ligne['id'],
        );
    }
}