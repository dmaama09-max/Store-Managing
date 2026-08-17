<?php

require_once __DIR__ . '/../Core/AuthManager.php';

class AuthController
{
    private AuthManager $authManager;

    public function __construct()
    {
        $this->authManager = new AuthManager();
    }

    /**
     * Traite le formulaire de connexion. En cas de succes, redirige vers
     * la vue par defaut du role (logique reprise du rolePermissions
     * deja present cote JS dans le HTML). En cas d'echec, reaffiche le
     * formulaire avec un message d'erreur.
     */
    public function handleLogin(): void
    {
        $messageErreur = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
            try {
                $email        = (string) ($_POST['email'] ?? '');
                $motDePasse   = (string) ($_POST['password'] ?? '');

                $utilisateur = $this->authManager->login($email, $motDePasse);

                // Redirection vers la vue par defaut selon le role,
                // fidele au mapping rolePermissions deja present dans le HTML.
                $vueParDefaut = match ($utilisateur->getRole()) {
                    Role::ADMIN      => 'dashboard',
                    Role::VENTE      => 'pos',
                    Role::STOCK      => 'supplies',
                    Role::INVENTAIRE => 'catalog',
                };

                header('Location: /index.php?view=' . $vueParDefaut);
                exit;

            } catch (Exception $e) {
                $messageErreur = $e->getMessage();
            }
        }

        // Si on arrive ici : soit c'est un simple GET (afficher le formulaire),
        // soit le login a echoue (on reaffiche avec le message d'erreur).
        require __DIR__ . '/../../views/auth/login.php';
    }

    public function handleLogout(): void
    {
        $this->authManager->logout();
        header('Location: /index.php');
        exit;
    }
}