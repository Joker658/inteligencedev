<?php
require_once __DIR__ . '/../functions.php';

$errors = [];
$success = false;
$resentCode = null;
$formData = [
    'email' => '',
    'code' => '',
];
$resendErrors = [];
$resendSuccess = false;
$resendData = [
    'email' => '',
];
$globalErrors = consumeGlobalErrors();

if (isPostRequest()) {
    $action = $_POST['action'] ?? 'verify';

    if ($action === 'resend') {
        $resendData['email'] = trim($_POST['resend_email'] ?? '');
    } else {
        $formData['email'] = trim($_POST['email'] ?? '');
        $formData['code'] = trim($_POST['code'] ?? '');
        $resendData['email'] = $formData['email'];
    }

    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        if ($action === 'resend') {
            $resendErrors[] = 'La session a expiré. Veuillez réessayer.';
        } else {
            $errors[] = 'La session a expiré. Veuillez réessayer.';
        }
        regenerateCsrfToken();
    } else {
        if ($action === 'resend') {
            $result = resendVerificationCode($resendData['email']);

            if ($result['success']) {
                $resendSuccess = true;
                $resendErrors = [];
                $resentCode = $result['verification_code'] ?? null;
                regenerateCsrfToken();
            } else {
                $resendErrors = array_merge($resendErrors, $result['errors']);
            }
        } else {
            $result = verifyEmailAddress($formData['email'], $formData['code']);

            if ($result['success']) {
                $success = true;
                $formData = ['email' => '', 'code' => ''];
                $resendData['email'] = '';
                regenerateCsrfToken();
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
    <title>Vérifier mon adresse e-mail - IntelligenceDev</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <div class="branding">
            <a href="/index.php" class="logo">IntelligenceDev</a>
            <span class="tagline">Confirmez votre compte</span>
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

        <h1>Vérifier mon adresse e-mail</h1>
        <p>Entrez l'adresse e-mail utilisée lors de votre inscription ainsi que le code à six chiffres affiché après la création du compte ou après une régénération.</p>

        <?php if ($success): ?>
            <div class="alert success">Votre adresse e-mail a bien été vérifiée. Vous pouvez maintenant <a href="/includes/login.php">vous connecter</a>.</div>
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
            <input type="hidden" name="action" value="verify">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="code">Code de vérification</label>
            <input type="text" id="code" name="code" value="<?= htmlspecialchars($formData['code'], ENT_QUOTES, 'UTF-8'); ?>" pattern="[0-9]{6}" maxlength="6" required>

            <button type="submit" class="button primary full">Confirmer mon e-mail</button>
        </form>
        <section class="resend-section">
            <h2>Besoin d'un nouveau code ?</h2>
            <p>Générez un nouveau code de vérification ci-dessous. Il sera affiché directement sur cette page.</p>

            <?php if ($resendSuccess): ?>
                <div class="alert success">
                    <p>Un nouveau code vient d'être généré. Saisissez-le dans le formulaire ci-dessus dans les 30 prochaines minutes.</p>
                    <?php if ($resentCode): ?>
                        <p class="verification-code">Nouveau code : <strong><?= htmlspecialchars($resentCode, ENT_QUOTES, 'UTF-8'); ?></strong></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($resendErrors): ?>
                <div class="alert error">
                    <ul>
                        <?php foreach ($resendErrors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="form">
                <input type="hidden" name="action" value="resend">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <label for="resend-email">Adresse e-mail</label>
                <input type="email" id="resend-email" name="resend_email" value="<?= htmlspecialchars($resendData['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                <button type="submit" class="button secondary full">Renvoyer le code</button>
            </form>
        </section>
    </div>
</main>
</body>
</html>
