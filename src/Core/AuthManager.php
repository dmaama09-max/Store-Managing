<?php

require_once __DIR__ . '/../Repository/UtilisateurRepository.php';
require_once __DIR__ . '/../Model/Enum/Role.php';

/**
 * AuthManager
 *
 * Gere TOUT ce qui touche a l'authentification et aux droits d'acces :
 * - verifier un email/mot de passe et ouvrir une session
 * - savoir qui est connecte
 * - filtrer l'acces a une page selon le role de l'utilisateur connecte
 *
 * Utilise $_SESSION (le mecanisme natif de PHP pour se souvenir d'un
 * utilisateur d'une page a l'autre, via un cookie de session cote navigateur).
 */
class AuthManager
{
    private UtilisateurRepository $utilisateurRepository;

    public function __construct()
    {
        // session_start() doit etre appele avant tout affichage HTML.
        // On verifie session_status() pour ne jamais demarrer une session
        // deux fois (ce qui provoquerait une erreur PHP).
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->utilisateurRepository = new UtilisateurRepository();
    }

    /**
     * Verifie les identifiants et ouvre la session si valides.
     *
     * @throws Exception Si l'email est inconnu ou le mot de passe incorrect.
     */
    public function login(string $email, string $motDePasseEnClair): Utilisateur
    {
        $utilisateur = $this->utilisateurRepository->findByLogin($email);

        // Message volontairement generique (on ne dit pas si c'est l'email
        // OU le mot de passe qui est faux) : une bonne pratique de securite,
        // pour ne pas aider un attaquant a deviner quels emails existent.
        if ($utilisateur === null || !$utilisateur->verifierMotDePasse($motDePasseEnClair)) {
            throw new Exception("Email ou mot de passe incorrect.");
        }

        $_SESSION['utilisateur_id'] = $utilisateur->getId();
        $_SESSION['utilisateur_role'] = $utilisateur->getRole()->value;
        $_SESSION['utilisateur_login'] = $utilisateur->getLogin();

        return $utilisateur;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public function estConnecte(): bool
    {
        return isset($_SESSION['utilisateur_id']);
    }

    /**
     * Renvoie l'utilisateur actuellement connecte, ou null si personne
     * n'est connecte. Va rechercher l'objet complet en base a chaque appel
     * (simple, mais fait une requete supplementaire - acceptable pour ce projet).
     */
    public function utilisateurConnecte(): ?Utilisateur
    {
        if (!$this->estConnecte()) {
            return null;
        }

        return $this->utilisateurRepository->findById($_SESSION['utilisateur_id']);
    }

    public function roleConnecte(): ?Role
    {
        if (!$this->estConnecte()) {
            return null;
        }

        return Role::from($_SESSION['utilisateur_role']);
    }

    /**
     * Verifie que l'utilisateur connecte a le droit d'acceder a la page
     * courante. A appeler en TOUT DEBUT de handle() dans chaque Controller
     * (POSController, DetteController, SupplyController, ...).
     *
     * Exemple d'utilisation :
     *   $auth->checkAccess(Role::ADMIN, Role::VENTE);
     *
     * @throws Exception Si personne n'est connecte, ou si le role connecte
     *                    ne fait pas partie des roles autorises.
     */
    public function checkAccess(Role ...$rolesAutorises): void
    {
        if (!$this->estConnecte()) {
            throw new Exception("Vous devez être connecté pour accéder à cette page.");
        }

        $roleActuel = $this->roleConnecte();

        if (!in_array($roleActuel, $rolesAutorises, true)) {
            throw new Exception("Accès refusé : votre rôle ({$roleActuel->libelle()}) ne permet pas d'accéder à cette page.");
        }
    }
}