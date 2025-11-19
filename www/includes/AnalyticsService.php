<?php

declare(strict_types=1);

class AnalyticsService
{
    private const DEFAULT_SUPPORT_AVAILABILITY = '24/7';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Retourne les statistiques affichées dans le héro abonnement.
     * Les valeurs sont prioritairement récupérées depuis Cloudflare lorsqu'une configuration est disponible,
     * puis dans la base de données via la table site_metrics.
     *
     * @return array{monthly_downloads:int,satisfaction_rate:float,support_availability:string}
     */
    public function getHeroMetrics(): array
    {
        return [
            'monthly_downloads' => $this->getMonthlyDownloads() ?? 0,
            'satisfaction_rate' => $this->getSatisfactionRate() ?? 0.0,
            'support_availability' => $this->getSupportAvailability() ?? self::DEFAULT_SUPPORT_AVAILABILITY,
        ];
    }

    private function getMonthlyDownloads(): ?int
    {
        $cloudflareValue = $this->fetchCloudflareRequests();

        if ($cloudflareValue !== null) {
            return $cloudflareValue;
        }

        $value = $this->getDbMetric('monthly_downloads');

        return $value !== null ? (int) $value : null;
    }

    private function getSatisfactionRate(): ?float
    {
        $value = $this->getDbMetric('satisfaction_rate');

        return $value !== null ? (float) $value : null;
    }

    private function getSupportAvailability(): ?string
    {
        $value = $this->getDbMetric('support_availability');

        return $value !== null && $value !== '' ? $value : null;
    }

    private function getDbMetric(string $key): ?string
    {
        $statement = $this->pdo->prepare('SELECT metric_value FROM site_metrics WHERE metric_key = :key LIMIT 1');
        $statement->bindValue(':key', $key, PDO::PARAM_STR);
        $statement->execute();
        $result = $statement->fetchColumn();

        return $result !== false ? (string) $result : null;
    }

    private function fetchCloudflareRequests(): ?int
    {
        $zoneId = env('CLOUDFLARE_ZONE_ID');
        $apiToken = env('CLOUDFLARE_API_TOKEN');

        if ($zoneId === null || $apiToken === null) {
            return null;
        }

        $ch = curl_init('https://api.cloudflare.com/client/v4/graphql');
        $query = [
            'query' => 'query Analytics($zoneTag: String!, $since: Time!, $until: Time!) {
                viewer {
                    zones(filter: {zoneTag: $zoneTag}) {
                        httpRequests1dGroups(limit: 30, filter: {datetime_geq: $since, datetime_lt: $until}) {
                            sum {
                                requests
                            }
                        }
                    }
                }
            }',
            'variables' => [
                'zoneTag' => $zoneId,
                'since' => gmdate('Y-m-d\TH:i:s\Z', strtotime('-30 days')),
                'until' => gmdate('Y-m-d\TH:i:s\Z'),
            ],
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiToken,
            ],
            CURLOPT_POSTFIELDS => json_encode($query, JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => 8,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);

            return null;
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            return null;
        }

        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        $groups = $decoded['data']['viewer']['zones'][0]['httpRequests1dGroups'] ?? [];

        if (!is_array($groups)) {
            return null;
        }

        $total = 0;

        foreach ($groups as $group) {
            $value = $group['sum']['requests'] ?? 0;

            if (is_numeric($value)) {
                $total += (int) $value;
            }
        }

        return $total > 0 ? $total : null;
    }
}
