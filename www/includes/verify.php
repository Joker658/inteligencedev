<?php
require_once __DIR__ . '/../functions.php';

$errors = [];
$successMessage = null;
$globalErrors = consumeGlobalErrors();
$pendingVerification = getPendingVerification();

if (isPostRequest()) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La session a expiré. Veuillez réessayer.';
        regenerateCsrfToken();
    } else {
        $userId = (int) ($_POST['user_id'] ?? ($pendingVerification['user_id'] ?? 0));
        $code = trim((string) ($_POST['verification_code'] ?? ''));

        if ($userId <= 0) {
            $errors[] = 'Votre session de vérification a expiré. Veuillez recréer un compte.';
        } else {
            $result = verifyEmailCode($userId, $code);

            if ($result['success']) {
                $successMessage = 'Votre adresse e-mail a été confirmée. Vous pouvez maintenant vous connecter.';
                clearPendingVerification();
                $pendingVerification = null;
            } else {
                $errors = array_merge($errors, $result['errors']);
            }
        }
    }
}

$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmez votre compte - IntelligenceDev</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <div class="branding">
            <a href="/index.php" class="logo">IntelligenceDev</a>
            <span class="tagline">Sécurisons votre accès</span>
        </div>
    </div>
</header>

<main class="auth-page">
    <div class="container auth-card">
        <?php if ($globalErrors): ?>
            <div class="alert error">
                <ul>
                    <?php foreach ($globalErrors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <h1>Confirmez votre compte</h1>
        <p>Entrez le code de vérification reçu lors de votre inscription pour activer votre compte.</p>

        <?php if ($pendingVerification && $pendingVerification['code']): ?>
            <div class="verification-hint">
                <p>Votre code de vérification :</p>
                <p class="verification-code"><?= htmlspecialchars($pendingVerification['code'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if (!empty($pendingVerification['email'])): ?>
                    <p class="muted">Compte associé à : <?= htmlspecialchars($pendingVerification['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="alert success">
                <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                <a href="/includes/login.php">Se connecter</a>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($pendingVerification): ?>
            <form method="post" class="form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $pendingVerification['user_id'], ENT_QUOTES, 'UTF-8'); ?>">

                <label for="verification_code">Code de vérification</label>
                <input type="text" id="verification_code" name="verification_code" value="<?= htmlspecialchars($pendingVerification['code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

                <button type="submit" class="button primary full">Confirmer mon compte</button>
            </form>
        <?php elseif (!$successMessage): ?>
            <p class="form-footer">Vous n'avez pas encore de compte à confirmer ? <a href="/includes/register.php">Créer un compte</a>.</p>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
