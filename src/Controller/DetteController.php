<?php

require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Core/AuthManager.php';
require_once __DIR__ . '/../Service/DebtService.php';
require_once __DIR__ . '/../Repository/DetteRepository.php';
require_once __DIR__ . '/../Repository/PaiementRepository.php';

/**
 * DetteController
 *
 * NOTE : ce controller n'etait pas explicitement liste dans le planning
 * (qui ne mentionnait que DetteRepository + DebtService + la vue), mais
 * il est necessaire pour orchestrer l'affichage et le traitement du
 * formulaire, exactement comme POSController le fait pour la vue POS.
 */
class DetteController
{
    private DebtService $debtService;
    private DetteRepository $detteRepository;
    private PaiementRepository $paiementRepository;
    private PDO $pdo;

    public function __construct()
    {
        $this->debtService        = new DebtService();
        $this->detteRepository     = new DetteRepository();
        $this->paiementRepository  = new PaiementRepository();
        $this->pdo                 = Database::getInstance();
    }

    public function handle(): void
    {
        (new AuthManager())->checkAccess(Role::ADMIN, Role::VENTE);

        $messageErreur = null;
        $messageSucces = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_payment') {
            try {
                $detteId      = (int) ($_POST['dette_id'] ?? 0);
                $montantVerse = (float) ($_POST['montant_verse'] ?? 0);
                $modePaiement = (string) ($_POST['mode_paiement'] ?? '');

                $this->debtService->rembourserDette($detteId, $montantVerse, $modePaiement);
                $messageSucces = "Remboursement de {$montantVerse} FCFA enregistré avec succès.";
            } catch (Exception $e) {
                $messageErreur = $e->getMessage();
            }
        }

        // ----- Donnees pour l'affichage -----
        $dettesActives = $this->detteRepository->findActivesAvecDetails();

        // Pour chaque dette active, on recupere ses paiements et les lignes
        // de la commande d'origine (pour les drawers "Paiements" et "Articles")
        $dettesAvecDetails = [];
        foreach ($dettesActives as $dette) {
            $paiements = $this->paiementRepository->findByDette((int) $dette['id']);
            $lignes    = $this->recupererLignesCommande($dette['commande_id']);

            $dettesAvecDetails[] = [
                'dette'     => $dette,
                'paiements' => $paiements,
                'lignes'    => $lignes,
            ];
        }

        $stats = $this->calculerStatistiques($dettesActives);

        require __DIR__ . '/../../views/dettes/index.php';
    }

    /**
     * Statistiques pour les 3 cartes du haut de la vue :
     * Créances actives, Clients débiteurs, Total des recouvrements.
     */
    private function calculerStatistiques(array $dettesActives): array
    {
        $creancesActives = 0.0;
        $clientsUniques  = [];

        foreach ($dettesActives as $dette) {
            $creancesActives += (float) $dette['montant_restant'];
            $clientsUniques[$dette['client_id']] = true;
        }

        return [
            'creances_actives'     => $creancesActives,
            'nombre_debiteurs'     => count($clientsUniques),
            'total_recouvrements'  => $this->paiementRepository->sommeTotale(),
        ];
    }

    /**
     * @return array Lignes de la commande d'origine de la dette (produit, quantite, prix)
     */
    private function recupererLignesCommande(?int $commandeId): array
    {
        if ($commandeId === null) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            "SELECT lc.*, p.nom AS produit_nom
             FROM lignes_commande lc
             INNER JOIN produits p ON p.id = lc.produit_id
             WHERE lc.commande_id = :commande_id"
        );
        $stmt->execute(['commande_id' => $commandeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}