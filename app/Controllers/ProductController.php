<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use PDOException;

/**
 * MUNG CMS V1
 * File: app/Controllers/ProductController.php
 * Author: Firmanda Himawan
 * Description: Handles product catalog presentation and CTA instrumentation for MUNG CMS.
 */

final class ProductController extends Controller
{
    private const HIGHLIGHT_DEFAULT_TITLE = 'Benefit Teknis';
    private const SPEC_DEFAULT_TITLE = 'Rangkuman Teknis';
    private const SUMMARY_DEFAULT_TITLE = 'Benefit Teknis';
    private const DOCUMENT_DEFAULT_TITLE = 'Dokumen Pendukung';

    public function index(): void
    {
        $settings = $this->siteSettings();
        $products = $this->fetchProducts();

        $this->render('product/index', [
            'title' => 'Produk Aspal Emulsi | ' . ($settings['company_name'] ?? 'Aspal Emulsi'),
            'meta' => [
                'description' => 'Katalog lengkap material aspal emulsi, slurry seal, dan waterproofing siap kirim.',
                'bodyClass' => 'page-product-index',
            ],
            'products' => $products,
            'currentRoute' => 'produk',
        ]);
    }

    public function show(string $slug): void
    {
        $product = $this->findProductBySlug($slug);

        if ($product === null) {
            abort(404, 'Produk tidak ditemukan.');
            return;
        }

        $seo = $this->fetchSeoMeta('product', (int) $product['id']);
        $settings = $this->siteSettings();

        $title = $seo['meta_title'] ?? ($product['name'] . ' | ' . ($settings['company_name'] ?? 'Aspal Emulsi'));
        $metaDescription = $seo['meta_description'] ?? $this->excerpt($product['short_description'] ?? $product['description'] ?? '', 160);
        $metaKeywords = $seo['meta_keywords'] ?? null;
        $metaImage = $product['cover_image_url'] ?? ($settings['logo_url'] ?? asset('images/logo.png'));

        $relatedProducts = $this->fetchRelatedProducts((int) $product['id']);

        $this->render('product/show', [
            'title' => $title,
            'meta' => [
                'description' => $metaDescription,
                'keywords' => $metaKeywords,
                'image' => $metaImage,
                'bodyClass' => 'page-product-detail',
            ],
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'currentRoute' => 'produk',
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchProducts(int $limit = 12): array
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT id, name, slug, short_description, cover_image_url, hero_badge, hero_eyebrow
                 FROM products
                 WHERE status = "published"
                 ORDER BY COALESCE(published_at, created_at) DESC
                 LIMIT :limit'
            );
            $statement->bindValue('limit', $limit, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $exception) {
            error_log('Fetch products failed: ' . $exception->getMessage());

            return [];
        }
    }

    private function findProductBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, slug, short_description, description, cover_image_url, hero_badge, hero_eyebrow,
                    highlights_json, specs_json, summary_json, documents_json,
                    shopee_url, tokopedia_url, whatsapp_url
             FROM products
             WHERE slug = :slug AND status = "published"
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);

        $product = $statement->fetch(PDO::FETCH_ASSOC);
        if ($product === false) {
            return null;
        }

        $highlightBlock = $this->decodeListBlock($product['highlights_json'] ?? null, self::HIGHLIGHT_DEFAULT_TITLE);
        $specBlock = $this->decodeKeyValueBlock($product['specs_json'] ?? null, self::SPEC_DEFAULT_TITLE);
        $summaryBlock = $this->decodeKeyValueBlock($product['summary_json'] ?? null, self::SUMMARY_DEFAULT_TITLE);
        $documentsBlock = $this->decodeDocumentsBlock($product['documents_json'] ?? null, self::DOCUMENT_DEFAULT_TITLE);

        $product['highlights_block'] = $highlightBlock;
        $product['highlights'] = $highlightBlock['items'];
        $product['spec_block'] = $specBlock;
        $product['specs'] = $specBlock['items'];
        $product['summary_block'] = $summaryBlock;
        $product['summary_points'] = $summaryBlock['items'];
        $product['documents_block'] = $documentsBlock;
        $product['documents'] = $documentsBlock['items'];

