<?php
require_once __DIR__ . '/../functions.php';

ensureAuthenticated();

$user = getCurrentUser();

if (!$user) {
    header('Location: /includes/login.php');
    exit;
}

$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';
$isReglementPage = strpos($currentPath, '/Règlement/') !== false;

$createdAt = $user['created_at'] ?? null;
$createdAtFormatted = 'Non disponible';

if ($createdAt) {
    try {
        $createdAtFormatted = (new DateTimeImmutable($createdAt))->format('d/m/Y à H\hi');
    } catch (Exception $exception) {
        $createdAtFormatted = (string) $createdAt;
    }
}

$verificationStatus = !empty($user['email_verified_at'])
    ? 'Adresse e-mail vérifiée'
    : 'Vérification en attente';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IntelligenceDev</title>
    <link rel="icon" type="image/png" href="/img/Favicon.png">
    <link rel="stylesheet" href="/style.css">
</head>
<body>
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
                    <div class="user-menu" data-user-menu>
                        <button type="button" class="user-menu-toggle" aria-haspopup="true" aria-expanded="false">
                            <span class="user-menu-toggle-text">Bonjour</span>
                            <span class="user-menu-toggle-name"><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="user-menu-toggle-icon" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                        <div class="user-menu-panel" role="menu" aria-hidden="true">
                            <div class="user-menu-info">
                                <p class="user-menu-greeting">Bonjour :</p>
                                <p class="user-menu-name"><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php if (!empty($user['email'])): ?>
                                    <p class="user-menu-meta"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="user-menu-actions">
                                <button type="button" class="user-menu-action" disabled>Administration</button>
                                <a href="/Profil/" class="user-menu-action current" role="menuitem">Mon Profil</a>
                                <button type="button" class="user-menu-action" disabled>Paramètres</button>
                                <a href="/includes/logout.php" class="user-menu-action danger" role="menuitem">Déconnecter</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<main class="profile-page">
    <div class="container">
        <div class="profile-hero">
            <p class="profile-eyebrow">Espace sécurisé</p>
            <h1>Mon Profil</h1>
            <p class="profile-subtitle">Consultez vos informations personnelles et vérifiez que vos coordonnées sont à jour.</p>
        </div>

        <div class="profile-actions">
            <button type="button" class="button primary" data-profile-info-toggle aria-expanded="false" aria-controls="profile-info-panel">
                Informations
            </button>
            <p class="profile-action-hint">Cliquez pour afficher vos informations enregistrées.</p>
        </div>

        <section class="profile-card" id="profile-info-panel" hidden>
            <header class="profile-card-header">
                <div>
                    <p class="profile-eyebrow">Détails du compte</p>
                    <h2>Informations personnelles</h2>
                    <p class="profile-card-subtitle">Ces informations proviennent de votre inscription sur IntelligenceDev.</p>
                </div>
                <span class="profile-status<?= !empty($user['email_verified_at']) ? ' success' : ''; ?>">
                    <?= htmlspecialchars($verificationStatus, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </header>

            <dl class="profile-info-grid">
                <div class="profile-info-item">
                    <dt>Nom</dt>
                    <dd>Non renseigné</dd>
                </div>
                <div class="profile-info-item">
                    <dt>Prénom</dt>
                    <dd>Non renseigné</dd>
                </div>
                <div class="profile-info-item">
                    <dt>Nom d'utilisateur</dt>
                    <dd><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="profile-info-item">
                    <dt>Adresse e-mail</dt>
                    <dd><?= htmlspecialchars($user['email'] ?? 'Non renseignée', ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="profile-info-item">
                    <dt>Date de création du compte</dt>
                    <dd><?= htmlspecialchars($createdAtFormatted, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div class="profile-info-item">
                    <dt>Identifiant interne</dt>
                    <dd>#<?= htmlspecialchars((string) $user['id'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
            </dl>
        </section>
    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y'); ?> IntelligenceDev. Tous droits réservés.</p>
    </div>
</footer>

<script>
(function () {
    const menus = document.querySelectorAll('[data-user-menu]');

    if (!menus.length) {
        return;
    }

    const closeAll = () => {
        menus.forEach((menu) => {
            menu.classList.remove('open');
            const toggle = menu.querySelector('.user-menu-toggle');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
            const panel = menu.querySelector('.user-menu-panel');
            if (panel) {
                panel.setAttribute('aria-hidden', 'true');
            }
        });
    };

    menus.forEach((menu) => {
        const toggle = menu.querySelector('.user-menu-toggle');
        const panel = menu.querySelector('.user-menu-panel');

        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();

            const isOpen = menu.classList.toggle('open');
            if (isOpen) {
                menus.forEach((other) => {
                    if (other !== menu) {
                        other.classList.remove('open');
                        const otherToggle = other.querySelector('.user-menu-toggle');
                        const otherPanel = other.querySelector('.user-menu-panel');
                        if (otherToggle) {
                            otherToggle.setAttribute('aria-expanded', 'false');
                        }
                        if (otherPanel) {
                            otherPanel.setAttribute('aria-hidden', 'true');
                        }
                    }
                });
            }

            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        });

        panel.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    });

    document.addEventListener('click', () => {
        closeAll();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAll();
        }
    });
})();

(function () {
    const toggle = document.querySelector('[data-profile-info-toggle]');
    const panel = document.getElementById('profile-info-panel');

    if (!toggle || !panel) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isHidden = panel.hasAttribute('hidden');
        if (isHidden) {
            panel.removeAttribute('hidden');
        } else {
            panel.setAttribute('hidden', 'hidden');
        }
        toggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    });
})();
</script>
</body>
</html>