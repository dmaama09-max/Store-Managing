<?php


class LigneApprovisionnement
{
    private ?int $id;
    private int $produitId;
    private int $quantite;
    private float $prixAchatUnitaire;

    public function __construct(int $produitId, int $quantite, float $prixAchatUnitaire, ?int $id = null)
    {
        if ($quantite <= 0) {
            throw new InvalidArgumentException("La quantité doit être positive.");
        }
        if ($prixAchatUnitaire < 0) {
            throw new InvalidArgumentException("Le prix d'achat ne peut pas être négatif.");
        }

        $this->id                 = $id;
        $this->produitId          = $produitId;
        $this->quantite           = $quantite;
        $this->prixAchatUnitaire  = $prixAchatUnitaire;
    }

    // ===== Méthode métier =====

    public function calculerSousTotal(): float
    {
        return $this->quantite * $this->prixAchatUnitaire;
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

    public function getPrixAchatUnitaire(): float
    {
        return $this->prixAchatUnitaire;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}