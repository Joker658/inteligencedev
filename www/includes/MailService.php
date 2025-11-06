<?php

declare(strict_types=1);

final class MailTransportException extends RuntimeException
{
}

final class MailService
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function sendVerificationEmail(string $recipientEmail, string $recipientName, string $verificationCode): void
    {
        $subject = 'Vérifiez votre adresse e-mail';
        $htmlBody = $this->buildHtmlBody($recipientName, $verificationCode);
        $textBody = $this->buildTextBody($recipientName, $verificationCode);

        $this->sendMessage($recipientEmail, $recipientName, $subject, $htmlBody, $textBody);
    }

    private function sendMessage(string $recipientEmail, string $recipientName, string $subject, string $htmlBody, string $textBody): void
    {
        $transport = strtolower((string) ($this->config['transport'] ?? 'mail'));

        if ($transport === 'smtp') {
            $this->sendViaSmtp($recipientEmail, $recipientName, $subject, $htmlBody, $textBody);

            return;
        }

        $this->sendViaMailFunction($recipientEmail, $recipientName, $subject, $htmlBody, $textBody);
    }

    private function buildHtmlBody(string $recipientName, string $verificationCode): string
    {
        $escapedName = htmlspecialchars($recipientName === '' ? 'membre' : $recipientName, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification de votre adresse e-mail</title>
</head>
<body>
    <p>Bonjour {$escapedName},</p>
    <p>Merci d'avoir créé un compte sur IntelligenceDev. Pour finaliser votre inscription, veuillez saisir le code de vérification ci-dessous :</p>
    <p style="font-size: 24px; font-weight: bold; letter-spacing: 4px;">{$verificationCode}</p>
    <p>Ce code est valable pendant 30 minutes. En cas d'expiration, demandez un nouveau code depuis la page de vérification de votre compte.</p>
    <p>À très bientôt,<br>L'équipe IntelligenceDev</p>
</body>
</html>
HTML;
    }

    private function buildTextBody(string $recipientName, string $verificationCode): string
    {
        $name = $recipientName === '' ? 'membre' : $recipientName;

        return "Bonjour {$name},\n\n" .
            "Merci d'avoir créé un compte sur IntelligenceDev. Saisissez le code suivant pour vérifier votre adresse e-mail : {$verificationCode}.\n" .
            "Ce code est valable pendant 30 minutes. En cas d'expiration, demandez un nouveau code depuis la page de vérification de votre compte.\n\n" .
            "À très bientôt,\nL'équipe IntelligenceDev";
    }

    private function sendViaMailFunction(string $recipientEmail, string $recipientName, string $subject, string $htmlBody, string $textBody): void
    {
        $fromEmail = (string) ($this->config['from_email'] ?? '');
        $fromName = (string) ($this->config['from_name'] ?? '');

        if ($fromEmail === '') {
            throw new MailTransportException('L\'adresse e-mail de l\'expéditeur est manquante dans la configuration.');
        }

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $this->formatAddress($fromEmail, $fromName);
        $headers[] = 'Reply-To: ' . $this->formatAddress($fromEmail, $fromName);
        $headers[] = 'Date: ' . $this->formatDateHeader();
        $headers[] = 'X-Mailer: PHP/' . PHP_VERSION;

        $success = @mail(
            $this->formatAddress($recipientEmail, $recipientName),
            $this->encodeHeaderValue($subject),
            $htmlBody,
            implode("\r\n", $headers)
        );

        if (!$success) {
            throw new MailTransportException('L\'envoi du message via la fonction mail() a échoué.');
        }
    }

    private function sendViaSmtp(string $recipientEmail, string $recipientName, string $subject, string $htmlBody, string $textBody): void
    {
        $host = (string) ($this->config['host'] ?? '');
        $port = (int) ($this->config['port'] ?? 587);
        $encryption = strtolower((string) ($this->config['encryption'] ?? 'tls'));
        $timeout = max(1, (int) ($this->config['timeout'] ?? 30));
        $username = (string) ($this->config['username'] ?? '');
        $password = (string) ($this->config['password'] ?? '');
        $fromEmail = (string) ($this->config['from_email'] ?? '');
        $fromName = (string) ($this->config['from_name'] ?? '');
        $ehloDomain = (string) ($this->config['ehlo_domain'] ?? 'localhost');

        if ($host === '') {
            throw new MailTransportException('Le serveur SMTP n\'est pas configuré.');
        }

        if ($fromEmail === '') {
            $fromEmail = $username;
        }

        if ($fromEmail === '') {
            throw new MailTransportException('Impossible de déterminer l\'adresse de l\'expéditeur pour l\'envoi SMTP.');
        }

        $transport = $encryption === 'ssl' ? 'ssl://' : 'tcp://';
        $socket = @stream_socket_client(
            $transport . $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT
        );

        if (!is_resource($socket)) {
            throw new MailTransportException(sprintf('Connexion SMTP impossible (%s:%d) : %s', $host, $port, $errstr ?: 'erreur inconnue'));
        }

        stream_set_timeout($socket, $timeout);

        try {
            $this->expectResponse($socket, [220]);
            $ehloDomain = $this->sanitizeEhloDomain($ehloDomain, $host);

            $this->sendCommand($socket, 'EHLO ' . $ehloDomain, [250]);

            if ($encryption === 'tls') {
                $this->sendCommand($socket, 'STARTTLS', [220]);

                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new MailTransportException('Échec de la négociation TLS avec le serveur SMTP.');
                }

                $this->sendCommand($socket, 'EHLO ' . $ehloDomain, [250]);
            }

            if ($username !== '') {
                $this->sendCommand($socket, 'AUTH LOGIN', [334]);
                $this->sendCommand($socket, base64_encode($username), [334]);
                $this->sendCommand($socket, base64_encode($password), [235]);
            }

            $this->sendCommand($socket, 'MAIL FROM: <' . $fromEmail . '>', [250]);
            $this->sendCommand($socket, 'RCPT TO: <' . $recipientEmail . '>', [250, 251]);
            $this->sendCommand($socket, 'DATA', [354]);

            $boundary = bin2hex(random_bytes(16));
            $headers = [];
            $headers[] = 'From: ' . $this->formatAddress($fromEmail, $fromName);
            $headers[] = 'To: ' . $this->formatAddress($recipientEmail, $recipientName);
            $headers[] = 'Reply-To: ' . $this->formatAddress($fromEmail, $fromName);
            $headers[] = 'Subject: ' . $this->encodeHeaderValue($subject);
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
            $headers[] = 'Date: ' . $this->formatDateHeader();
            $headers[] = 'X-Mailer: IntelligenceDev';

            $body = "This is a multipart message in MIME format.\r\n\r\n";
            $body .= '--' . $boundary . "\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $body .= $this->normalizeLineEndings($textBody) . "\r\n\r\n";
            $body .= '--' . $boundary . "\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $body .= $this->normalizeLineEndings($htmlBody) . "\r\n\r\n";
            $body .= '--' . $boundary . "--\r\n";

            $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
            $message = $this->escapeLeadingDots($message);

            $this->write($socket, $message . "\r\n.");
            $this->expectResponse($socket, [250]);
            $this->sendCommand($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private function sanitizeEhloDomain(string $domain, string $fallbackHost): string
    {
        $domain = trim($domain);

        if ($domain === '') {
            $domain = $fallbackHost;
        }

        $domain = preg_replace('#^[a-z0-9.+-]+://#i', '', $domain) ?? '';
        $domain = preg_replace('/[^A-Za-z0-9.-]/', '', $domain) ?? '';
        $domain = rtrim($domain, '.');

        if ($domain === '') {
            $fallback = preg_replace('/[^A-Za-z0-9.-]/', '', $fallbackHost) ?? '';

            return $fallback !== '' ? $fallback : 'localhost';
        }

        return $domain;
    }

    private function sendCommand($socket, string $command, array $expectedCodes): void
    {
        $this->write($socket, $command);
        $this->expectResponse($socket, $expectedCodes);
    }

    private function write($socket, string $data): void
    {
        $result = fwrite($socket, $data . "\r\n");

        if ($result === false) {
            throw new MailTransportException('Impossible d\'écrire dans le socket SMTP.');
        }
    }

    private function expectResponse($socket, array $expectedCodes): string
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;

            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if ($response === '') {
            throw new MailTransportException('Aucune réponse reçue du serveur SMTP.');
        }

        $code = (int) substr($response, 0, 3);

        if (!in_array($code, $expectedCodes, true)) {
            throw new MailTransportException('Réponse inattendue du serveur SMTP : ' . trim($response));
        }

        return $response;
    }

    private function escapeLeadingDots(string $message): string
    {
        return preg_replace('/(^|\r\n)\./', '$1..', $message) ?? $message;
    }

    private function normalizeLineEndings(string $message): string
    {
        return str_replace(["\r\n", "\r", "\n"], "\r\n", $message);
    }

    private function formatAddress(string $email, string $name): string
    {
        $email = trim($email);
        $name = trim($name);

        if ($name === '') {
            return $email;
        }

        return sprintf('%s <%s>', $this->encodeHeaderValue($name), $email);
    }

    private function encodeHeaderValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function formatDateHeader(): string
    {
        return gmdate('D, d M Y H:i:s O');
    }
}
