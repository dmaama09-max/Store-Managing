<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Model/Entity/Produit.php';

/**
 * ProduitRepository
 *
 * Role : etre le SEUL endroit du code qui parle SQL pour les produits.
 * Le Service et le Controller ne manipuleront jamais de requetes SQL
 * directement : ils appelleront des methodes comme findById() ou create(),
 * qui renvoient/recoivent des objets Produit (pas des tableaux bruts).
 */
class ProduitRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Insere un nouveau produit en base et renvoie son id genere.
     * On utilise une requete preparee (les ":xxx") pour eviter
     * toute injection SQL : les valeurs ne sont JAMAIS concatenees
     * directement dans la chaine SQL.
     */
    public function create(Produit $produit): int
    {
        $sql = "INSERT INTO produits (reference, nom, prix_achat, prix_vente, quantite_stock, seuil_alerte)
                VALUES (:reference, :nom, :prix_achat, :prix_vente, :quantite_stock, :seuil_alerte)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'reference'      => $produit->getReference(),
            'nom'            => $produit->getNom(),
            'prix_achat'     => $produit->getPrixAchat(),
            'prix_vente'     => $produit->getPrixVente(),
            'quantite_stock' => $produit->getQuantiteStock(),
            'seuil_alerte'   => $produit->getSeuilAlerte(),
        ]);

        // lastInsertId() renvoie l'id auto-incremente que la base vient de generer
        $id = (int) $this->pdo->lastInsertId();
        $produit->setId($id);

        return $id;
    }

    /**
     * Recherche un produit par son id.
     * Renvoie null si aucun produit ne correspond (au lieu de "false"
     * ou d'un tableau vide, plus facile a tester avec un simple "if").
     */
    public function findById(int $id): ?Produit
    {
        $sql = "SELECT * FROM produits WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne ? $this->hydrater($ligne) : null;
    }

    /**
     * Renvoie tous les produits, triés par nom.
     * @return Produit[]
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM produits ORDER BY nom ASC";

        $stmt = $this->pdo->query($sql);

        $produits = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $produits[] = $this->hydrater($ligne);
        }

        return $produits;
    }

    /**
     * Renvoie uniquement les produits en rupture de stock
     * (utile pour la vue Dashboard : "Lister les ruptures et alertes").
     * @return Produit[]
     */
    public function findEnRupture(): array
    {
        $sql = "SELECT * FROM produits WHERE quantite_stock <= seuil_alerte ORDER BY nom ASC";

        $stmt = $this->pdo->query($sql);

        $produits = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $produits[] = $this->hydrater($ligne);
        }

        return $produits;
    }

    /**
     * Met a jour un produit existant en base (ex: apres decrementerStock()
     * ou incrementerStock() sur l'objet en memoire).
     */
    public function update(Produit $produit): void
    {
        $sql = "UPDATE produits
                SET reference = :reference,
                    nom = :nom,
                    prix_achat = :prix_achat,
                    prix_vente = :prix_vente,
                    quantite_stock = :quantite_stock,
                    seuil_alerte = :seuil_alerte
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'reference'      => $produit->getReference(),
            'nom'            => $produit->getNom(),
            'prix_achat'     => $produit->getPrixAchat(),
            'prix_vente'     => $produit->getPrixVente(),
            'quantite_stock' => $produit->getQuantiteStock(),
            'seuil_alerte'   => $produit->getSeuilAlerte(),
            'id'             => $produit->getId(),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM produits WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    /**
     * "Hydrater" = transformer une ligne SQL brute (tableau associatif)
     * en un vrai objet Produit, en passant par son constructeur.
     * C'est la SEULE fonction de cette classe qui connait a la fois
     * la structure SQL (noms de colonnes) ET la structure de l'objet Produit.
     */
    private function hydrater(array $ligne): Produit
    {
        return new Produit(
            nom: $ligne['nom'],
            prixVente: (float) $ligne['prix_vente'],
            quantiteStock: (int) $ligne['quantite_stock'],
            reference: $ligne['reference'],
            prixAchat: (float) $ligne['prix_achat'],
            seuilAlerte: (int) $ligne['seuil_alerte'],
            id: (int) $ligne['id'],
        );
    }
}