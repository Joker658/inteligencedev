<?php

declare(strict_types=1);
final class AuthService
{
    private PDO $pdo;
    private ?string $passwordColumn = null;
    private const VERIFICATION_TTL_MINUTES = 30;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array{
     *     success: bool,
     *     errors: string[],
     *     user_id: int|null,
     *     email: string,
     *     username: string,
     *     verification_code: string|null
     * }
     */
    public function register(string $username, string $email, string $password): array
    {
        $errors = [];
        $userId = null;
        $verificationCode = null;

        $username = trim($username);
        $email = trim($email);
        $length = static function (string $value): int {
            return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        };

        if ($username === '' || $length($username) < 3) {
            $errors[] = 'Le nom d\'utilisateur doit contenir au moins 3 caractères.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'adresse e-mail est invalide.';
        }

        if ($password === '' || $length($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }

        if ($errors) {
            return [
                'success' => false,
                'errors' => $errors,
                'user_id' => null,
                'email' => $email,
                'username' => $username,
                'verification_code' => null,
            ];
        }

        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare(
                'SELECT username, email FROM users WHERE username = :username OR email = :email LIMIT 1'
            );
            $statement->execute([
                'username' => $username,
                'email' => $email,
            ]);

            $existing = $statement->fetch();

            if ($existing) {
                if (strcasecmp($existing['username'], $username) === 0) {
                    $errors[] = 'Ce nom d\'utilisateur est déjà pris.';
                }

                if (strcasecmp($existing['email'], $email) === 0) {
                    $errors[] = 'Cette adresse e-mail est déjà utilisée.';
                }
            }

            if ($errors) {
                $this->pdo->rollBack();

                return [
                    'success' => false,
                    'errors' => $errors,
                    'user_id' => null,
                    'email' => $email,
                    'username' => $username,
                    'verification_code' => null,
                ];
            }

            $passwordColumn = $this->resolvePasswordColumn();
            $verificationCode = $this->generateVerificationCode();
            $verificationHash = password_hash($verificationCode, PASSWORD_DEFAULT);
            $expiresAt = (new \DateTimeImmutable(sprintf('+%d minutes', self::VERIFICATION_TTL_MINUTES)))->format('Y-m-d H:i:s');

            $insert = $this->pdo->prepare(
                sprintf(
                    'INSERT INTO users (username, email, %s, email_verification_code, verification_code_expires_at, email_verified_at) VALUES (:username, :email, :password, :verification_code, :expires_at, NULL)',
                    $passwordColumn
                )
            );
            $insert->execute([
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'verification_code' => $verificationHash,
                'expires_at' => $expiresAt,
            ]);

            $userId = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('User registration failed: ' . $exception->getMessage());
            $errors[] = 'Une erreur est survenue lors de la création du compte. Veuillez réessayer plus tard.';
            $userId = null;
            $verificationCode = null;
        }

        return [
            'success' => !$errors,
            'errors' => $errors,
            'user_id' => $userId,
            'email' => $email,
            'username' => $username,
            'verification_code' => $verificationCode,
        ];
    }

