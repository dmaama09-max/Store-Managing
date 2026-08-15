<?php


class LigneCommande
{
    private ?int $id;
    private int $produitId;
    private int $quantite;
    private float $prixUnitaire;

    public function __construct(int $produitId, int $quantite, float $prixUnitaire, ?int $id = null)
    {
        if ($quantite <= 0) {
            throw new InvalidArgumentException("La quantité doit être positive.");
        }
        if ($prixUnitaire < 0) {
            throw new InvalidArgumentException("Le prix unitaire ne peut pas être négatif.");
        }

        $this->id            = $id;
        $this->produitId     = $produitId;
        $this->quantite      = $quantite;
        $this->prixUnitaire  = $prixUnitaire;
    }

    // ===== Méthode métier =====

    public function calculerSousTotal(): float
    {
        return $this->quantite * $this->prixUnitaire;
    }

    // ===== Getters =====

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduitId(): int
    {
        return $this->produitId;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function getPrixUnitaire(): float
    {
        return $this->prixUnitaire;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}