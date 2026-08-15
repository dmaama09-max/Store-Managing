<?php

require_once __DIR__ . '/LigneApprovisionnement.php';

class Approvisionnement
{
    public const STATUT_EN_ATTENTE  = 'EN_ATTENTE';
    public const STATUT_RECEPTIONNE = 'RECEPTIONNE';

    private ?int $id;
    private int $fournisseurId;
    private int $utilisateurId;
    private DateTime $dateReception;
    private string $statut;

    /** @var LigneApprovisionnement[] */
    private array $lignes = [];

    public function __construct(
        int $fournisseurId,
        int $utilisateurId,
        ?DateTime $dateReception = null,
        string $statut = self::STATUT_EN_ATTENTE,
        ?int $id = null
    ) {
        $this->id             = $id;
        $this->fournisseurId  = $fournisseurId;
        $this->utilisateurId  = $utilisateurId;
        $this->dateReception  = $dateReception ?? new DateTime();
        $this->statut          = $statut;
    }

    // ===== Méthodes métier =====

    public function ajouterLigne(LigneApprovisionnement $ligne): void
    {
        $this->lignes[] = $ligne;
    }

    public function calculerCoutTotal(): float
    {
        $total = 0.0;
        foreach ($this->lignes as $ligne) {
            $total += $ligne->calculerSousTotal();
        }
        return $total;
    }


    public function receptionner(): void
    {
        if (empty($this->lignes)) {
            throw new Exception("Impossible de réceptionner un bon de livraison sans aucune ligne.");
        }
        $this->statut = self::STATUT_RECEPTIONNE;
    }

    // ===== Getters =====

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFournisseurId(): int
    {
        return $this->fournisseurId;
    }

    public function getUtilisateurId(): int
    {
        return $this->utilisateurId;
    }

    public function getDateReception(): DateTime
    {
        return $this->dateReception;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    /** @return LigneApprovisionnement[] */
    public function getLignes(): array
    {
        return $this->lignes;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}