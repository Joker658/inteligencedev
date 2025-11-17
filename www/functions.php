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
 *     verification_code: string|null,
 *     email: string,
 *     username: string
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
        ];
    }

    if (!$result['success']) {
        return [
            'success' => false,
            'errors' => $result['errors'],
            'verification_code' => null,
            'email' => $email,
            'username' => $username,
        ];
    }

    regenerateCsrfToken();

    return [
        'success' => true,
        'errors' => [],
        'verification_code' => $result['verification_code'] ?? null,
        'email' => $result['email'] ?? $email,
        'username' => $result['username'] ?? $username,
    ];
}

/**
 * @return array{success: bool, errors: string[]}
 */
function verifyEmailAddress(string $email, string $code): array
{
    try {
        return getAuthService()->verifyEmail($email, $code);
    } catch (DatabaseConnectionException $exception) {
        return [
            'success' => false,
            'errors' => [$exception->getMessage()],
        ];
    }
}

/**
 * @return array{
 *     success: bool,
 *     errors: string[],
 *     verification_code: string|null,
 *     email: string,
 *     username: string
 * }
 */
function resendVerificationCode(string $email): array
{
    try {
        $result = getAuthService()->resendVerificationCode($email);
    } catch (DatabaseConnectionException $exception) {
        return [
            'success' => false,
            'errors' => [$exception->getMessage()],
        ];
    }

    if (!$result['success']) {
        return [
            'success' => false,
            'errors' => $result['errors'],
            'verification_code' => null,
            'email' => $email,
            'username' => $result['username'] ?? '',
        ];
    }

    return [
        'success' => true,
        'errors' => [],
        'verification_code' => $result['verification_code'] ?? null,
        'email' => $result['email'] ?? $email,
        'username' => $result['username'] ?? '',
    ];
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
