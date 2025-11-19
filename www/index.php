<?php
require_once __DIR__ . '/functions.php';

$user = getCurrentUser();
$globalErrors = consumeGlobalErrors();
$loginErrors = [];
$registerErrors = [];
$registerSuccess = false;
$initialModal = null;
$loginData = [
    'identifier' => ''
];
$registerData = [
    'username' => '',
    'email' => ''
];

$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';
$isReglementPage = strpos($currentPath, '/Règlement/') !== false;

if (isPostRequest()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $loginData['identifier'] = trim($_POST['identifier'] ?? '');
    } elseif ($action === 'register') {
        $registerData['username'] = trim($_POST['username'] ?? '');
        $registerData['email'] = trim($_POST['email'] ?? '');
    }

    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'La session a expiré. Veuillez réessayer.';

        if ($action === 'register') {
            $registerErrors[] = $errorMessage;
            $initialModal = 'register-modal';
        } else {
            $loginErrors[] = $errorMessage;
            $initialModal = 'login-modal';
        }

        regenerateCsrfToken();
    } else {
        if ($action === 'login') {
            $password = (string) ($_POST['password'] ?? '');
            $result = attemptLogin($loginData['identifier'], $password);

            if ($result['success']) {
                header('Location: /index.php');
                exit;
            }

            $loginErrors = array_merge($loginErrors, $result['errors']);
            $initialModal = 'login-modal';
        } elseif ($action === 'register') {
            $password = (string) ($_POST['password'] ?? '');
            $result = registerUser($registerData['username'], $registerData['email'], $password);

            if ($result['success']) {
                setPendingVerification([
                    'user_id' => $result['user_id'],
                    'email' => $result['email'],
                    'code' => $result['verification_code'],
                ]);

                header('Location: /includes/verify.php');
                exit;
            } else {
                $registerErrors = array_merge($registerErrors, $result['errors']);
                $initialModal = 'register-modal';
            }
        }
    }
}

$csrfToken = getCsrfToken();

