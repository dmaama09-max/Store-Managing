<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Model/Entity/Dette.php';

class DetteRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function create(Dette $dette): int
    {
        $sql = "INSERT INTO dettes (commande_id, client_id, montant_initial, montant_restant, statut, date_echeance)
                VALUES (:commande_id, :client_id, :montant_initial, :montant_restant, :statut, :date_echeance)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'commande_id'     => $dette->getCommandeId(),
            'client_id'       => $dette->getClientId(),
            'montant_initial' => $dette->getMontantInitial(),
            'montant_restant' => $dette->getMontantRestant(),
            'statut'          => $dette->getStatut(),
            'date_echeance'   => $dette->getDateEcheance()?->format('Y-m-d'),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $dette->setId($id);

        return $id;
    }

    public function findById(int $id): ?Dette
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dettes WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

        return $ligne ? $this->hydrater($ligne) : null;
    }

    /** @return Dette[] */
    public function findActives(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM dettes WHERE statut = 'EN_COURS' ORDER BY id DESC");

        $dettes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $dettes[] = $this->hydrater($ligne);
        }

        return $dettes;
    }

    /**
     * Version enrichie pour l'affichage (vue Dettes) : jointe avec clients
     * (nom/telephone) et commandes (date de creation de la dette = date
     * de la vente a credit d'origine, la table dettes n'ayant pas sa
     * propre colonne de date de creation).
     *
     * @return array Tableau de lignes brutes (id, montant_restant, prenom, nom, telephone, date_vente, ...)
     */
    public function findActivesAvecDetails(): array
    {
        $sql = "SELECT d.*, c.prenom, c.nom, c.telephone, cmd.date_vente
                FROM dettes d
                INNER JOIN clients c ON c.id = d.client_id
                LEFT JOIN commandes cmd ON cmd.id = d.commande_id
                WHERE d.statut = 'EN_COURS'
                ORDER BY cmd.date_vente DESC";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return Dette[] */
    public function findByClient(int $clientId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dettes WHERE client_id = :client_id ORDER BY id DESC");
        $stmt->execute(['client_id' => $clientId]);

        $dettes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $dettes[] = $this->hydrater($ligne);
        }

        return $dettes;
    }

    public function update(Dette $dette): void
    {
        $sql = "UPDATE dettes
                SET montant_restant = :montant_restant,
                    statut = :statut
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'montant_restant' => $dette->getMontantRestant(),
            'statut'          => $dette->getStatut(),
            'id'              => $dette->getId(),
        ]);
    }

    private function hydrater(array $ligne): Dette
    {
        $dette = new Dette(
            clientId: (int) $ligne['client_id'],
            montantInitial: (float) $ligne['montant_initial'],
            commandeId: $ligne['commande_id'] !== null ? (int) $ligne['commande_id'] : null,
            dateEcheance: $ligne['date_echeance'] ? new DateTime($ligne['date_echeance']) : null,
            id: (int) $ligne['id'],
        );

        // Le constructeur de Dette initialise montantRestant = montantInitial
        // et statut = EN_COURS (logique pour une NOUVELLE dette). Ici, on
        // restaure l'etat REEL lu en base, qui peut deja avoir ete remboursee
        // partiellement. On simule ca en "rejouant" un paiement equivalent
        // a ce qui a deja ete rembourse.
        $dejaRembourse = (float) $ligne['montant_initial'] - (float) $ligne['montant_restant'];
        if ($dejaRembourse > 0) {
            $dette->enregistrerPaiement($dejaRembourse);
        }

        return $dette;
    }
}