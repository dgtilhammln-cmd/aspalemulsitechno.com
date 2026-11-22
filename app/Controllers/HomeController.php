<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use PDOException;

final class HomeController extends Controller
{
    public function index(): void
    {
        $settings = $this->siteSettings();

        $services = $this->fetchServices(6);
        $products = $this->fetchProducts(6);
        $blogPosts = $this->fetchBlogPosts(3);
        $portfolioItems = $this->fetchPortfolioItems(5);
        $testimonials = $this->fetchTestimonials(4);

        $heroSlides = $this->fetchHomeSliderSlides();

        $meta = [
            'description' => $settings['meta_description'] ?? null,
            'keywords' => $settings['meta_keywords'] ?? null,
            'bodyClass' => 'page-home',
        ];

        if ($heroSlides === []) {
            $heroSlides = $this->fallbackHeroSlides($products, $portfolioItems);
        }

        $this->render('home/index', [
            'title' => $settings['meta_title'] ?? ($settings['company_name'] ?? 'Aspal Emulsi Techno'),
            'meta' => $meta,
            'services' => $services,
            'products' => $products,
            'blogPosts' => $blogPosts,
            'portfolioItems' => $portfolioItems,
            'testimonials' => $testimonials,
            'heroSlides' => $heroSlides,
            'currentRoute' => 'home',
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchServices(int $limit = 6): array
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT name, slug, short_description, description, cover_image_url
                 FROM services
                 WHERE status = "published"
                 ORDER BY COALESCE(published_at, created_at) DESC
                 LIMIT :limit'
            );
            $statement->bindValue('limit', $limit, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $exception) {
            error_log('Fetch services failed: ' . $exception->getMessage());

            return [];
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchProducts(int $limit = 6): array
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT id, name, slug, short_description, cover_image_url, hero_badge, hero_eyebrow, highlights_json, summary_json
                 FROM products
                 WHERE status = "published"
                 ORDER BY COALESCE(published_at, created_at) DESC
                 LIMIT :limit'
            );
            $statement->bindValue('limit', $limit, PDO::PARAM_INT);
            $statement->execute();

            $products = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($products as &$product) {
                $product['highlights'] = $this->decodeListJson($product['highlights_json'] ?? null);
                $product['summary'] = $this->decodeKeyValueJson($product['summary_json'] ?? null);
            }

            return $products;
        } catch (PDOException $exception) {
            error_log('Fetch products failed: ' . $exception->getMessage());

            return [];
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchBlogPosts(int $limit = 3): array
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT bp.id, bp.title, bp.slug, bp.summary, bp.cover_image_url, bp.hero_tag, bp.published_at, bp.hero_reading_time,
                        u.full_name AS author
                 FROM blog_posts bp
                 LEFT JOIN users u ON u.id = bp.author_id
                 WHERE bp.status = "published"
                 ORDER BY COALESCE(bp.published_at, bp.created_at) DESC
                 LIMIT :limit'
            );
            $statement->bindValue('limit', $limit, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $exception) {
            error_log('Fetch blog posts failed: ' . $exception->getMessage());

            return [];
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchPortfolioItems(int $limit = 5): array
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT title, slug, category, description, image_url
                 FROM portfolio_items
                 WHERE status = "published"
                 ORDER BY COALESCE(showcased_at, created_at) DESC
                 LIMIT :limit'
            );
            $statement->bindValue('limit', $limit, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $exception) {
            error_log('Fetch portfolio failed: ' . $exception->getMessage());

            return [];
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchTestimonials(int $limit = 4): array
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT client_name, company, role, quote, rating, avatar_url
                 FROM testimonials
                 WHERE status = "published"
                 ORDER BY COALESCE(published_at, created_at) DESC
                 LIMIT :limit'
            );
            $statement->bindValue('limit', $limit, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $exception) {
            error_log('Fetch testimonials failed: ' . $exception->getMessage());

            return [];
        }
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function fetchHomeSliderSlides(): array
    {
        try {
            $stmt = $this->pdo->query(
                'SELECT title, subtitle, description, image_url, link_url
                 FROM home_slider_slides
                 WHERE status = "published"
                 ORDER BY sort_order ASC, created_at DESC'
            );
            $slides = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            if (!$slides) {
                return [];
            }

            $resolved = [];
            foreach ($slides as $slide) {
                $resolved[] = [
                    'title' => (string) ($slide['title'] ?? 'Konten Slider'),
                    'subtitle' => (string) ($slide['subtitle'] ?? ''),
                    'description' => (string) ($slide['description'] ?? ''),
                    'image' => $this->resolveSliderImage($slide['image_url'] ?? null),
                    'detail_url' => $this->resolveSliderLink($slide['link_url'] ?? null),
                ];
            }

            return $resolved;
        } catch (PDOException $exception) {
            error_log('Fetch home slider failed: ' . $exception->getMessage());

            return [];
        }
    }

    /**
     * @param array<int,array<string,mixed>> $products
     * @param array<int,array<string,mixed>> $portfolio
     * @return array<int,array<string,string>>
     */
    private function fallbackHeroSlides(array $products, array $portfolio): array
    {
        $slides = [];

        foreach ($products as $product) {
            if (count($slides) >= 4) {
                break;
            }

            $slides[] = [
                'title' => $product['name'] ?? 'Produk Aspal Emulsi',
                'subtitle' => $product['hero_badge'] ?? 'Produk Unggulan',
                'description' => $product['short_description'] ?? excerpt_text((string) ($product['hero_eyebrow'] ?? ''), 180),
                'image' => $this->resolveSliderImage($product['cover_image_url'] ?? null),
                'detail_url' => '/produk/' . ($product['slug'] ?? ''),
            ];
        }

        if ($slides === [] && $portfolio !== []) {
            foreach ($portfolio as $item) {
                if (count($slides) >= 4) {
                    break;
                }
                $slides[] = [
                    'title' => $item['title'] ?? 'Portofolio Proyek',
                    'subtitle' => $item['category'] ?? 'Project Highlight',
                    'description' => excerpt_text((string) ($item['description'] ?? ''), 180),
                    'image' => $this->resolveSliderImage($item['image_url'] ?? null),
                    'detail_url' => '#galeri',
                ];
            }
        }

        if ($slides === []) {
            $slides = [
                [
                    'title' => 'Aspal Emulsi Siap Kirim',
                    'subtitle' => 'Solusi Lapangan',
                    'description' => 'Formulasi emulsi kami diracik supaya pekerjaan overlay, patching, dan sealing jadi praktis tanpa mengorbankan daya rekat.',
                    'image' => $this->resolveSliderImage('images/hero-1.jpg'),
                    'detail_url' => '#produk',
                ],
                [
                    'title' => 'Support Engineer On-Site',
                    'subtitle' => 'Pendampingan Teknis',
                    'description' => 'Tim engineer kami standby mendampingi pengerjaan supaya hasil lapangan sesuai spesifikasi yang kamu butuhkan.',
                    'image' => $this->resolveSliderImage('images/hero-2.jpeg'),
                    'detail_url' => '#layanan',
                ],
                [
                    'title' => 'Jaringan Distribusi Nasional',
                    'subtitle' => 'Logistik Cepat',
                    'description' => 'Warehouse kami tersebar di kota-kota besar sehingga pengiriman emulsi bisa tiba lebih cepat dengan biaya efisien.',
                    'image' => $this->resolveSliderImage('images/hero-3.jpeg'),
                    'detail_url' => '#hubungi',
                ],
            ];
        }

        return $slides;
    }

    private function resolveSliderImage(?string $path): string
    {
        if ($path === null || $path === '') {
            return asset('images/hero-1.jpg');
        }

        if (preg_match('/^https?:/i', $path) || str_starts_with($path, '//')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    private function resolveSliderLink(?string $link): string
    {
        if ($link === null || $link === '') {
            return '#';
        }

        if (preg_match('/^https?:/i', $link) || str_starts_with($link, '/')) {
            return $link;
        }

        return '/' . ltrim($link, '/');
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
}
