<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Model/Entity/Fournisseur.php';

class FournisseurRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function create(Fournisseur $fournisseur): int
    {
        $sql = "INSERT INTO fournisseurs (nom, telephone, adresse, email)
                VALUES (:nom, :telephone, :adresse, :email)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nom'       => $fournisseur->getNom(),
            'telephone' => $fournisseur->getTelephone(),
            'adresse'   => $fournisseur->getAdresse(),
            'email'     => $fournisseur->getEmail(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $fournisseur->setId($id);

        return $id;
    }

    public function findById(int $id): ?Fournisseur
    {
        $stmt = $this->pdo->prepare("SELECT * FROM fournisseurs WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne ? $this->hydrater($ligne) : null;
    }

    /** @return Fournisseur[] */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM fournisseurs ORDER BY nom ASC");

        $fournisseurs = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $fournisseurs[] = $this->hydrater($ligne);
        }

        return $fournisseurs;
    }

    public function update(Fournisseur $fournisseur): void
    {
        $sql = "UPDATE fournisseurs
                SET nom = :nom,
                    telephone = :telephone,
                    adresse = :adresse,
                    email = :email
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nom'       => $fournisseur->getNom(),
            'telephone' => $fournisseur->getTelephone(),
            'adresse'   => $fournisseur->getAdresse(),
            'email'     => $fournisseur->getEmail(),
            'id'        => $fournisseur->getId(),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM fournisseurs WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    private function hydrater(array $ligne): Fournisseur
    {
        return new Fournisseur(
            nom: $ligne['nom'],
            telephone: $ligne['telephone'],
            adresse: $ligne['adresse'],
            email: $ligne['email'],
            id: (int) $ligne['id'],
        );
    }
}