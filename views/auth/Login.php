<?php
/** views/auth/login.php — recoit $messageErreur (string|null) */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — StoreManager Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">

    <div style="width: 100%; max-width: 400px; padding: 32px; background: var(--panel-bg); border: 1px solid var(--border-color); border-radius: 16px;">
        <div style="font-weight: 800; font-size: 18px; color: var(--accent); margin-bottom: 24px; text-align: center;">
            StoreManager Pro
        </div>

        <?php if ($messageErreur): ?>
            <div style="background: rgba(248, 113, 113, 0.1); border: 1px solid var(--danger); color: #fca5a5; padding: 10px 14px; border-radius: 10px; font-size: 12px; margin-bottom: 16px;">
                ⚠️ <?= htmlspecialchars($messageErreur) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/index.php" style="display: flex; flex-direction: column; gap: 16px;">
            <input type="hidden" name="action" value="login">

            <div>
                <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px; text-transform: uppercase;">Adresse email</label>
                <input type="email" name="email" class="form-control" placeholder="vous@boutique.sn" required
                       style="width: 100%; padding: 12px 14px; background: rgba(15,23,42,0.6); border: 1px solid var(--border-color); border-radius: 10px; color: #fff; font-size: 13px;">
            </div>

            <div>
                <label style="font-size: 11px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px; text-transform: uppercase;">Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="Votre mot de passe" required
                       style="width: 100%; padding: 12px 14px; background: rgba(15,23,42,0.6); border: 1px solid var(--border-color); border-radius: 10px; color: #fff; font-size: 13px;">
            </div>

            <button type="submit" class="btn-submit" style="padding: 14px; font-weight: 800; text-transform: uppercase;">
                Se connecter ➔
            </button>
        </form>

        <div style="text-align: center; margin-top: 18px; font-size: 11px; color: var(--text-muted);">
            ✓ Tous les comptes de démo utilisent le mot de passe : <strong style="color: var(--accent);">demo1234</strong>
        </div>
    </div>

</body>
</html>