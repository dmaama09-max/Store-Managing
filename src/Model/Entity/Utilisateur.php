<?php

require_once __DIR__ . '/../Enum/Role.php';


class Utilisateur
{
    private ?int $id;
    private string $login;
    private string $motDePasseHache;
    private Role $role;

    public function __construct(string $login, string $motDePasseHache, Role $role, ?int $id = null)
    {
        $this->id              = $id;
        $this->login            = $login;
        $this->motDePasseHache = $motDePasseHache;
        $this->role             = $role;
    }

    // ===== Méthodes métier =====

    public function verifierMotDePasse(string $motDePasseEnClair): bool
    {
        return password_verify($motDePasseEnClair, $this->motDePasseHache);
    }

    
    public function aleRole(Role $role): bool
    {
        return $this->role === $role;
    }

    // ===== Getters =====

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}