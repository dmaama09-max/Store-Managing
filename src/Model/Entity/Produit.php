<?php


class Produit
{
    private ?int $id;
    private string $reference;
    private string $nom;
    private float $prixAchat;
    private float $prixVente;
    private int $quantiteStock;
    private int $seuilAlerte;

    public function __construct(
        string $reference,
        string $nom,
        float $prixAchat,
        float $prixVente,
        int $quantiteStock = 0,
        int $seuilAlerte = 0,
        ?int $id = null
    ) {
        $this->id            = $id;
        $this->reference     = $reference;
        $this->nom           = $nom;
        $this->prixAchat     = $prixAchat;
        $this->prixVente     = $prixVente;
        $this->quantiteStock = $quantiteStock;
        $this->seuilAlerte   = $seuilAlerte;
    }

    // ===== Méthodes métier =====

    /**
     * Diminue le stock. Lève une exception si le stock est insuffisant,
     * plutôt que de laisser quantiteStock devenir négatif.
     */
    public function decrementerStock(int $qte): void
    {
        if ($qte <= 0) {
            throw new InvalidArgumentException("La quantité à décrémenter doit être positive.");
        }
        if ($qte > $this->quantiteStock) {
            throw new Exception("Stock insuffisant pour le produit '{$this->nom}' (disponible: {$this->quantiteStock}, demandé: {$qte}).");
        }
        $this->quantiteStock -= $qte;
    }

    /**
     * Augmente le stock (ex: lors d'une réception d'approvisionnement).
     */
    public function incrementerStock(int $qte): void
    {
        if ($qte <= 0) {
            throw new InvalidArgumentException("La quantité à incrémenter doit être positive.");
        }
        $this->quantiteStock += $qte;
    }

    /**
     * Vrai si le stock est descendu au niveau (ou sous) le seuil d'alerte.
     */
    public function estEnRupture(): bool
    {
        return $this->quantiteStock <= $this->seuilAlerte;
    }

    // ===== Getters (lecture seule depuis l'extérieur) =====

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getPrixAchat(): float
    {
        return $this->prixAchat;
    }

    public function getPrixVente(): float
    {
        return $this->prixVente;
    }

    public function getQuantiteStock(): int
    {
        return $this->quantiteStock;
    }

    public function getSeuilAlerte(): int
    {
        return $this->seuilAlerte;
    }

    /**
     * Utile après un INSERT en base : le Repository appellera ceci
     * pour donner à l'objet l'id généré par la base de données.
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
}