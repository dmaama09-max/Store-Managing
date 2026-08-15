<?php

class Paiement
{
    public const MODE_ESPECES      = 'ESPECES';
    public const MODE_MOBILE_MONEY = 'MOBILE_MONEY';
    public const MODE_VIREMENT     = 'VIREMENT';

    private ?int $id;
    private int $detteId;
    private float $montant;
    private DateTime $date;
    private string $modePaiement;

    public function __construct(
        int $detteId,
        float $montant,
        string $modePaiement,
        ?DateTime $date = null,
        ?int $id = null
    ) {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant du paiement doit être positif.");
        }

        $this->id           = $id;
        $this->detteId       = $detteId;
        $this->montant       = $montant;
        $this->modePaiement  = $modePaiement;
        $this->date          = $date ?? new DateTime();
    }

    // ===== Getters =====

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDetteId(): int
    {
        return $this->detteId;
    }

    public function getMontant(): float
    {
        return $this->montant;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function getModePaiement(): string
    {
        return $this->modePaiement;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}