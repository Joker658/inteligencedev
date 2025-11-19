<?php
require_once __DIR__ . '/../functions.php';

$user = getCurrentUser();
$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';
$isReglementPage = strpos($currentPath, '/Règlement/') !== false;
$isAbonnementPage = strpos($currentPath, '/Abonnement/') !== false;
$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnement | IntelligenceDev</title>
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
            <a href="/Abonnement/index.php" class="nav-link<?= $isAbonnementPage ? ' active' : ''; ?>">Abonnement</a>
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
                                <a href="/Profil/index.php" class="user-menu-action<?= strpos($currentPath, '/Profil/') !== false ? ' current' : ''; ?>" role="menuitem">Mon Profil</a>
                                <button type="button" class="user-menu-action" disabled>Paramètres</button>
                                <a href="/includes/logout.php" class="user-menu-action danger" role="menuitem">Déconnecter</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <button type="button" class="button secondary" data-modal-target="login-modal">Connexion</button>
                    <button type="button" class="button primary" data-modal-target="register-modal">Créer un compte</button>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<main class="subscriptions-page">
    <section class="hero compact subscriptions-hero">
        <div class="container">
            <p class="hero-kicker">Offres exclusives</p>
            <h1>Choisissez l'abonnement adapté à vos projets</h1>
            <p>Accédez à des scripts premium, des mises à jour prioritaires et une assistance dédiée pour accélérer vos développements.</p>
            <div class="hero-meta">
                <div class="pill">Accès immédiat</div>
                <div class="pill pill-soft">Support prioritaire</div>
                <div class="pill">Mises à jour incluses</div>
            </div>
        </div>
    </section>

    <section class="subscriptions-grid" aria-labelledby="plans-title">
        <div class="container">
            <div class="section-header">
                <p class="section-kicker">Comparatif</p>
                <div>
                    <h2 id="plans-title">3 niveaux d'abonnement</h2>
                    <p class="section-subtitle">Sélectionnez le plan qui correspond le mieux à vos besoins et cliquez sur « Informations » pour découvrir le détail complet.</p>
                </div>
            </div>

            <div class="plan-grid">
                <article class="plan-card">
                    <div class="plan-header">
                        <p class="plan-badge">Essentiel</p>
                        <h3>Starter</h3>
                        <p class="plan-description">Pour débuter avec nos scripts et tester nos intégrations.</p>
                    </div>
                    <p class="plan-price"><span class="plan-amount">12€</span> / mois</p>
                    <ul class="plan-features">
                        <li>Accès à 15 scripts optimisés</li>
                        <li>Mises à jour mensuelles</li>
                        <li>Support communautaire</li>
                    </ul>
                    <div class="plan-actions">
                        <button type="button" class="button primary full">Ajouter au panier</button>
                        <button type="button" class="button secondary full" data-modal-target="plan-starter-modal">Informations</button>
                    </div>
                </article>

                <article class="plan-card highlighted">
                    <div class="plan-header">
                        <p class="plan-badge accent">Populaire</p>
                        <h3>Pro</h3>
                        <p class="plan-description">Le meilleur rapport qualité/prix pour les équipes actives.</p>
                    </div>
                    <p class="plan-price"><span class="plan-amount">24€</span> / mois</p>
                    <ul class="plan-features">
                        <li>Tous les scripts actuels</li>
                        <li>Mises à jour hebdomadaires</li>
                        <li>Support prioritaire 24/7</li>
                        <li>Intégrations Discord avancées</li>
                    </ul>
                    <div class="plan-actions">
                        <button type="button" class="button primary full">Ajouter au panier</button>
                        <button type="button" class="button secondary full" data-modal-target="plan-pro-modal">Informations</button>
                    </div>
                </article>

                <article class="plan-card">
                    <div class="plan-header">
                        <p class="plan-badge">Premium</p>
                        <h3>Entreprise</h3>
                        <p class="plan-description">Pour les organisations qui veulent un accompagnement complet.</p>
                    </div>
                    <p class="plan-price"><span class="plan-amount">55€</span> / mois</p>
                    <ul class="plan-features">
                        <li>Scripts illimités & bêta privée</li>
                        <li>Gestionnaire de compte dédié</li>
                        <li>Ateliers techniques mensuels</li>
                        <li>SLA renforcé et supervision</li>
                    </ul>
                    <div class="plan-actions">
                        <button type="button" class="button primary full">Ajouter au panier</button>
                        <button type="button" class="button secondary full" data-modal-target="plan-enterprise-modal">Informations</button>
                    </div>
                </article>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y'); ?> IntelligenceDev. Tous droits réservés.</p>
    </div>
