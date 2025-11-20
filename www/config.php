<?php
declare(strict_types=1);

// -----------------------------------------------------------------------------
// Configuration de la base de données
// -----------------------------------------------------------------------------
// Les valeurs peuvent être surchargées avec les variables d'environnement
// INTELLIGENCEDEV_DB_* ou DB_*. Cela permet de déployer l'application sans
// modifier ce fichier (ex. dans Docker ou sur un hébergement mutualisé).
// -----------------------------------------------------------------------------

const DB_DEFAULT_DRIVER = 'sqlite';
const DB_DEFAULT_HOST = '86.196.245.7';
const DB_DEFAULT_PORT = 3306;
const DB_DEFAULT_NAME = 's186_intelligencedev';
const DB_DEFAULT_USER = 'u186_4LEQr9mRKo';
const DB_DEFAULT_PASSWORD = 'w@I.3YGMTxDwmL8d9T0ca86X';
const DB_DEFAULT_CHARSET = 'utf8mb4';
const SQLITE_DEFAULT_PATH = __DIR__ . '/data/database.sqlite';

/**
 * Retourne une instance PDO connectée à la base de données MySQL configurée.
 */
class DatabaseConnectionException extends RuntimeException
{
}

function getDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $customDsn = env('INTELLIGENCEDEV_DB_DSN', env('DB_DSN'));
    $driver = env('INTELLIGENCEDEV_DB_DRIVER', env('DB_DRIVER', DB_DEFAULT_DRIVER));

    if ($customDsn) {
        [$dsn, $username, $password] = [$customDsn, env('INTELLIGENCEDEV_DB_USER', env('DB_USER')), env('INTELLIGENCEDEV_DB_PASS', env('DB_PASS'))];
    } elseif (strtolower((string) $driver) === 'sqlite') {
        $sqlitePath = env('INTELLIGENCEDEV_DB_SQLITE_PATH', env('DB_SQLITE_PATH', SQLITE_DEFAULT_PATH));
        $dsn = sprintf('sqlite:%s', ensureSqliteDatabaseFile($sqlitePath));
        $username = null;
        $password = null;
    } else {
        $host = env('INTELLIGENCEDEV_DB_HOST', env('DB_HOST', DB_DEFAULT_HOST));
        $port = (int) env('INTELLIGENCEDEV_DB_PORT', env('DB_PORT', (string) DB_DEFAULT_PORT));
        $dbname = env('INTELLIGENCEDEV_DB_NAME', env('DB_NAME', DB_DEFAULT_NAME));
        $username = env('INTELLIGENCEDEV_DB_USER', env('DB_USER', DB_DEFAULT_USER));
        $password = env('INTELLIGENCEDEV_DB_PASS', env('DB_PASS', DB_DEFAULT_PASSWORD));
        $charset = env('INTELLIGENCEDEV_DB_CHARSET', env('DB_CHARSET', DB_DEFAULT_CHARSET));

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $dbname,
            $charset
        );
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $exception) {
        handleDatabaseConnectionFailure($exception, $dsn, (string) $username);
    }

    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    try {
        initializeDatabase($pdo);
    } catch (PDOException $exception) {
        error_log(sprintf(
            'Database initialization failed for DSN "%s": %s',
            $dsn,
            $exception->getMessage()
        ));

        throw new DatabaseConnectionException(
            'Le service de connexion est momentanément indisponible. Veuillez réessayer ultérieurement.',
            0,
            $exception
        );
    } catch (Throwable $exception) {
        error_log(sprintf(
            'Unexpected error during database initialization for DSN "%s": %s',
            $dsn,
            $exception->getMessage()
        ));

        throw new DatabaseConnectionException(
            'Le service de connexion est momentanément indisponible. Veuillez réessayer ultérieurement.',
            0,
            $exception
        );
    }

    return $pdo;
}

/**
 * Récupère une variable d'environnement ou renvoie une valeur par défaut.
 */
function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

/**
 * Gestion centralisée des erreurs de connexion à la base de données.
 */
function handleDatabaseConnectionFailure(PDOException $exception, string $dsn, string $username): void
{
    error_log(sprintf(
        'Database connection failed for DSN "%s" with user "%s": %s',
        $dsn,
        $username,
        $exception->getMessage()
    ));

    throw new DatabaseConnectionException(
        'Le service de connexion est momentanément indisponible. Veuillez réessayer ultérieurement.',
        0,
        $exception
    );
}

function ensureSqliteDatabaseFile(string $path): string
{
    $directory = dirname($path);

    if (!is_dir($directory)) {
        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create SQLite directory "%s"', $directory));
        }
    }

    if (!file_exists($path)) {
        touch($path);
    }

    return $path;
}

