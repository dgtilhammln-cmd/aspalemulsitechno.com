<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use PDOException;

/**
 * MUNG CMS V1
 * File: app/Controllers/SitemapController.php
 * Description: Generates XML sitemap for SEO
 */
final class SitemapController extends Controller
{
    public function index(): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        $baseUrl = $this->getBaseUrl();
        $urls = $this->generateUrls($baseUrl);

        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
        echo '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . PHP_EOL;
        echo '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . PHP_EOL;
        echo '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . PHP_EOL;

        foreach ($urls as $url) {
            echo '  <url>' . PHP_EOL;
            echo '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . '</loc>' . PHP_EOL;
            if (isset($url['lastmod'])) {
                echo '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>' . PHP_EOL;
            }
            if (isset($url['changefreq'])) {
                echo '    <changefreq>' . htmlspecialchars($url['changefreq'], ENT_XML1, 'UTF-8') . '</changefreq>' . PHP_EOL;
            }
            if (isset($url['priority'])) {
                echo '    <priority>' . htmlspecialchars($url['priority'], ENT_XML1, 'UTF-8') . '</priority>' . PHP_EOL;
            }
            echo '  </url>' . PHP_EOL;
        }

        echo '</urlset>' . PHP_EOL;
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function generateUrls(string $baseUrl): array
    {
        $urls = [];

        // Homepage - highest priority
        $urls[] = [
            'loc' => $baseUrl . '/',
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // Blog index
        $urls[] = [
            'loc' => $baseUrl . '/blog',
            'lastmod' => $this->getLastModified('blog_posts'),
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ];

        // Product index
        $urls[] = [
            'loc' => $baseUrl . '/produk',
            'lastmod' => $this->getLastModified('products'),
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ];

        // Individual blog posts
        $blogPosts = $this->fetchPublishedItems('blog_posts', 'slug', 'published_at');
        foreach ($blogPosts as $post) {
            $urls[] = [
                'loc' => $baseUrl . '/blog/' . $post['slug'],
                'lastmod' => $this->formatDate($post['published_at'] ?? $post['updated_at'] ?? null),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        }

        // Individual products
        $products = $this->fetchPublishedItems('products', 'slug', 'published_at');
        foreach ($products as $product) {
            $urls[] = [
                'loc' => $baseUrl . '/produk/' . $product['slug'],
                'lastmod' => $this->formatDate($product['published_at'] ?? $product['updated_at'] ?? null),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        }

        return $urls;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchPublishedItems(string $table, string $slugColumn, string $dateColumn): array
    {
        try {
            // Sanitize table and column names to prevent SQL injection
            $allowedTables = ['blog_posts', 'products'];
            $allowedColumns = ['slug', 'published_at', 'updated_at', 'created_at'];
            
            if (!in_array($table, $allowedTables, true)) {
                return [];
            }
            
            if (!in_array($slugColumn, $allowedColumns, true) || !in_array($dateColumn, $allowedColumns, true)) {
                return [];
            }
            
            $statement = $this->pdo->prepare(
                "SELECT {$slugColumn}, {$dateColumn}, updated_at
                 FROM {$table}
                 WHERE status = 'published'
                 ORDER BY COALESCE({$dateColumn}, created_at) DESC"
            );
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $exception) {
            error_log("Fetch {$table} for sitemap failed: " . $exception->getMessage());

            return [];
        }
    }

    private function getLastModified(string $table): string
    {
        try {
            $statement = $this->pdo->prepare(
                "SELECT MAX(COALESCE(published_at, updated_at, created_at)) as lastmod
                 FROM {$table}
                 WHERE status = 'published'"
            );
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC);

            if ($result && isset($result['lastmod'])) {
                return $this->formatDate($result['lastmod']);
            }
        } catch (PDOException $exception) {
            error_log("Get last modified for {$table} failed: " . $exception->getMessage());
        }

        return date('Y-m-d');
    }

    private function formatDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return date('Y-m-d');
        }

        try {
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                return date('Y-m-d');
            }

            return date('Y-m-d', $timestamp);
        } catch (\Exception $e) {
            return date('Y-m-d');
        }
    }

    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host;

        // Remove trailing slash
        return rtrim($baseUrl, '/');
    }
}