    /**
     * @return array{success: bool, errors: string[]}
     */
    public function authenticate(string $identifier, string $password): array
    {
        $identifier = trim($identifier);
        $password = (string) $password;

        if ($identifier === '' || $password === '') {
            return [
                'success' => false,
                'errors' => ['Veuillez saisir votre identifiant et votre mot de passe.'],
            ];
        }

        try {
            $passwordColumn = $this->resolvePasswordColumn();

            $statement = $this->pdo->prepare(
                sprintf(
                    'SELECT id, username, email, email_verified_at, email_verification_code, verification_code_expires_at, %s AS password_hash FROM users WHERE username = :username OR email = :email LIMIT 1',
                    $passwordColumn
                )
            );
            $statement->execute([
                'username' => $identifier,
                'email' => $identifier,
            ]);

            $user = $statement->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return [
                    'success' => false,
                    'errors' => ['Identifiants incorrects. Veuillez réessayer.'],
                ];
            }

            if ($user['email_verified_at'] === null) {
                return [
                    'success' => false,
                    'errors' => [
                        'Votre adresse e-mail n\'a pas encore été vérifiée. Saisissez le code reçu par e-mail sur la page de vérification.',
                    ],
                ];
            }

            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $update = $this->pdo->prepare(
                    sprintf('UPDATE users SET %s = :password WHERE id = :id', $passwordColumn)
                );
                $update->execute([
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'id' => $user['id'],
                ]);
            }
        } catch (PDOException $exception) {
            error_log('User authentication failed: ' . $exception->getMessage());

            return [
                'success' => false,
                'errors' => ['Une erreur interne est survenue. Veuillez réessayer ultérieurement.'],
            ];
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        return [
            'success' => true,
            'errors' => [],
        ];
    }

    public function getUserById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, username, email, email_verified_at, created_at FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $user = $statement->fetch();

        return $user ?: null;
    }

    /**
     * @return array{success: bool, errors: string[]}
     */
    public function verifyEmail(string $email, string $code): array
    {
        $email = trim($email);
        $code = trim($code);

        if ($email === '' || $code === '') {
            return [
                'success' => false,
                'errors' => ['Veuillez renseigner votre adresse e-mail et le code reçu.'],
            ];
        }

        try {
            $statement = $this->pdo->prepare(
                'SELECT id, email_verification_code, verification_code_expires_at, email_verified_at FROM users WHERE email = :email LIMIT 1'
            );
            $statement->execute(['email' => $email]);

            $user = $statement->fetch();

            if (!$user) {
                return [
                    'success' => false,
                    'errors' => ['Aucun compte ne correspond à cette adresse e-mail.'],
                ];
            }

            if ($user['email_verified_at'] !== null) {
                return [
                    'success' => false,
                    'errors' => ['Cette adresse e-mail est déjà vérifiée. Vous pouvez vous connecter.'],
                ];
            }

            if (!is_string($user['email_verification_code']) || $user['email_verification_code'] === '') {
                return [
                    'success' => false,
                    'errors' => ['Aucun code de vérification actif n\'a été trouvé. Veuillez demander un nouveau code.'],
                ];
            }

            if ($user['verification_code_expires_at']) {
                try {
                    $expiresAt = new \DateTimeImmutable($user['verification_code_expires_at']);
                } catch (\Exception $exception) {
                    $expiresAt = null;
                }

                if ($expiresAt instanceof \DateTimeImmutable && $expiresAt < new \DateTimeImmutable('now')) {
                    return [
                        'success' => false,
                        'errors' => ['Le code de vérification a expiré. Veuillez demander un nouveau code.'],
                    ];
                }
            }

            if (!password_verify($code, $user['email_verification_code'])) {
                return [
                    'success' => false,
                    'errors' => ['Le code saisi est incorrect.'],
                ];
            }

            $update = $this->pdo->prepare(
                'UPDATE users SET email_verified_at = CURRENT_TIMESTAMP, email_verification_code = NULL, verification_code_expires_at = NULL WHERE id = :id'
            );
            $update->execute(['id' => $user['id']]);
        } catch (PDOException $exception) {
            error_log('Email verification failed: ' . $exception->getMessage());

            return [
                'success' => false,
                'errors' => ['Une erreur est survenue lors de la vérification de l\'adresse e-mail. Veuillez réessayer ultérieurement.'],
            ];
        }

        return [
            'success' => true,
            'errors' => [],
        ];
    }

    /**
     * @return array{
     *     success: bool,
     *     errors: string[],
     *     user_id: int|null,
     *     email: string,
     *     username: string,
     *     verification_code: string|null
     * }
     */
    public function resendVerificationCode(string $email): array
    {
        $email = trim($email);

        if ($email === '') {
            return [
                'success' => false,
                'errors' => ['Veuillez renseigner votre adresse e-mail.'],
                'user_id' => null,
                'email' => $email,
                'username' => '',
                'verification_code' => null,
            ];
        }

        try {
            $statement = $this->pdo->prepare(
                'SELECT id, username, email_verified_at FROM users WHERE email = :email LIMIT 1'
            );
            $statement->execute(['email' => $email]);

            $user = $statement->fetch();

            if (!$user) {
                return [
                    'success' => false,
                    'errors' => ['Aucun compte ne correspond à cette adresse e-mail.'],
                    'user_id' => null,
                    'email' => $email,
                    'username' => '',
                    'verification_code' => null,
                ];
            }

            if ($user['email_verified_at'] !== null) {
                return [
                    'success' => false,
                    'errors' => ['Cette adresse e-mail est déjà vérifiée. Vous pouvez vous connecter.'],
                    'user_id' => null,
                    'email' => $email,
                    'username' => (string) ($user['username'] ?? ''),
                    'verification_code' => null,
                ];
            }

            $verificationCode = $this->generateVerificationCode();
            $verificationHash = password_hash($verificationCode, PASSWORD_DEFAULT);
            $expiresAt = (new \DateTimeImmutable(sprintf('+%d minutes', self::VERIFICATION_TTL_MINUTES)))->format('Y-m-d H:i:s');

            $update = $this->pdo->prepare(
                'UPDATE users SET email_verification_code = :code, verification_code_expires_at = :expires_at WHERE id = :id'
            );
            $update->execute([
                'code' => $verificationHash,
                'expires_at' => $expiresAt,
                'id' => $user['id'],
            ]);
        } catch (PDOException $exception) {
            error_log('Resend verification code failed: ' . $exception->getMessage());

            return [
                'success' => false,
                'errors' => ['Une erreur est survenue lors de la génération d\'un nouveau code. Veuillez réessayer ultérieurement.'],
                'user_id' => null,
                'email' => $email,
                'username' => '',
                'verification_code' => null,
            ];
        }

        return [
            'success' => true,
            'errors' => [],
            'user_id' => (int) $user['id'],
            'email' => $email,
            'username' => (string) ($user['username'] ?? ''),
            'verification_code' => $verificationCode,
        ];
    }

    public function deleteUserById(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
    }

    private function generateVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_regenerate_id(true);
        session_destroy();

        session_start();
        $_SESSION = [];
    }

    private function resolvePasswordColumn(): string
    {
        if ($this->passwordColumn !== null) {
            return $this->passwordColumn;
        }

        foreach (['password_hash', 'password'] as $column) {
            try {
                $this->pdo->query(sprintf('SELECT %s FROM users LIMIT 0', $column));
                $this->passwordColumn = $column;

                return $this->passwordColumn;
            } catch (PDOException $exception) {
                if ($this->isMissingColumnException($exception)) {
                    continue;
                }

                throw $exception;
            }
        }

        $this->passwordColumn = 'password_hash';

        return $this->passwordColumn;
    }

    private function isMissingColumnException(PDOException $exception): bool
    {
        $sqlState = $exception->getCode();

        if ($sqlState === '42S22') {
            return true;
        }

        $message = $exception->getMessage();

        return stripos($message, 'Unknown column') !== false
            || stripos($message, 'no such column') !== false;
    }
}
