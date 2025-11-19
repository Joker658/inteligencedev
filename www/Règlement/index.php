<?php
require_once __DIR__ . '/../functions.php';

$user = getCurrentUser();
$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';
$isReglementPage = strpos($currentPath, '/Règlement/') !== false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Règlement | IntelligenceDev</title>
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
            <a href="/Abonnement/index.php" class="nav-link<?= strpos($currentPath, '/Abonnement/') !== false ? ' active' : ''; ?>">Abonnement</a>
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

<main class="reglement-page">
    <section class="hero compact reglement-hero">
        <div class="container">
            <p class="hero-kicker">Documentation officielle · IntelligenceDev</p>
            <h1>Règlement général d'IntelligenceDev</h1>
            <p>Ce cadre commun s'applique à l'ensemble de nos services : site web, boutique, infrastructures Discord et tout support lié au développement.</p>

            <div class="hero-meta">
                <div class="pill">Dernière mise à jour · <?= date('d/m/Y'); ?></div>
                <div class="pill pill-soft">Version 2.0</div>
                <div class="pill pill-soft">Applicable partout</div>
            </div>

            <div class="reglement-pills" aria-label="Navigation rapide">
                <a href="#principes" class="pill-link">Principes</a>
                <a href="#ressources" class="pill-link">Ressources</a>
                <a href="#communications" class="pill-link">Communications</a>
                <a href="#support" class="pill-link">Support</a>
                <a href="#securite" class="pill-link">Sécurité</a>
                <a href="#sanctions" class="pill-link">Sanctions</a>
            </div>
        </div>
    </section>

    <section class="reglement-highlights">
        <div class="container">
            <div class="highlight-grid">
                <div class="highlight-card">
                    <span class="highlight-icon" aria-hidden="true">🤝</span>
                    <p class="highlight-label">Priorité</p>
                    <p class="highlight-value">Respect & confiance</p>
                    <p class="highlight-desc">La collaboration saine est la base de nos communautés publiques et privées.</p>
                </div>
                <div class="highlight-card">
                    <span class="highlight-icon" aria-hidden="true">🛡️</span>
                    <p class="highlight-label">Sécurité</p>
                    <p class="highlight-value">Signalement responsable</p>
                    <p class="highlight-desc">Toute faille est traitée en priorité via nos canaux sécurisés.</p>
                </div>
                <div class="highlight-card">
                    <span class="highlight-icon" aria-hidden="true">⚖️</span>
                    <p class="highlight-label">Cadre légal</p>
                    <p class="highlight-value">Licences respectées</p>
                    <p class="highlight-desc">Nos outils et API restent protégés par leurs licences d'utilisation.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="reglement-section">
        <div class="container">
            <article class="reglement-block" id="principes">
                <div class="block-header">
                    <span class="block-index">01</span>
                    <div>
                        <p class="block-kicker">Cadre humain</p>
                        <h2>Principes généraux</h2>
                    </div>
                </div>
                <ul>
                    <li>Respect mutuel et professionnalisme sont attendus dans toutes les interactions, publiques comme privées.</li>
                    <li>Tout contenu partagé doit respecter la législation en vigueur et la propriété intellectuelle.</li>
                    <li>Les comptes personnels sont strictement individuels : ne partagez pas vos accès ou licences.</li>
                </ul>
            </article>

            <article class="reglement-block" id="ressources">
                <div class="block-header">
                    <span class="block-index">02</span>
                    <div>
                        <p class="block-kicker">Outils & API</p>
                        <h2>Utilisation des ressources de développement</h2>
                    </div>
                </div>
                <ul>
                    <li>Les scripts, API et documentations fournis sont réservés à un usage licite et conforme aux licences associées.</li>
                    <li>Il est interdit de redistribuer, revendre ou décompiler nos solutions sans accord écrit.</li>
                    <li>Signalez toute faille de sécurité via nos canaux officiels avant toute divulgation publique.</li>
                </ul>
            </article>

            <article class="reglement-block" id="communications">
                <div class="block-header">
                    <span class="block-index">03</span>
                    <div>
                        <p class="block-kicker">Communautés</p>
                        <h2>Communications & Discord</h2>
                    </div>
                </div>
                <ul>
                    <li>Nos salons Discord suivent les mêmes règles que le site : pas de spam, d'insultes ni de publicité non sollicitée.</li>
                    <li>Les échanges techniques doivent rester centrés sur le développement et l'amélioration des projets IntelligenceDev.</li>
                    <li>Les décisions des modérateurs Discord et du support sont applicables sur l'ensemble de nos plateformes.</li>
                </ul>
            </article>

            <article class="reglement-block" id="support">
                <div class="block-header">
                    <span class="block-index">04</span>
                    <div>
                        <p class="block-kicker">Relation client</p>
                        <h2>Support et commandes</h2>
                    </div>
                </div>
                <ul>
                    <li>Les demandes de support se font via le panel client ou le ticket Discord officiel.</li>
                    <li>Fournissez des informations complètes pour accélérer l'analyse de vos incidents ou demandes de développement personnalisé.</li>
                    <li>Tout paiement lancé vaut acceptation expresse des présentes conditions.</li>
                </ul>
                <p class="refund-notice"><strong> Politique de remboursement&nbsp;: nous remboursons sous un délai maximum de 10 jours et uniquement pour les scripts non open source.</strong></p>
                <p> En cas de litige, une preuve d'achat et la description du problème devront être fournies pour initier la procédure.</p>
            </article>

            <article class="reglement-block" id="securite">
                <div class="block-header">
                    <span class="block-index">05</span>
                    <div>
                        <p class="block-kicker">Protection</p>
                        <h2>Sécurité & confidentialité</h2>
                    </div>
                </div>
                <ul>
                    <li>Ne tentez pas d'exploiter nos infrastructures : toute intrusion entraînera une suspension définitive et des poursuites.</li>
                    <li>Les données collectées sont utilisées uniquement pour la gestion des comptes et des commandes.</li>
                    <li>Vous êtes responsables des intégrations tierces connectées à votre compte.</li>
                </ul>
            </article>

            <article class="reglement-block" id="sanctions">
                <div class="block-header">
                    <span class="block-index">06</span>
                    <div>
                        <p class="block-kicker">Application</p>
                        <h2>Sanctions</h2>
                    </div>
                </div>
                <ul>
                    <li>Nous nous réservons le droit de suspendre ou de résilier un accès en cas de non-respect du règlement.</li>
                    <li>Les abus répétés peuvent aboutir à des poursuites civiles et pénales selon la gravité des faits.</li>
                </ul>
            </article>
        </div>
    </section>

    <section class="reglement-cta">
        <div class="container">
            <div class="cta-card">
                <div>
                    <p class="cta-kicker">Besoin d'un éclaircissement ?</p>
                    <h2>Notre équipe reste disponible pour toute question juridique ou technique.</h2>
                    <p>Ouvrez un ticket via le panel client ou écrivez-nous sur Discord pour échanger avec un conseiller.</p>
                </div>
                <a class="button primary" href="/index.php#support">Contacter le support</a>
            </div>
        </div>
    </section>
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
</script>
</body>
</html>
