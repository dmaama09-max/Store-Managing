<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Model/Entity/Utilisateur.php';
require_once __DIR__ . '/../Model/Enum/Role.php';

class UtilisateurRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function create(Utilisateur $utilisateur): int
    {
        $sql = "INSERT INTO utilisateurs (login, mot_de_passe, role)
                VALUES (:login, :mot_de_passe, :role)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'login'        => $utilisateur->getLogin(),
            'mot_de_passe' => $utilisateur->getMotDePasseHache(),
            'role'         => $utilisateur->getRole()->value,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $utilisateur->setId($id);

        return $id;
    }

    public function findById(int $id): ?Utilisateur
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne ? $this->hydrater($ligne) : null;
    }

    /**
     * Recherche par "login". Dans ce projet, le login utilise correspond
     * a l'email saisi sur l'ecran de connexion.
     */
    public function findByLogin(string $login): ?Utilisateur
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE login = :login");
        $stmt->execute(['login' => $login]);

        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne ? $this->hydrater($ligne) : null;
    }

    /** @return Utilisateur[] */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM utilisateurs ORDER BY login ASC");

        $utilisateurs = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $utilisateurs[] = $this->hydrater($ligne);
        }

        return $utilisateurs;
    }

    private function hydrater(array $ligne): Utilisateur
    {
        return new Utilisateur(
            login: $ligne['login'],
            motDePasseHache: $ligne['mot_de_passe'],
            role: Role::from($ligne['role']),
            id: (int) $ligne['id'],
        );
    }
}