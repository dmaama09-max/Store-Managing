<?php


class Client
{
    private ?int $id;
    private string $prenom;
    private string $nom;
    private ?string $telephone;
    private ?string $email;
    private float $limiteCredit;

   
    private float $encoursActuel;

    public function __construct(
        string $prenom,
        string $nom,
        ?string $telephone = null,
        ?string $email = null,
        float $limiteCredit = 0,
        float $encoursActuel = 0,
        ?int $id = null
    ) {
        $this->id            = $id;
        $this->prenom         = $prenom;
        $this->nom             = $nom;
        $this->telephone      = $telephone;
        $this->email           = $email;
        $this->limiteCredit    = $limiteCredit;
        $this->encoursActuel  = $encoursActuel;
    }

    // ===== Méthodes métier =====

    public function peutAcheterACredit(float $montant): bool
    {
        if ($montant <= 0) {
            throw new InvalidArgumentException("Le montant doit être positif.");
        }
        return ($this->encoursActuel + $montant) <= $this->limiteCredit;
    }

    public function augmenterEncours(float $montant): void
    {
        $this->encoursActuel += $montant;
    }

    public function diminuerEncours(float $montant): void
    {
        $this->encoursActuel = max(0, $this->encoursActuel - $montant);
    }

    /**
     * Pratique pour l'affichage dans les vues (ex: tableau "Répertoire Clients"),
     * qui affiche un nom complet plutôt que prenom/nom séparés.
     */
    public function getNomComplet(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    // ===== Getters =====

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getLimiteCredit(): float
    {
        return $this->limiteCredit;
    }

    public function getEncoursActuel(): float
    {
        return $this->encoursActuel;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}