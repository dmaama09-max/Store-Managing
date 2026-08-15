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
        string $nom,
        float $prixVente,
        int $quantiteStock = 0,
        ?string $reference = null,
        float $prixAchat = 0.0,
        int $seuilAlerte = 5,
        ?int $id = null
    ) {
        $this->id            = $id;
        $this->reference     = $reference ?? self::genererReference($nom);
        $this->nom           = $nom;
        $this->prixAchat     = $prixAchat;
        $this->prixVente     = $prixVente;
        $this->quantiteStock = $quantiteStock;
        $this->seuilAlerte   = $seuilAlerte;
    }


    private static function genererReference(string $nom): string
    {
        $prefixe = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $nom), 0, 3));
        $prefixe = $prefixe !== '' ? $prefixe : 'PRD';
        $code    = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        return "{$prefixe}-{$code}";
    }

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




    public function incrementerStock(int $qte, ?float $prixAchatUnitaire = null): void
    {
        if ($qte <= 0) {
            throw new InvalidArgumentException("La quantité à incrémenter doit être positive.");
        }

        if ($prixAchatUnitaire !== null) {
            $valeurStockActuel = $this->quantiteStock * $this->prixAchat;
            $valeurEntree       = $qte * $prixAchatUnitaire;
            $nouvelleQuantite   = $this->quantiteStock + $qte;

            $this->prixAchat = ($valeurStockActuel + $valeurEntree) / $nouvelleQuantite;
        }

        $this->quantiteStock += $qte;
    }


    
    public function estEnRupture(): bool
    {
        return $this->quantiteStock <= $this->seuilAlerte;
    }


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

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}