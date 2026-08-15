<?php


class Dette
{
    public const STATUT_EN_COURS = 'EN_COURS';
    public const STATUT_SOLDEE   = 'SOLDEE';

    private ?int $id;
    private ?int $commandeId;
    private int $clientId;
    private float $montantInitial;
    private float $montantRestant;
    private string $statut;
    private ?DateTime $dateEcheance;

    public function __construct(
        int $clientId,
        float $montantInitial,
        ?int $commandeId = null,
        ?DateTime $dateEcheance = null,
        ?int $id = null
    ) {
        if ($montantInitial <= 0) {
            throw new InvalidArgumentException("Le montant initial d'une dette doit être positif.");
        }

        $this->id             = $id;
        $this->commandeId     = $commandeId;
        $this->clientId       = $clientId;
        $this->montantInitial = $montantInitial;
        $this->montantRestant = $montantInitial; // au départ, rien n'est encore remboursé
        $this->statut          = self::STATUT_EN_COURS;
        $this->dateEcheance    = $dateEcheance;
    }

    // ===== Méthodes métier =====

    public function enregistrerPaiement(float $montant): void
    {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant du paiement doit être positif.");
        }
        if ($montant > $this->montantRestant) {
            throw new Exception("Le paiement ({$montant}) dépasse le montant restant dû ({$this->montantRestant}).");
        }

        $this->montantRestant -= $montant;

        if ($this->montantRestant <= 0.0) {
            $this->statut = self::STATUT_SOLDEE;
        }
    }

    public function estSoldee(): bool
    {
        return $this->statut === self::STATUT_SOLDEE;
    }

    // ===== Getters =====

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommandeId(): ?int
    {
        return $this->commandeId;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getMontantInitial(): float
    {
        return $this->montantInitial;
    }

    public function getMontantRestant(): float
    {
        return $this->montantRestant;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getDateEcheance(): ?DateTime
    {
        return $this->dateEcheance;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}