/**
 * Création automatique de la table des utilisateurs lorsque nécessaire.
 */
function initializeDatabase(PDO $pdo): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        initializeSqliteDatabase($pdo);

        return;
    }

    initializeMysqlDatabase($pdo);
}

function initializeMysqlDatabase(PDO $pdo): void
{
    if (!databaseTableExists($pdo, 'users')) {
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS users (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    email_verification_code VARCHAR(255) DEFAULT NULL,
                    verification_code_expires_at DATETIME DEFAULT NULL,
                    email_verified_at DATETIME DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (PDOException $exception) {
            if (!databaseTableExists($pdo, 'users') || !isPrivilegeError($exception)) {
                throw $exception;
            }

            error_log(sprintf('Unable to create users table automatically: %s', $exception->getMessage()));
        }
    }

    ensureUserColumn($pdo, 'email_verification_code', 'VARCHAR(255) DEFAULT NULL');
    ensureUserColumn($pdo, 'verification_code_expires_at', 'DATETIME DEFAULT NULL');
    ensureUserColumn($pdo, 'email_verified_at', 'DATETIME DEFAULT NULL');

    if (!databaseTableExists($pdo, 'site_metrics')) {
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS site_metrics (
                    metric_key VARCHAR(100) NOT NULL PRIMARY KEY,
                    metric_value VARCHAR(255) NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } catch (PDOException $exception) {
            if (!databaseTableExists($pdo, 'site_metrics') || !isPrivilegeError($exception)) {
                throw $exception;
            }

            error_log(sprintf('Unable to create site_metrics table automatically: %s', $exception->getMessage()));
        }
    }

    $sql = <<<'SQL'
UPDATE users
SET email_verified_at = COALESCE(email_verified_at, created_at)
WHERE email_verified_at IS NULL
  AND (email_verification_code IS NULL OR email_verification_code = '')
SQL;

    $pdo->exec($sql);
}

function initializeSqliteDatabase(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            email_verification_code VARCHAR(255) DEFAULT NULL,
            verification_code_expires_at DATETIME DEFAULT NULL,
            email_verified_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS site_metrics (
            metric_key VARCHAR(100) NOT NULL PRIMARY KEY,
            metric_value VARCHAR(255) NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )'
    );

    ensureUserColumn($pdo, 'email_verification_code', 'VARCHAR(255) DEFAULT NULL');
    ensureUserColumn($pdo, 'verification_code_expires_at', 'DATETIME DEFAULT NULL');
    ensureUserColumn($pdo, 'email_verified_at', 'DATETIME DEFAULT NULL');

    $pdo->exec(
        'UPDATE users
        SET email_verified_at = COALESCE(email_verified_at, created_at)
        WHERE email_verified_at IS NULL
          AND (email_verification_code IS NULL OR email_verification_code = "")'
    );
}

function ensureUserColumn(PDO $pdo, string $column, string $definition): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        ensureSqliteUserColumn($pdo, $column, $definition);

        return;
    }

    $statement = $pdo->prepare('SHOW COLUMNS FROM `users` LIKE :column');
    $statement->execute(['column' => $column]);

    if ($statement->fetch() === false) {
        try {
            $pdo->exec(sprintf('ALTER TABLE `users` ADD COLUMN `%s` %s', $column, $definition));
        } catch (PDOException $exception) {
            if (!isPrivilegeError($exception)) {
                throw $exception;
            }

            error_log(sprintf(
                'Unable to add missing column `%s` automatically: %s',
                $column,
                $exception->getMessage()
            ));
        }
    }
}

function ensureSqliteUserColumn(PDO $pdo, string $column, string $definition): void
{
    $statement = $pdo->prepare('PRAGMA table_info(users)');
    $statement->execute();
    $columns = $statement->fetchAll();

    foreach ($columns as $info) {
        if (isset($info['name']) && $info['name'] === $column) {
            return;
        }
    }

    $pdo->exec(sprintf('ALTER TABLE users ADD COLUMN %s %s', $column, $definition));
}

function databaseTableExists(PDO $pdo, string $table): bool
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $statement = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = :table");
        $statement->execute(['table' => $table]);

        return $statement->fetchColumn() !== false;
    }

    $statement = $pdo->prepare('SHOW TABLES LIKE :table');
    $statement->execute(['table' => $table]);

    return $statement->fetchColumn() !== false;
}

function isPrivilegeError(PDOException $exception): bool
{
    if ($exception->getCode() === '42000') {
        return true;
    }

    $message = $exception->getMessage();

    return stripos($message, 'denied') !== false || stripos($message, 'permission') !== false;
}
