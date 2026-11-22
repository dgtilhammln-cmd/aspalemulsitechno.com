<?php

declare(strict_types=1);

namespace App\Support;

/**
 * MUNG CMS V1
 * File: app/Support/AnalyticsRecorder.php
 * Author: Firmanda Himawan
 * Description: Service layer that enriches and records CTA click & content view analytics with geo/device inference.
 */

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use Throwable;

final class AnalyticsRecorder
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function recordProductClick(array $payload): bool
    {
        $channel = strtolower(trim((string) ($payload['cta_channel'] ?? '')));
        if ($channel === '') {
            return false;
        }

        $productId = $this->toIntOrNull($payload['product_id'] ?? null);
        $productSlug = $this->normalizeSlug($payload['product_slug'] ?? null);
        $productName = $this->safeString($payload['product_name'] ?? null, 180);

        [$productId, $productSlug, $productName] = $this->resolveProductMeta($productId, $productSlug, $productName);

        $environment = $this->collectEnvironment($payload['user_agent'] ?? null);
        $ipAddress = $environment['ip'];
        $geo = $this->lookupGeo($ipAddress);
        $utm = $this->extractUtm($payload);

        $targetUrl = $this->safeString($payload['target_url'] ?? null, 1000);
        $referrerUrl = $this->safeString($payload['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? null), 1000);

        $extra = [
            'page_url' => $this->safeString($payload['page_url'] ?? ($_SERVER['REQUEST_SCHEME'] ?? '') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''), 1000),
            'client_event_time' => $payload['client_event_time'] ?? null,
            'meta' => $payload['metadata'] ?? null,
        ];

        $extraJson = $this->encodeJson($extra);

        $timestamp = $this->resolveTimestamp($payload['captured_at'] ?? null);

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO product_click_events (
                    product_id,
                    product_slug,
                    product_name,
                    cta_channel,
                    cta_label,
                    target_url,
                    ip_address,
                    user_agent,
                    device_type,
                    browser,
                    os,
                    referrer_url,
                    utm_source,
                    utm_medium,
                    utm_campaign,
                    country,
                    region,
                    city,
                    latitude,
                    longitude,
                    extra_json,
                    created_at
                ) VALUES (
                    :product_id,
                    :product_slug,
                    :product_name,
                    :cta_channel,
                    :cta_label,
                    :target_url,
                    :ip_address,
                    :user_agent,
                    :device_type,
                    :browser,
                    :os,
                    :referrer_url,
                    :utm_source,
                    :utm_medium,
                    :utm_campaign,
                    :country,
                    :region,
                    :city,
                    :latitude,
                    :longitude,
                    :extra_json,
                    :created_at
                )'
            );

            $statement->execute([
                'product_id' => $productId,
                'product_slug' => $productSlug,
                'product_name' => $productName,
                'cta_channel' => $channel,
                'cta_label' => $this->safeString($payload['cta_label'] ?? null, 180),
                'target_url' => $targetUrl,
                'ip_address' => $ipAddress,
                'user_agent' => $environment['user_agent'],
                'device_type' => $environment['device_type'],
                'browser' => $environment['browser'],
                'os' => $environment['os'],
                'referrer_url' => $referrerUrl,
                'utm_source' => $utm['utm_source'],
                'utm_medium' => $utm['utm_medium'],
                'utm_campaign' => $utm['utm_campaign'],
                'country' => $geo['country'] ?? null,
                'region' => $geo['region'] ?? null,
                'city' => $geo['city'] ?? null,
                'latitude' => $geo['latitude'] ?? null,
                'longitude' => $geo['longitude'] ?? null,
                'extra_json' => $extraJson,
                'created_at' => $timestamp,
            ]);

            return true;
        } catch (Throwable $throwable) {
            error_log('Record product click failed: ' . $throwable->getMessage());

            return false;
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function recordContentView(string $contentType, array $payload): bool
    {
        $contentType = strtolower(trim($contentType));

        if ($contentType === '') {
            return false;
        }

        $contentId = $this->toIntOrNull($payload['content_id'] ?? null);
        $contentSlug = $this->normalizeSlug($payload['content_slug'] ?? null);
        $contentTitle = $this->safeString($payload['content_title'] ?? null, 255);

        $environment = $this->collectEnvironment($payload['user_agent'] ?? null);
        $ipAddress = $environment['ip'];
        $geo = $this->lookupGeo($ipAddress);
        $utm = $this->extractUtm($payload);

        $referrerUrl = $this->safeString($payload['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? null), 1000);
        $pageUrl = $this->safeString($payload['page_url'] ?? $this->currentUrl(), 1000);

        $extra = [
            'page_url' => $pageUrl,
            'client_event_time' => $payload['client_event_time'] ?? null,
            'meta' => $payload['metadata'] ?? null,
        ];

        $extraJson = $this->encodeJson($extra);
        $timestamp = $this->resolveTimestamp($payload['captured_at'] ?? null);

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO content_view_events (
                    content_type,
                    content_id,
                    content_slug,
                    content_title,
                    ip_address,
                    user_agent,
                    device_type,
                    browser,
                    os,
                    referrer_url,
                    utm_source,
                    utm_medium,
                    utm_campaign,
                    country,
                    region,
                    city,
                    latitude,
                    longitude,
                    extra_json,
                    created_at
                ) VALUES (
                    :content_type,
                    :content_id,
                    :content_slug,
                    :content_title,
                    :ip_address,
                    :user_agent,
                    :device_type,
                    :browser,
                    :os,
                    :referrer_url,
                    :utm_source,
                    :utm_medium,
                    :utm_campaign,
                    :country,
                    :region,
                    :city,
                    :latitude,
                    :longitude,
                    :extra_json,
                    :created_at
                )'
            );

            $statement->execute([
                'content_type' => $contentType,
                'content_id' => $contentId,
                'content_slug' => $contentSlug,
                'content_title' => $contentTitle,
                'ip_address' => $ipAddress,
                'user_agent' => $environment['user_agent'],
                'device_type' => $environment['device_type'],
                'browser' => $environment['browser'],
                'os' => $environment['os'],
                'referrer_url' => $referrerUrl,
                'utm_source' => $utm['utm_source'],
                'utm_medium' => $utm['utm_medium'],
                'utm_campaign' => $utm['utm_campaign'],
                'country' => $geo['country'] ?? null,
                'region' => $geo['region'] ?? null,
                'city' => $geo['city'] ?? null,
                'latitude' => $geo['latitude'] ?? null,
                'longitude' => $geo['longitude'] ?? null,
                'extra_json' => $extraJson,
                'created_at' => $timestamp,
            ]);

            return true;
        } catch (Throwable $throwable) {
            error_log('Record content view failed: ' . $throwable->getMessage());

            return false;
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function recordMarketplaceClick(array $payload): bool
    {
        $channel = $this->safeString($payload['channel'] ?? null, 120);

        if ($channel === null) {
            return false;
        }

        $sourcePage = $this->safeString($payload['page_type'] ?? null, 60);
        $sourceContext = $this->safeString($payload['context'] ?? null, 120);
        $label = $this->safeString($payload['label'] ?? null, 180);

        $environment = $this->collectEnvironment($payload['user_agent'] ?? null);
        $ipAddress = $environment['ip'];
        $geo = $this->lookupGeo($ipAddress);
        $utm = $this->extractUtm($payload);

        $targetUrl = $this->safeString($payload['target_url'] ?? null, 1000);
        $referrerUrl = $this->safeString($payload['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? null), 1000);
        $pageUrl = $this->safeString($payload['page_url'] ?? $this->currentUrl(), 1000);

        $extra = [
            'page_url' => $pageUrl,
            'client_event_time' => $payload['client_event_time'] ?? null,
            'meta' => $payload['metadata'] ?? null,
        ];

        $extraJson = $this->encodeJson($extra);
        $timestamp = $this->resolveTimestamp($payload['captured_at'] ?? null);

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO marketplace_click_events (
                    channel,
                    source_page,
                    source_context,
                    link_label,
                    target_url,
                    ip_address,
                    user_agent,
                    device_type,
                    browser,
                    os,
                    referrer_url,
                    utm_source,
                    utm_medium,
                    utm_campaign,
                    country,
                    region,
                    city,
                    latitude,
                    longitude,
                    extra_json,
                    created_at
                ) VALUES (
                    :channel,
                    :source_page,
                    :source_context,
                    :link_label,
                    :target_url,
                    :ip_address,
                    :user_agent,
                    :device_type,
                    :browser,
                    :os,
                    :referrer_url,
                    :utm_source,
                    :utm_medium,
                    :utm_campaign,
                    :country,
                    :region,
                    :city,
                    :latitude,
                    :longitude,
                    :extra_json,
                    :created_at
                )'
            );

            $statement->execute([
                'channel' => strtolower($channel),
                'source_page' => $sourcePage,
                'source_context' => $sourceContext,
                'link_label' => $label,
                'target_url' => $targetUrl,
                'ip_address' => $ipAddress,
                'user_agent' => $environment['user_agent'],
                'device_type' => $environment['device_type'],
                'browser' => $environment['browser'],
                'os' => $environment['os'],
                'referrer_url' => $referrerUrl,
                'utm_source' => $utm['utm_source'],
                'utm_medium' => $utm['utm_medium'],
                'utm_campaign' => $utm['utm_campaign'],
                'country' => $geo['country'] ?? null,
                'region' => $geo['region'] ?? null,
                'city' => $geo['city'] ?? null,
                'latitude' => $geo['latitude'] ?? null,
                'longitude' => $geo['longitude'] ?? null,
                'extra_json' => $extraJson,
                'created_at' => $timestamp,
            ]);

            return true;
        } catch (Throwable $throwable) {
            error_log('Record marketplace click failed: ' . $throwable->getMessage());

            return false;
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,?string>
     */
    private function extractUtm(array $payload): array
    {
        $source = null;
        $medium = null;
        $campaign = null;

        foreach (['page_url', 'referrer', 'target_url'] as $key) {
            if (empty($payload[$key])) {
                continue;
            }

            $query = parse_url((string) $payload[$key], PHP_URL_QUERY);
            if (!is_string($query) || $query === '') {
                continue;
            }

            parse_str($query, $params);

            if ($source === null && isset($params['utm_source'])) {
                $source = $this->safeString($params['utm_source'], 120);
            }
            if ($medium === null && isset($params['utm_medium'])) {
                $medium = $this->safeString($params['utm_medium'], 120);
            }
            if ($campaign === null && isset($params['utm_campaign'])) {
                $campaign = $this->safeString($params['utm_campaign'], 120);
            }
        }

        if (isset($payload['utm_source']) && $payload['utm_source'] !== '') {
            $source = $this->safeString($payload['utm_source'], 120);
        }
        if (isset($payload['utm_medium']) && $payload['utm_medium'] !== '') {
            $medium = $this->safeString($payload['utm_medium'], 120);
        }
        if (isset($payload['utm_campaign']) && $payload['utm_campaign'] !== '') {
            $campaign = $this->safeString($payload['utm_campaign'], 120);
        }

        return [
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
        ];
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{ip:string,user_agent:?string,device_type:?string,browser:?string,os:?string}
     */
    private function collectEnvironment(mixed $userAgentOverride = null): array
    {
        $server = $_SERVER ?? [];
        $ip = $this->resolveIp($server);
        $userAgent = $this->safeString($userAgentOverride ?? ($server['HTTP_USER_AGENT'] ?? ''), 500);

        return [
            'ip' => $ip,
            'user_agent' => $userAgent,
            'device_type' => $this->detectDeviceType($userAgent),
            'browser' => $this->detectBrowser($userAgent),
            'os' => $this->detectOs($userAgent),
        ];
    }

    /**
     * @param array<string,string> $server
     */
    private function resolveIp(array $server): string
    {
        $candidates = [
            $server['HTTP_CF_CONNECTING_IP'] ?? null,
            $server['HTTP_X_FORWARDED_FOR'] ?? null,
            $server['HTTP_X_REAL_IP'] ?? null,
            $server['HTTP_CLIENT_IP'] ?? null,
            $server['REMOTE_ADDR'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (!is_string($value) || $value === '') {
                continue;
            }

            $parts = explode(',', $value);

            foreach ($parts as $part) {
                $candidate = trim($part);
                if ($candidate === '') {
                    continue;
                }

                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }
        }

        return '';
    }

    private function detectDeviceType(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        $ua = strtolower($userAgent);

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet') || str_contains($ua, 'kindle')) {
            return 'tablet';
        }

        if (str_contains($ua, 'mobi') || str_contains($ua, 'iphone') || str_contains($ua, 'android')) {
            return 'mobile';
        }

        if (str_contains($ua, 'smart-tv') || str_contains($ua, 'hbbtv') || str_contains($ua, 'appletv')) {
            return 'tv';
        }

        return 'desktop';
    }

    private function detectBrowser(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        $ua = strtolower($userAgent);

        $browsers = [
            'edge' => ['edg/'],
            'chrome' => ['chrome/', 'crios/'],
            'firefox' => ['firefox/', 'fxios/'],
            'safari' => ['safari/'],
            'opera' => ['opera', 'opr/'],
            'brave' => ['brave/'],
        ];

        foreach ($browsers as $name => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($ua, $needle)) {
                    if ($name === 'safari' && str_contains($ua, 'chrome')) {
                        continue;
                    }

                    return $name;
                }
            }
        }

        if (str_contains($ua, 'msie') || str_contains($ua, 'trident/')) {
            return 'ie';
        }

        return 'other';
    }

    private function detectOs(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        $ua = strtolower($userAgent);

        $oses = [
            'ios' => ['iphone', 'ipad', 'ipod'],
            'android' => ['android'],
            'windows' => ['windows nt'],
            'macos' => ['mac os x'],
            'linux' => ['linux', 'x11'],
        ];

        foreach ($oses as $name => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($ua, $needle)) {
                    return $name;
                }
            }
        }

        return 'other';
    }

    /**
     * @return array<string,mixed>
     */
    private function lookupGeo(string $ip): array
    {
        static $cache = [];

        if ($ip === '' || $this->isPrivateIp($ip)) {
            return [];
        }

        if (isset($cache[$ip])) {
            return $cache[$ip];
        }

        $resolvers = [
            fn (string $candidate) => $this->fetchGeoFromIpApi($candidate),
            fn (string $candidate) => $this->fetchGeoFromIpWhoIs($candidate),
        ];

        foreach ($resolvers as $resolver) {
            $result = $resolver($ip);
            if ($result !== null) {
                $cache[$ip] = $result;

                return $result;
            }
        }

        $cache[$ip] = [];

        return [];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function fetchGeoFromIpApi(string $ip): ?array
    {
        $url = sprintf('https://ipapi.co/%s/json/', rawurlencode($ip));
        $data = $this->fetchJson($url);

        if (!is_array($data) || isset($data['error'])) {
            return null;
        }

        return $this->formatGeoData(
            $data['country_name'] ?? null,
            $data['region'] ?? ($data['region_code'] ?? null),
            $data['city'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    private function fetchGeoFromIpWhoIs(string $ip): ?array
    {
        $url = sprintf('https://ipwho.is/%s', rawurlencode($ip));
        $data = $this->fetchJson($url);

        if (!is_array($data) || ($data['success'] ?? false) !== true) {
            return null;
        }

        return $this->formatGeoData(
            $data['country'] ?? null,
            $data['region'] ?? null,
            $data['city'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    private function fetchJson(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 1.5,
                'ignore_errors' => true,
                'header' => implode("\r\n", [
                    'User-Agent: AspalAnalyticsBot/1.0',
                    'Accept: application/json',
                ]),
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        try {
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                return null;
            }

            $decoded = json_decode($response, true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $throwable) {
            error_log('Geo fetch failed: ' . $throwable->getMessage());

            return null;
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function formatGeoData(mixed $country, mixed $region, mixed $city, mixed $latitude, mixed $longitude): array
    {
        return [
            'country' => $this->safeString($country, 120),
            'region' => $this->safeString($region, 120),
            'city' => $this->safeString($city, 120),
            'latitude' => $this->toFloatOrNull($latitude),
            'longitude' => $this->toFloatOrNull($longitude),
        ];
    }

    private function isPrivateIp(string $ip): bool
    {
        if ($ip === '') {
            return true;
        }

        $isPublic = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        return $isPublic === false;
    }

    private function safeString(mixed $value, int $maxLength): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (function_exists('mb_substr')) {
            $value = mb_substr($value, 0, $maxLength);
        } else {
            $value = substr($value, 0, $maxLength);
        }

        return $value;
    }

    private function encodeJson(array $data): ?string
    {
        $filtered = array_filter($data, static function ($value) {
            if (is_array($value)) {
                return array_filter($value, static fn ($inner) => $inner !== null && $inner !== '') !== [];
            }

            return $value !== null && $value !== '';
        });

        if ($filtered === []) {
            return null;
        }

        $json = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? null : $json;
    }

    private function resolveTimestamp(mixed $value): string
    {
        if (is_string($value) && $value !== '') {
            try {
                $parsed = new DateTimeImmutable($value);

                return $parsed->format('Y-m-d H:i:s');
            } catch (Throwable) {
                // ignore and fallback
            }
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));

        return $now->format('Y-m-d H:i:s');
    }

    private function normalizeSlug(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\-]+/', '-', $value) ?? $value;
        $value = trim($value, '-');

        return $value === '' ? null : $value;
    }

    private function toIntOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function toFloatOrNull(mixed $value): ?float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    private function currentUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        return $scheme . '://' . $host . $requestUri;
    }

    /**
     * @return array{0:?int,1:?string,2:?string}
     */
    private function resolveProductMeta(?int $productId, ?string $productSlug, ?string $productName): array
    {
        if ($productId === null && $productSlug === null) {
            return [null, $productSlug, $productName];
        }

        $by = $productId !== null ? 'id:' . $productId : 'slug:' . $productSlug;

        static $cache = [];
        if (isset($cache[$by])) {
            return $cache[$by];
        }

        try {
            if ($productId !== null) {
                $statement = $this->pdo->prepare('SELECT id, slug, name FROM products WHERE id = :id LIMIT 1');
                $statement->execute(['id' => $productId]);
            } else {
                $statement = $this->pdo->prepare('SELECT id, slug, name FROM products WHERE slug = :slug LIMIT 1');
                $statement->execute(['slug' => $productSlug]);
            }

            $result = $statement->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $resolved = [
                    (int) $result['id'],
                    $this->safeString($result['slug'], 200),
                    $this->safeString($result['name'], 180),
                ];

                $cache[$by] = $resolved;

                return $resolved;
            }
        } catch (PDOException $exception) {
            error_log('Resolve product meta failed: ' . $exception->getMessage());
        }

        $resolved = [$productId, $productSlug, $productName];
        $cache[$by] = $resolved;

        return $resolved;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function recordLead(array $payload): bool
    {
        $name = $this->safeString($payload['name'] ?? '', 150);
        $phone = $this->safeString($payload['phone'] ?? '', 50);
        $kota = $this->safeString($payload['kota'] ?? $payload['city'] ?? '', 100);
        $needs = $this->safeString($payload['needs'] ?? '', 2000);
        $source = $this->safeString($payload['source'] ?? 'floating_wa', 100);

        if ($name === '' || $phone === '') {
            return false;
        }

        $environment = $this->collectEnvironment($payload['user_agent'] ?? null);
        $utm = $this->extractUtm($payload);

        $utmSource = $this->safeString($payload['utm_source'] ?? ($utm['source'] ?? null), 120);
        $utmMedium = $this->safeString($payload['utm_medium'] ?? ($utm['medium'] ?? null), 120);
        $utmCampaign = $this->safeString($payload['utm_campaign'] ?? ($utm['campaign'] ?? null), 150);
        $utmTerm = $this->safeString($payload['utm_term'] ?? null, 150);
        $utmContent = $this->safeString($payload['utm_content'] ?? null, 150);
        $referrer = $this->safeString($payload['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? null), 500);
        $deviceType = ucfirst((string) ($environment['device_type'] ?? 'Desktop'));

        $this->ensureLeadsTable();

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO leads (
                    name, phone, kota, needs, source, device, ip_address, user_agent,
                    utm_source, utm_medium, utm_campaign, utm_term, utm_content, referrer, created_at
                ) VALUES (
                    :name, :phone, :kota, :needs, :source, :device, :ip, :ua,
                    :utm_source, :utm_medium, :utm_campaign, :utm_term, :utm_content, :referrer, NOW()
                )
            ');

            return $stmt->execute([
                ':name'         => $name,
                ':phone'        => $phone,
                ':kota'         => $kota,
                ':needs'        => $needs,
                ':source'       => $source,
                ':device'       => $deviceType,
                ':ip'           => $environment['ip'],
                ':ua'           => $environment['user_agent'],
                ':utm_source'   => $utmSource,
                ':utm_medium'   => $utmMedium,
                ':utm_campaign' => $utmCampaign,
                ':utm_term'     => $utmTerm,
                ':utm_content'  => $utmContent,
                ':referrer'     => $referrer,
            ]);
        } catch (Throwable $throwable) {
            error_log('Record lead failed: ' . $throwable->getMessage());

            return false;
        }
    }

    private function ensureLeadsTable(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }

        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS leads (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL,
                    phone VARCHAR(50) NOT NULL,
                    kota VARCHAR(100) NULL,
                    needs TEXT NULL,
                    source VARCHAR(100) DEFAULT 'floating_wa',
                    device VARCHAR(50) DEFAULT 'Desktop',
                    ip_address VARCHAR(45) NULL,
                    user_agent VARCHAR(500) NULL,
                    utm_source VARCHAR(120) NULL,
                    utm_medium VARCHAR(120) NULL,
                    utm_campaign VARCHAR(150) NULL,
                    utm_term VARCHAR(150) NULL,
                    utm_content VARCHAR(150) NULL,
                    referrer VARCHAR(500) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_leads_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // Pastikan kolom baru ada di tabel yang sudah ada sebelumnya
            $cols = [
                'kota'         => 'VARCHAR(100) NULL AFTER phone',
                'device'       => "VARCHAR(50) DEFAULT 'Desktop' AFTER source",
                'utm_source'   => 'VARCHAR(120) NULL AFTER user_agent',
                'utm_medium'   => 'VARCHAR(120) NULL AFTER utm_source',
                'utm_campaign' => 'VARCHAR(150) NULL AFTER utm_medium',
                'utm_term'     => 'VARCHAR(150) NULL AFTER utm_campaign',
                'utm_content'  => 'VARCHAR(150) NULL AFTER utm_term',
                'referrer'     => 'VARCHAR(500) NULL AFTER utm_content',
            ];
            foreach ($cols as $colName => $colDef) {
                try {
                    $checkCol = $this->pdo->prepare(
                        "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND COLUMN_NAME = :col"
                    );
                    $checkCol->execute([':col' => $colName]);
                    if (!$checkCol->fetch()) {
                        $this->pdo->exec("ALTER TABLE leads ADD COLUMN {$colName} {$colDef}");
                    }
                } catch (Throwable) {
                }
            }

            $checked = true;
        } catch (Throwable $e) {
            error_log('Ensure leads table failed: ' . $e->getMessage());
        }
    }
}