if (!$initialModal) {
    $initialModal = 'refund-policy-modal';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IntelligenceDev Scripts</title>
    <link rel="icon" type="image/png" href="/img/Favicon.png">
    <link rel="stylesheet" href="/style.css">
</head>
<body<?= $initialModal ? ' data-initial-modal="' . htmlspecialchars($initialModal, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
<header class="site-header">
    <div class="container">
        <div class="branding">
            <a href="/index.php" class="logo-link" aria-label="Accueil IntelligenceDev">
                <img src="/img/LogoWeb.png" alt="IntelligenceDev" class="logo-image">
            </a>
        </div>
        <nav class="main-nav">
            <a href="/index.php" class="nav-link<?= $currentPath === '/index.php' ? ' active' : ''; ?>">Accueil</a>
            <a href="/Règlement/index.php" class="nav-link<?= $isReglementPage ? ' active' : ''; ?>">Règlement</a>
            <div class="nav-actions">
                <?php if ($user): ?>
                    <span class="welcome">Bonjour, <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?> !</span>
                    <a href="/includes/logout.php" class="button secondary">Déconnexion</a>
                <?php else: ?>
                    <button type="button" class="button secondary" data-modal-target="login-modal">Connexion</button>
                    <button type="button" class="button primary" data-modal-target="register-modal">Créer un compte</button>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<main>
    <?php if ($globalErrors): ?>
        <div class="container">
            <div class="alert error">
                <ul>
                    <?php foreach ($globalErrors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <section class="hero">
        <div class="container">
            <h1>Boostez vos projets avec nos scripts prêts à l'emploi</h1>
            <p>Découvrez une collection de scripts optimisés pour automatiser vos tâches et accélérer vos développements.</p>
            <div class="hero-actions">
                <a class="button primary" href="#catalogue">Explorer le catalogue</a>
                <button type="button" class="button secondary" data-modal-target="register-modal">Rejoindre la communauté</button>
            </div>
        </div>
    </section>

    <section class="features" id="catalogue">
        <div class="container">
            <h2>Pourquoi choisir IntelligenceDev ?</h2>
            <div class="feature-grid">
                <article class="feature">
                    <h3>Performances optimisées</h3>
                    <p>Nos scripts sont testés et optimisés pour offrir des performances rapides et fiables dans tous les environnements.</p>
                </article>
                <article class="feature">
                    <h3>Mises à jour régulières</h3>
                    <p>Recevez des mises à jour fréquentes et profitez des améliorations basées sur les retours de la communauté.</p>
                </article>
                <article class="feature">
                    <h3>Support dédié</h3>
                    <p>Une équipe de support réactive est disponible pour répondre à vos questions et vous accompagner.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <h2>Prêt à transformer vos idées en réalité ?</h2>
            <p>Créez un compte gratuitement et accédez à tous nos scripts premium.</p>
            <button type="button" class="button primary" data-modal-target="register-modal">Commencer maintenant</button>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y'); ?> IntelligenceDev. Tous droits réservés.</p>
    </div>
</footer>

<div class="modal" id="refund-policy-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-content refund-policy">
        <button type="button" class="modal-close" aria-label="Fermer" data-close-modal>&times;</button>
        <div class="modal-icon-badge" aria-hidden="true">
            <span>🛡️</span>
        </div>
        <p class="modal-eyebrow">Informations importantes</p>
        <h2>Politique de remboursement</h2>
        <p class="modal-subtitle">Veuillez prendre connaissance de notre politique avant de parcourir nos offres.</p>
        <div class="modal-body">
            <div class="policy-highlight">
                <p><strong>Les scripts numériques sont livrés instantanément après votre achat.</strong></p>
                <p>Merci de lire attentivement les conditions ci-dessous avant de poursuivre votre navigation.</p>
            </div>
            <ul class="policy-list">
                <li>
                    <span class="policy-bullet" aria-hidden="true">✓</span>
                    <div>
                        <h3>Achats définitifs</h3>
                        <p>Une fois le téléchargement disponible, la commande est considérée comme finale.</p>
                    </div>
                </li>
                <li>
                    <span class="policy-bullet" aria-hidden="true">✓</span>
                    <div>
                        <h3>Remboursements sous 48&nbsp;heures.</h3>
                        <p>
                            Un remboursement peut être envisagé uniquement en cas de dysfonctionnement avéré signalé sous 24 heures. <br>
                            <span class="refund-notice2">À condition que les scripts ne soient pas open source.</span>
                        </p>
                    </div>
                </li>
            </ul>
            <p class="policy-move-up">
                En continuant votre navigation, vous confirmez avoir pris connaissance et accepté cette politique.
            </p>
        </div>
        <div class="modal-actions">
            <button type="button" class="button primary full" data-close-modal>J'ai compris</button>
        </div>
    </div>
</div>

<div class="modal" id="login-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-content">
        <button type="button" class="modal-close" aria-label="Fermer" data-close-modal>&times;</button>
        <h2>Connexion</h2>
        <p class="modal-subtitle">Connectez-vous pour accéder à vos scripts et à votre espace personnel.</p>

        <?php if ($loginErrors): ?>
            <div class="alert error">
                <ul>
                    <?php foreach ($loginErrors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="form">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="login-identifier">Nom d'utilisateur ou e-mail</label>
            <input type="text" id="login-identifier" name="identifier" value="<?= htmlspecialchars($loginData['identifier'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="login-password">Mot de passe</label>
            <input type="password" id="login-password" name="password" required>

            <button type="submit" class="button primary full">Se connecter</button>
        </form>
        <p class="form-footer">Pas encore de compte ? <button type="button" class="link-button" data-switch-modal="register-modal">Inscrivez-vous ici</button>.</p>
    </div>
</div>

<div class="modal" id="register-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-content">
        <button type="button" class="modal-close" aria-label="Fermer" data-close-modal>&times;</button>
        <h2>Créer un compte</h2>
        <p class="modal-subtitle">Inscrivez-vous pour accéder à notre catalogue de scripts exclusifs.</p>

        <?php if ($registerSuccess): ?>
            <div class="alert success">Compte créé avec succès ! Vous pouvez maintenant vous connecter.</div>
        <?php endif; ?>

        <?php if ($registerErrors): ?>
            <div class="alert error">
                <ul>
                    <?php foreach ($registerErrors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="form">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="register-username">Nom d'utilisateur</label>
            <input type="text" id="register-username" name="username" value="<?= htmlspecialchars($registerData['username'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="register-email">Adresse e-mail</label>
            <input type="email" id="register-email" name="email" value="<?= htmlspecialchars($registerData['email'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="register-password">Mot de passe</label>
            <input type="password" id="register-password" name="password" required>

            <button type="submit" class="button primary full">Créer mon compte</button>
        </form>
        <p class="form-footer">Déjà membre ? <button type="button" class="link-button" data-switch-modal="login-modal">Connectez-vous ici</button>.</p>
    </div>
</div>

<script>
(function () {
    const body = document.body;

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) {
            return;
        }
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        body.classList.add('modal-open');
        const focusTarget = modal.querySelector('input, button, [href], select, textarea');
        if (focusTarget) {
            focusTarget.focus();
        }
    }

    function closeModal(modal) {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.modal.open')) {
            body.classList.remove('modal-open');
        }
    }

    function handleOpenClick(event) {
        const target = event.currentTarget.getAttribute('data-modal-target');
        if (!target) {
            return;
        }
        openModal(target);
    }

    document.querySelectorAll('[data-modal-target]').forEach((trigger) => {
        trigger.addEventListener('click', handleOpenClick);
    });

    document.querySelectorAll('.modal').forEach((modal) => {
        modal.setAttribute('aria-hidden', 'true');

        modal.querySelectorAll('[data-close-modal]').forEach((closer) => {
            closer.addEventListener('click', () => closeModal(modal));
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const open = document.querySelector('.modal.open');
            if (open) {
                closeModal(open);
            }
        }
    });

    document.querySelectorAll('[data-switch-modal]').forEach((switcher) => {
        switcher.addEventListener('click', (event) => {
            const target = event.currentTarget.getAttribute('data-switch-modal');
            if (!target) {
                return;
            }
            const current = event.currentTarget.closest('.modal');
            if (current) {
                closeModal(current);
            }
            openModal(target);
        });
    });

    const initialModal = body.getAttribute('data-initial-modal');
    if (initialModal) {
        openModal(initialModal);
    }
})();
</script>
</body>
</html>
