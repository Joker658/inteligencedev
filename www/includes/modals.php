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
