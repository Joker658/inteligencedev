<?php
require_once __DIR__ . '/../functions.php';

$errors = [];
$formData = [
    'username' => '',
    'email' => '',
];
$globalErrors = consumeGlobalErrors();
$successMessage = null;

if (isPostRequest()) {
    $formData['username'] = trim($_POST['username'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La session a expiré. Veuillez réessayer.';
        regenerateCsrfToken();
    } else {
        $result = registerUser($formData['username'], $formData['email'], $password);

        if ($result['success']) {
            $successMessage = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
            $formData = ['username' => '', 'email' => ''];
        } else {
            $errors = array_merge($errors, $result['errors']);
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
    <title>Créer un compte - IntelligenceDev</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <div class="branding">
            <a href="/index.php" class="logo">IntelligenceDev</a>
            <span class="tagline">Rejoignez la communauté</span>
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

        <h1>Créer un compte</h1>
        <p>Inscrivez-vous pour accéder à notre catalogue de scripts exclusifs.</p>

        <?php if ($successMessage): ?>
            <div class="alert success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?> <a href="/includes/login.php">Se connecter</a></div>
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

        <form method="post" class="form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($formData['username'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="button primary full">Créer mon compte</button>
        </form>
        <p class="form-footer">Déjà membre ? <a href="/includes/login.php">Connectez-vous ici</a>.</p>
    </div>
</main>
</body>
</html>
