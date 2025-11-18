<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/AuthService.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function isPostRequest(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function getAuthService(): AuthService
{
    static $service = null;

    if (!$service instanceof AuthService) {
        $service = new AuthService(getDatabaseConnection());
    }

    return $service;
}

function getCurrentUser(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    try {
        return getAuthService()->getUserById((int) $_SESSION['user_id']);
    } catch (DatabaseConnectionException $exception) {
        addGlobalError($exception->getMessage());

        return null;
    }
}

/**
 * @return array{success: bool, errors: string[]}
 */
function attemptLogin(string $identifier, string $password): array
{
    try {
        $result = getAuthService()->authenticate($identifier, $password);
    } catch (DatabaseConnectionException $exception) {
        return [
            'success' => false,
            'errors' => [$exception->getMessage()],
        ];
    }

    if ($result['success']) {
        regenerateCsrfToken();
    }

    return $result;
}

/**
 * @return array{
 *     success: bool,
 *     errors: string[],
 *     email: string|null,
 *     username: string|null,
 *     user_id: int|null,
 *     verification_code: string|null
 * }
 */
function registerUser(string $username, string $email, string $password): array
{
    try {
        $result = getAuthService()->register($username, $email, $password);
    } catch (DatabaseConnectionException $exception) {
        return [
            'success' => false,
            'errors' => [$exception->getMessage()],
            'email' => $email,
            'username' => $username,
            'user_id' => null,
            'verification_code' => null,
        ];
    }

    if (!$result['success']) {
        return [
            'success' => false,
            'errors' => $result['errors'],
            'email' => $result['email'],
            'username' => $result['username'],
            'user_id' => $result['user_id'] ?? null,
            'verification_code' => $result['verification_code'] ?? null,
        ];
    }

    regenerateCsrfToken();

    return [
        'success' => true,
        'errors' => [],
        'email' => $result['email'],
        'username' => $result['username'],
        'user_id' => $result['user_id'],
        'verification_code' => $result['verification_code'] ?? null,
    ];
}

/**
 * @return array{success: bool, errors: string[]}
 */
function verifyEmailCode(int $userId, string $code): array
{
    try {
        return getAuthService()->verifyEmail($userId, $code);
    } catch (DatabaseConnectionException $exception) {
        return [
            'success' => false,
            'errors' => [$exception->getMessage()],
        ];
    }
}

function logoutUser(): void
{
    try {
        getAuthService()->logout();
        regenerateCsrfToken();
    } catch (DatabaseConnectionException $exception) {
        forceLogoutSession();
        regenerateCsrfToken();
        addGlobalError($exception->getMessage());
    }
}

function forceLogoutSession(): void
{
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

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION = [];
}

function ensureAuthenticated(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /includes/login.php');
        exit;
    }
}

function getCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function regenerateCsrfToken(): string
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($sessionToken) || $sessionToken === '' || !is_string($token) || $token === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

function addGlobalError(string $message): void
{
    if (!isset($_SESSION['global_errors']) || !is_array($_SESSION['global_errors'])) {
        $_SESSION['global_errors'] = [];
    }

    if (!in_array($message, $_SESSION['global_errors'], true)) {
        $_SESSION['global_errors'][] = $message;
    }
}

function consumeGlobalErrors(): array
{
    $errors = [];

    if (isset($_SESSION['global_errors']) && is_array($_SESSION['global_errors'])) {
        $errors = $_SESSION['global_errors'];
    }

    $_SESSION['global_errors'] = [];

    return $errors;
}

function setPendingVerification(array $data): void
{
    $_SESSION['pending_verification'] = [
        'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : null,
        'email' => isset($data['email']) ? (string) $data['email'] : '',
        'code' => isset($data['code']) ? (string) $data['code'] : '',
    ];
}

function getPendingVerification(): ?array
{
    if (!isset($_SESSION['pending_verification']) || !is_array($_SESSION['pending_verification'])) {
        return null;
    }

    $pending = $_SESSION['pending_verification'];

    if (!isset($pending['user_id']) || $pending['user_id'] === null) {
        return null;
    }

    return [
        'user_id' => (int) $pending['user_id'],
        'email' => isset($pending['email']) ? (string) $pending['email'] : '',
        'code' => isset($pending['code']) ? (string) $pending['code'] : '',
    ];
}

function clearPendingVerification(): void
{
    unset($_SESSION['pending_verification']);
}