</footer>

<div class="modal" id="plan-starter-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-content">
        <button type="button" class="modal-close" aria-label="Fermer" data-close-modal>&times;</button>
        <p class="modal-eyebrow">Plan Starter</p>
        <h2>Commencez en douceur</h2>
        <p class="modal-subtitle">Idéal pour tester nos scripts, prototyper vos idées et prendre en main notre écosystème.</p>
        <div class="modal-body">
            <ul class="modal-list">
                <li>Accès instantané aux 15 scripts essentiels.</li>
                <li>Mises à jour mensuelles avec alertes e-mail.</li>
                <li>Accès à la documentation détaillée et aux guides vidéo.</li>
            </ul>
            <p class="modal-note">Découvrez un tutoriel rapide : <a href="https://youtu.be/dQw4w9WgXcQ" target="_blank" rel="noopener">voir la vidéo de présentation</a>.</p>
        </div>
        <div class="modal-actions">
            <button type="button" class="button primary full">Ajouter au panier</button>
        </div>
    </div>
</div>

<div class="modal" id="plan-pro-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-content">
        <button type="button" class="modal-close" aria-label="Fermer" data-close-modal>&times;</button>
        <p class="modal-eyebrow">Plan Pro</p>
        <h2>Le choix des équipes actives</h2>
        <p class="modal-subtitle">Pensé pour les développeurs qui veulent un flux continu de nouveautés et un support réactif.</p>
        <div class="modal-body">
            <ul class="modal-list">
                <li>Accès complet au catalogue et aux futures sorties.</li>
                <li>Hotfix prioritaires et canal de support dédié.</li>
                <li>Connecteurs avancés pour Discord et API partenaires.</li>
            </ul>
            <p class="modal-note">Présentation complète en vidéo : <a href="https://youtu.be/6_b7RDuLwcI" target="_blank" rel="noopener">voir la démonstration Pro</a>.</p>
        </div>
        <div class="modal-actions">
            <button type="button" class="button primary full">Ajouter au panier</button>
        </div>
    </div>
</div>

<div class="modal" id="plan-enterprise-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-content">
        <button type="button" class="modal-close" aria-label="Fermer" data-close-modal>&times;</button>
        <p class="modal-eyebrow">Plan Entreprise</p>
        <h2>Accompagnement sur mesure</h2>
        <p class="modal-subtitle">Bénéficiez d'un suivi dédié, d'ateliers privés et d'un SLA adapté à vos enjeux critiques.</p>
        <div class="modal-body">
            <ul class="modal-list">
                <li>Accès anticipé aux versions bêta et environnements de staging.</li>
                <li>Sessions techniques mensuelles avec nos experts.</li>
                <li>Supervision, monitoring et assistance renforcée.</li>
            </ul>
            <p class="modal-note">Explorez le tour complet : <a href="https://youtu.be/V-_O7nl0Ii0" target="_blank" rel="noopener">voir la visite Entreprise</a>.</p>
        </div>
        <div class="modal-actions">
            <button type="button" class="button primary full">Ajouter au panier</button>
        </div>
    </div>
</div>

<div class="modal" id="login-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-content">
        <button type="button" class="modal-close" aria-label="Fermer" data-close-modal>&times;</button>
        <p class="modal-subtitle">Connectez-vous pour poursuivre vos achats et accéder à vos scripts.</p>
        <form class="form" method="post" action="/index.php">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="login-identifier">E-mail ou nom d'utilisateur</label>
            <input type="text" id="login-identifier" name="identifier" required>
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
        <p class="modal-subtitle">Créez un compte pour gérer vos abonnements et vos téléchargements.</p>
        <form class="form" method="post" action="/index.php">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="register-username">Nom d'utilisateur</label>
            <input type="text" id="register-username" name="username" required>
            <label for="register-email">Adresse e-mail</label>
            <input type="email" id="register-email" name="email" required>
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

    document.querySelectorAll('[data-modal-target]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            const target = event.currentTarget.getAttribute('data-modal-target');
            if (target) {
                openModal(target);
            }
        });
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
})();

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

            toggle.setAttribute('aria-expanded', String(isOpen));
            panel.setAttribute('aria-hidden', String(!isOpen));
        });
    });

    document.addEventListener('click', (event) => {
        if (![...menus].some((menu) => menu.contains(event.target))) {
            closeAll();
        }
    });
})();
</script>
</body>
</html>
