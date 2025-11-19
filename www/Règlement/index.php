<?php
require_once __DIR__ . '/../functions.php';

$user = getCurrentUser();
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
            <a href="/index.php" class="nav-link">Accueil</a>
            <a href="/Règlement/index.php" class="nav-link active">Règlement</a>
            <div class="nav-actions">
                <?php if ($user): ?>
                    <span class="welcome">Bonjour, <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?> !</span>
                    <a href="/includes/logout.php" class="button secondary">Déconnexion</a>
                <?php else: ?>
                    <a href="/index.php#login-modal" class="button secondary">Connexion</a>
                    <a href="/index.php#register-modal" class="button primary">Créer un compte</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<main class="reglement-page">
    <section class="hero compact">
        <div class="container">
            <h1>Règlement général d'IntelligenceDev</h1>
            <p>Ce règlement s'applique à l'ensemble de nos services : site web, boutique, infrastructure Discord et tout support lié au développement.</p>
        </div>
    </section>

    <section class="reglement-section">
        <div class="container">
            <article class="reglement-block">
                <h2>1. Principes généraux</h2>
                <ul>
                    <li>Respect mutuel et professionnalisme sont attendus dans toutes les interactions, publiques comme privées.</li>
                    <li>Tout contenu partagé doit respecter la législation en vigueur et la propriété intellectuelle.</li>
                    <li>Les comptes personnels sont strictement individuels : ne partagez pas vos accès ou licences.</li>
                </ul>
            </article>

            <article class="reglement-block">
                <h2>2. Utilisation des ressources de développement</h2>
                <ul>
                    <li>Les scripts, API et documentations fournis sont réservés à un usage licite et conforme aux licences associées.</li>
                    <li>Il est interdit de redistribuer, revendre ou décompiler nos solutions sans accord écrit.</li>
                    <li>Signalez toute faille de sécurité via nos canaux officiels avant toute divulgation publique.</li>
                </ul>
            </article>

            <article class="reglement-block">
                <h2>3. Communications & Discord</h2>
                <ul>
                    <li>Nos salons Discord suivent les mêmes règles que le site : pas de spam, d'insultes ni de publicité non sollicitée.</li>
                    <li>Les échanges techniques doivent rester centrés sur le développement et l'amélioration des projets IntelligenceDev.</li>
                    <li>Les décisions des modérateurs Discord et du support sont applicables sur l'ensemble de nos plateformes.</li>
                </ul>
            </article>

            <article class="reglement-block">
                <h2>4. Support et commandes</h2>
                <ul>
                    <li>Les demandes de support se font via le panel client ou le ticket Discord officiel.</li>
                    <li>Fournissez des informations complètes pour accélérer l'analyse de vos incidents ou demandes de développement personnalisé.</li>
                    <li>Tout paiement lancé vaut acceptation expresse des présentes conditions.</li>
                </ul>
                <p class="refund-notice"><strong>Politique de remboursement&nbsp;: nous remboursons sous un délai maximum de 10 jours.</strong></p>
                <p>En cas de litige, une preuve d'achat et la description du problème devront être fournies pour initier la procédure.</p>
            </article>

            <article class="reglement-block">
                <h2>5. Sécurité & confidentialité</h2>
                <ul>
                    <li>Ne tentez pas d'exploiter nos infrastructures : toute intrusion entraînera une suspension définitive et des poursuites.</li>
                    <li>Les données collectées sont utilisées uniquement pour la gestion des comptes et des commandes.</li>
                    <li>Vous êtes responsables des intégrations tierces connectées à votre compte.</li>
                </ul>
            </article>

            <article class="reglement-block">
                <h2>6. Sanctions</h2>
                <ul>
                    <li>Nous nous réservons le droit de suspendre ou de résilier un accès en cas de non-respect du règlement.</li>
                    <li>Les abus répétés peuvent aboutir à des poursuites civiles et pénales selon la gravité des faits.</li>
                </ul>
            </article>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y'); ?> IntelligenceDev. Tous droits réservés.</p>
    </div>
</footer>
</body>
</html>
