<?php
require_once __DIR__ . '/../functions.php';

$errors = [];
$formData = [
    'identifier' => '',
];
$globalErrors = consumeGlobalErrors();

if (isPostRequest()) {
    $formData['identifier'] = trim($_POST['identifier'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La session a expiré. Veuillez réessayer.';
        regenerateCsrfToken();
    } else {
        $result = attemptLogin($formData['identifier'], $password);

        if ($result['success']) {
            header('Location: /index.php');
            exit;
        }

        $errors = array_merge($errors, $result['errors']);
    }
}

$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - IntelligenceDev</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <div class="branding">
            <a href="/index.php" class="logo">IntelligenceDev</a>
            <span class="tagline">Heureux de vous revoir</span>
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

        <h1>Connexion</h1>
        <p>Connectez-vous pour accéder à vos scripts et à votre espace personnel.</p>

        <?php if ($errors): ?>
            <div class="alert error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="identifier">Nom d'utilisateur ou e-mail</label>
            <input type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($formData['identifier'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="button primary full">Se connecter</button>
        </form>
        <p class="form-footer">Pas encore de compte ? <a href="/includes/register.php">Inscrivez-vous ici</a>.</p>
    </div>
</main>
</body>
</html>
