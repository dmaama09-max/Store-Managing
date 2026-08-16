<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Model/Entity/Client.php';

class ClientRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function create(Client $client): int
    {
        $sql = "INSERT INTO clients (prenom, nom, telephone, email, limite_credit)
                VALUES (:prenom, :nom, :telephone, :email, :limite_credit)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'prenom'        => $client->getPrenom(),
            'nom'           => $client->getNom(),
            'telephone'     => $client->getTelephone(),
            'email'         => $client->getEmail(),
            'limite_credit' => $client->getLimiteCredit(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $client->setId($id);

        return $id;
    }

    public function findById(int $id): ?Client
    {
        $stmt = $this->pdo->prepare("SELECT * FROM clients WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne ? $this->hydrater($ligne) : null;
    }

    /** @return Client[] */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM clients ORDER BY nom ASC");

        $clients = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $clients[] = $this->hydrater($ligne);
        }

        return $clients;
    }

    /**
     * Renvoie uniquement les clients ayant un encours actuel > 0
     * (utile pour la vue "Clients débiteurs").
     * NB : l'encoursActuel n'est pas stocke tel quel dans la table clients,
     * il est recalcule ici via une sous-requete sur les dettes non soldees.
     * @return Client[]
     */
    public function findDebiteurs(): array
    {
        $sql = "SELECT c.*, COALESCE(SUM(d.montant_restant), 0) AS encours_actuel
                FROM clients c
                INNER JOIN dettes d ON d.client_id = c.id AND d.statut = 'EN_COURS'
                GROUP BY c.id
                HAVING encours_actuel > 0
                ORDER BY encours_actuel DESC";

        $stmt = $this->pdo->query($sql);

        $clients = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $clients[] = $this->hydrater($ligne);
        }

        return $clients;
    }

    public function update(Client $client): void
    {
        $sql = "UPDATE clients
                SET prenom = :prenom,
                    nom = :nom,
                    telephone = :telephone,
                    email = :email,
                    limite_credit = :limite_credit
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'prenom'        => $client->getPrenom(),
            'nom'           => $client->getNom(),
            'telephone'     => $client->getTelephone(),
            'email'         => $client->getEmail(),
            'limite_credit' => $client->getLimiteCredit(),
            'id'            => $client->getId(),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM clients WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    /**
     * L'encours_actuel n'existe pas forcement dans chaque ligne (ex: findAll()
     * ne le calcule pas). On utilise donc une valeur par defaut a 0 si absente,
     * pour ne pas faire planter le constructeur de Client.
     */
    private function hydrater(array $ligne): Client
    {
        return new Client(
            prenom: $ligne['prenom'],
            nom: $ligne['nom'],
            telephone: $ligne['telephone'],
            email: $ligne['email'],
            limiteCredit: (float) $ligne['limite_credit'],
            encoursActuel: isset($ligne['encours_actuel']) ? (float) $ligne['encours_actuel'] : 0.0,
            id: (int) $ligne['id'],
        );
    }
}