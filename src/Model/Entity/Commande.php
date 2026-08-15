<?php

require_once __DIR__ . '/LigneCommande.php';


class Commande
{
    public const TYPE_ESPECES      = 'ESPECES';
    public const TYPE_CREDIT       = 'CREDIT';
    public const TYPE_MOBILE_MONEY = 'MOBILE_MONEY';

    public const STATUT_VALIDEE = 'VALIDEE';
    public const STATUT_ANNULEE = 'ANNULEE';

    private ?int $id;
    private int $clientId;
    private int $utilisateurId;
    private DateTime $dateVente;
    private string $typeReglement;
    private string $statut;

    /** @var LigneCommande[] */
    private array $lignes = [];

    public function __construct(
        int $clientId,
        int $utilisateurId,
        string $typeReglement,
        ?DateTime $dateVente = null,
        string $statut = self::STATUT_VALIDEE,
        ?int $id = null
    ) {
        $this->id            = $id;
        $this->clientId       = $clientId;
        $this->utilisateurId  = $utilisateurId;
        $this->typeReglement  = $typeReglement;
        $this->dateVente      = $dateVente ?? new DateTime();
        $this->statut         = $statut;
    }

    // ===== Méthodes métier =====


    public function ajouterLigne(LigneCommande $ligne): void
    {
        $this->lignes[] = $ligne;
    }


    public function calculerTotal(): float
    {
        $total = 0.0;
        foreach ($this->lignes as $ligne) {
            $total += $ligne->calculerSousTotal();
        }
        return $total;
    }


    public function validerVente(): bool
    {
        if (empty($this->lignes)) {
            throw new Exception("Impossible de valider une commande sans aucune ligne.");
        }

        $this->statut = self::STATUT_VALIDEE;
        return true;
    }

    public function annuler(): void
    {
        $this->statut = self::STATUT_ANNULEE;
    }

    // ===== Getters =====

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getUtilisateurId(): int
    {
        return $this->utilisateurId;
    }

    public function getDateVente(): DateTime
    {
        return $this->dateVente;
    }

    public function getTypeReglement(): string
    {
        return $this->typeReglement;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    /** @return LigneCommande[] */
    public function getLignes(): array
    {
        return $this->lignes;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}