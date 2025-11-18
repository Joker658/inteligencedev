<?php
require_once __DIR__ . '/../functions.php';

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
                header('Location: /Règlement/reglement.php');
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IntelligenceDev - Règlement</title>
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
            <a href="/index.php" class="nav-link">Accueil</a>
            <a href="/Règlement/reglement.php" class="nav-link active">Règlement</a>
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

    <section class="hero regulation-hero">
        <div class="container">
            <p class="breadcrumb"><a href="/index.php">Accueil</a> / Règlement</p>
            <h1>Règlement officiel de la communauté IntelligenceDev</h1>
            <p>Cette page détaille l'ensemble des règles à respecter sur nos plateformes : site, boutique, outils collaboratifs et serveur Discord. Chaque membre s'engage à respecter ces lignes directrices pour garantir une expérience saine et sécurisée.</p>
            <div class="hero-actions">
                <a class="button primary" href="#principes">Découvrir les principes</a>
                <a class="button secondary" href="mailto:contact@intelligencedev.fr">Contacter la modération</a>
            </div>
        </div>
    </section>

    <section class="regulation" id="principes">
        <div class="container">
            <h2>Principes fondamentaux</h2>
            <div class="regulation-grid">
                <article class="regulation-card">
                    <h3>Respect & bienveillance</h3>
                    <ul>
                        <li>Aucun propos insultant, discriminatoire ou diffamatoire n'est toléré.</li>
                        <li>Les échanges privés doivent également respecter notre charte.</li>
                        <li>La modération se réserve le droit de supprimer tout contenu jugé nuisible.</li>
                    </ul>
                </article>
                <article class="regulation-card">
                    <h3>Utilisation des scripts</h3>
                    <ul>
                        <li>Les scripts téléchargés restent soumis aux licences précisées dans leur fiche.</li>
                        <li>Il est interdit de revendre ou redistribuer nos produits sans accord écrit.</li>
                        <li>Toute modification doit être documentée pour assurer la traçabilité.</li>
                    </ul>
                </article>
                <article class="regulation-card">
                    <h3>Sécurité & données</h3>
                    <ul>
                        <li>Ne partagez jamais de mots de passe, tokens ou accès client.</li>
                        <li>Signalez immédiatement toute faille ou comportement suspect.</li>
                        <li>Respectez le RGPD et chiffrez les données sensibles en transit comme au repos.</li>
                    </ul>
                </article>
                <article class="regulation-card">
                    <h3>Support technique</h3>
                    <ul>
                        <li>Ouvrez un ticket détaillé avec logs, captures et étapes pour faciliter l'assistance.</li>
                        <li>Les tickets urgents doivent être justifiés par un impact en production.</li>
                        <li>Un suivi régulier est obligatoire jusqu'à la résolution complète.</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <section class="regulation regulation-extended" id="procedures">
        <div class="container">
            <h2>Procédures de modération</h2>
            <div class="regulation-grid">
                <article class="regulation-card">
                    <h3>Signalements</h3>
                    <p>Tout membre peut signaler un abus via le support ou en contactant directement un modérateur. Chaque signalement est enregistré et traité sous 24 heures.</p>
                </article>
                <article class="regulation-card">
                    <h3>Sanctions progressives</h3>
                    <ul>
                        <li>Avertissement écrit</li>
                        <li>Restriction temporaire d'accès</li>
                        <li>Suspension définitive en cas de récidive ou de faute grave</li>
                    </ul>
                </article>
                <article class="regulation-card">
                    <h3>Recours possibles</h3>
                    <p>Une révision peut être demandée dans les 7 jours suivant une sanction. Fournissez des éléments précis (captures, témoins, logs) pour accélérer le traitement.</p>
                </article>
            </div>
            <div class="regulation-extra">
                <p>Nos équipes appliquent ces procédures de manière équitable afin de protéger l'ensemble de la communauté et de préserver un environnement professionnel.</p>
                <p class="refund-highlight"><strong>NOUS REMBOURSONS SOUS UN DÉLAI MAXIMUM DE 10 JOURS À COMPTER DE LA VALIDATION DE LA DEMANDE.</strong></p>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y'); ?> IntelligenceDev. Tous droits réservés.</p>
    </div>
</footer>

<?php include __DIR__ . '/../includes/modals.php'; ?>
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
