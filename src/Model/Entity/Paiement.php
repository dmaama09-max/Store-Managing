<?php

/**
 * Entité Paiement
 *
 * Représente UN remboursement effectué sur une Dette.
 * Les valeurs de MODE_* correspondent EXACTEMENT aux "value" du <select>
 * "Canal de Paiement" du formulaire HTML de remboursement, pour ne pas
 * avoir a faire de conversion/mapping entre le formulaire et la base.
 */
class Paiement
{
    public const MODE_ORANGE_MONEY = 'Orange Money';
    public const MODE_WAVE         = 'Wave';
    public const MODE_ESPECES      = 'Especes';
    public const MODE_VIREMENT     = 'Virement';

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