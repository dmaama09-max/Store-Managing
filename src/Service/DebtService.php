<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Model/Entity/Paiement.php';
require_once __DIR__ . '/../Repository/DetteRepository.php';
require_once __DIR__ . '/../Repository/PaiementRepository.php';
require_once __DIR__ . '/../Repository/ClientRepository.php';

/**
 * DebtService
 *
 * Logique metier du remboursement des dettes. Un remboursement touche
 * TROIS choses a la fois : la Dette elle-meme (montant_restant/statut),
 * l'historique des Paiements, et l'encours du Client -> transaction SQL
 * obligatoire, comme pour VenteService.
 */
class DebtService
{
    private PDO $pdo;
    private DetteRepository $detteRepository;
    private PaiementRepository $paiementRepository;
    private ClientRepository $clientRepository;

    public function __construct()
    {
        $this->pdo                = Database::getInstance();
        $this->detteRepository     = new DetteRepository();
        $this->paiementRepository  = new PaiementRepository();
        $this->clientRepository    = new ClientRepository();
    }

    /**
     * Enregistre un remboursement (partiel ou total) sur une dette.
     *
     * @throws Exception Si la dette n'existe pas, si le montant est invalide,
     *                    ou si le montant depasse le reste du.
     */
    public function rembourserDette(int $detteId, float $montant, string $modePaiement): Dette
    {
        $this->pdo->beginTransaction();

        try {
            $dette = $this->detteRepository->findById($detteId);

            if ($dette === null) {
                throw new Exception("Dette introuvable (id: {$detteId}).");
            }

            if ($dette->estSoldee()) {
                throw new Exception("Cette dette est déjà intégralement soldée.");
            }

            // enregistrerPaiement() (codee dans Dette.php au Step 2.1) applique
            // la regle metier : leve une exception si $montant > montantRestant,
            // et bascule automatiquement le statut a SOLDEE si ca tombe a 0.
            $dette->enregistrerPaiement($montant);
            $this->detteRepository->update($dette);

            $paiement = new Paiement(
                detteId: $detteId,
                montant: $montant,
                modePaiement: $modePaiement,
            );
            $this->paiementRepository->create($paiement);

            // L'encours du client diminue du montant rembourse
            $client = $this->clientRepository->findById($dette->getClientId());
            if ($client === null) {
                throw new Exception("Client associé à cette dette introuvable (id: {$dette->getClientId()}).");
            }
            $client->diminuerEncours($montant);
            $this->clientRepository->update($client);

            $this->pdo->commit();

            return $dette;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** @return Dette[] */
    public function listerDettesActives(): array
    {
        return $this->detteRepository->findActives();
    }
}