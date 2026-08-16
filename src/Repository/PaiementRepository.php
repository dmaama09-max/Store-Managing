<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Model/Entity/Paiement.php';

class PaiementRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function create(Paiement $paiement): int
    {
        $sql = "INSERT INTO paiements (dette_id, montant, date_paiement, mode_paiement)
                VALUES (:dette_id, :montant, :date_paiement, :mode_paiement)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'dette_id'      => $paiement->getDetteId(),
            'montant'       => $paiement->getMontant(),
            'date_paiement' => $paiement->getDate()->format('Y-m-d H:i:s'),
            'mode_paiement' => $paiement->getModePaiement(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $paiement->setId($id);

        return $id;
    }

    /** @return Paiement[] */
    public function findByDette(int $detteId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM paiements WHERE dette_id = :dette_id ORDER BY date_paiement DESC");
        $stmt->execute(['dette_id' => $detteId]);

        $paiements = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
            $paiements[] = new Paiement(
                detteId: (int) $ligne['dette_id'],
                montant: (float) $ligne['montant'],
                modePaiement: $ligne['mode_paiement'],
                date: new DateTime($ligne['date_paiement']),
                id: (int) $ligne['id'],
            );
        }

        return $paiements;
    }

    /**
     * Somme totale de tous les paiements enregistres, toutes dettes confondues.
     * Utile pour la statistique "Total Recouvrements" du Dashboard/vue Dettes.
     */
    public function sommeTotale(): float
    {
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(montant), 0) AS total FROM paiements");
        return (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}