        return $product;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchRelatedProducts(int $excludeId, int $limit = 3): array
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT id, name, slug, cover_image_url, hero_badge
                 FROM products
                 WHERE status = "published" AND id != :id
                 ORDER BY COALESCE(published_at, created_at) DESC
                 LIMIT :limit'
            );
            $statement->bindValue('id', $excludeId, PDO::PARAM_INT);
            $statement->bindValue('limit', $limit, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $exception) {
            error_log('Fetch related products failed: ' . $exception->getMessage());

            return [];
        }
    }

    private function excerpt(string $text, int $length = 155): string
    {
        $plain = trim(strip_tags($text));
        if ($plain === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            $truncated = mb_substr($plain, 0, $length);
        } else {
            $truncated = substr($plain, 0, $length);
        }

        return rtrim($truncated) . (strlen($plain) > $length ? '…' : '');
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function decodeListJson(mixed $value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $decoded = $decoded['items'];
        }

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn ($item) => trim((string) $item), $decoded), static fn ($item) => $item !== ''));
    }

    /**
     * @param mixed $value
     * @return array<int,array{label:string,value:string}>
     */
    private function decodeKeyValueJson(mixed $value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $maybeTitle = $decoded['title'] ?? null;
            $decoded = $decoded['items'];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $val = trim((string) ($item['value'] ?? ''));

            if ($label === '' && $val === '') {
                continue;
            }

            $items[] = ['label' => $label, 'value' => $val];
        }

        return $items;
    }

    /**
     * @param mixed $value
     * @return array<int,array{title:string,url:string}>
     */
    private function decodeDocumentsJson(mixed $value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $decoded = $decoded['items'];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));

            if ($title === '' && $url === '') {
                continue;
            }

            $items[] = ['title' => $title, 'url' => $url];
        }

        return $items;
    }

    private function decodeListBlock(mixed $value, string $defaultTitle): array
    {
        $block = [
            'title' => $defaultTitle,
            'items' => [],
        ];

        if (!is_string($value) || $value === '') {
            return $block;
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return $block;
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $maybeTitle = trim((string) ($decoded['title'] ?? ''));
            if ($maybeTitle !== '') {
                $block['title'] = $maybeTitle;
            }
            $decoded = $decoded['items'];
        }

        if (!is_array($decoded)) {
            return $block;
        }

        $block['items'] = array_values(array_filter(array_map(static fn ($item) => trim((string) $item), $decoded), static fn ($item) => $item !== ''));

        return $block;
    }

    private function decodeKeyValueBlock(mixed $value, string $defaultTitle): array
    {
        $block = [
            'title' => $defaultTitle,
            'items' => [],
        ];

        if (!is_string($value) || $value === '') {
            return $block;
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return $block;
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $maybeTitle = trim((string) ($decoded['title'] ?? ''));
            if ($maybeTitle !== '') {
                $block['title'] = $maybeTitle;
            }
            $decoded = $decoded['items'];
        }

        if (!is_array($decoded)) {
            return $block;
        }

        $items = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            $valueString = trim((string) ($item['value'] ?? ''));
            if ($label === '' && $valueString === '') {
                continue;
            }
            $items[] = [
                'label' => $label,
                'value' => $valueString,
            ];
        }

        $block['items'] = $items;

        return $block;
    }

    private function decodeDocumentsBlock(mixed $value, string $defaultTitle): array
    {
        $block = [
            'title' => $defaultTitle,
            'items' => [],
        ];

        if (!is_string($value) || $value === '') {
            return $block;
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return $block;
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $maybeTitle = trim((string) ($decoded['title'] ?? ''));
            if ($maybeTitle !== '') {
                $block['title'] = $maybeTitle;
            }
            $decoded = $decoded['items'];
        }

        if (!is_array($decoded)) {
            return $block;
        }

        $items = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = trim((string) ($item['title'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($title === '' && $url === '') {
                continue;
            }
            $items[] = [
                'title' => $title,
                'url' => $url,
            ];
        }

        $block['items'] = $items;

        return $block;
    }
}
