<?php

declare(strict_types=1);

if (!function_exists('env')) {
    require_once __DIR__ . '/config.php';
}

const MAIL_DEFAULT_TRANSPORT = 'mail';
const MAIL_DEFAULT_HOST = 'ssl0.ovh.net';
const MAIL_DEFAULT_PORT = 587;
const MAIL_DEFAULT_ENCRYPTION = 'tls';
const MAIL_DEFAULT_USERNAME = 'contact@vortexdev.store';
const MAIL_DEFAULT_PASSWORD = 'Aurelien66290@';
const MAIL_DEFAULT_FROM_EMAIL = 'contact@vortexdev.store';
const MAIL_DEFAULT_FROM_NAME = 'IntelligenceDev';
const MAIL_DEFAULT_TIMEOUT = 30;
const MAIL_DEFAULT_EHLO_DOMAIN = 'intelligencedev.fr/';

/**
 * Retourne la configuration du service d'envoi de courriels.
 */
function getMailerConfig(): array
{
    return [
        'transport' => env('INTELLIGENCEDEV_MAIL_TRANSPORT', env('MAIL_TRANSPORT', MAIL_DEFAULT_TRANSPORT)),
        'host' => env('INTELLIGENCEDEV_MAIL_HOST', env('MAIL_HOST', MAIL_DEFAULT_HOST)),
        'port' => (int) env('INTELLIGENCEDEV_MAIL_PORT', env('MAIL_PORT', (string) MAIL_DEFAULT_PORT)),
        'encryption' => env('INTELLIGENCEDEV_MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', MAIL_DEFAULT_ENCRYPTION)),
        'username' => env('INTELLIGENCEDEV_MAIL_USER', env('MAIL_USER', MAIL_DEFAULT_USERNAME)),
        'password' => env('INTELLIGENCEDEV_MAIL_PASS', env('MAIL_PASS', MAIL_DEFAULT_PASSWORD)),
        'from_email' => env('INTELLIGENCEDEV_MAIL_FROM_EMAIL', env('MAIL_FROM_EMAIL', MAIL_DEFAULT_FROM_EMAIL)),
        'from_name' => env('INTELLIGENCEDEV_MAIL_FROM_NAME', env('MAIL_FROM_NAME', MAIL_DEFAULT_FROM_NAME)),
        'timeout' => (int) env('INTELLIGENCEDEV_MAIL_TIMEOUT', env('MAIL_TIMEOUT', (string) MAIL_DEFAULT_TIMEOUT)),
        'ehlo_domain' => env('INTELLIGENCEDEV_MAIL_EHLO_DOMAIN', env('MAIL_EHLO_DOMAIN', MAIL_DEFAULT_EHLO_DOMAIN)),
    ];
}
