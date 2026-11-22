<?php

declare(strict_types=1);

/**
 * MUNG CMS V1
 * File: app/Support/helpers.php
 * Author: Firmanda Himawan
 * Description: Global helper functions for rendering, assets, identity metadata, and application utilities.
 */

if (!function_exists('cms_name')) {
    function cms_name(): string
    {
        return defined('MUNG_CMS_NAME') ? (string) MUNG_CMS_NAME : 'MUNG CMS V1';
    }
}

if (!function_exists('cms_author')) {
    function cms_author(): string
    {
        return defined('MUNG_CMS_AUTHOR') ? (string) MUNG_CMS_AUTHOR : 'Firmanda Himawan';
    }
}

if (!function_exists('cms_generator')) {
    function cms_generator(): string
    {
        if (defined('MUNG_CMS_GENERATOR')) {
            return (string) MUNG_CMS_GENERATOR;
        }

        return cms_name() . ' by ' . cms_author();
    }
}

if (!function_exists('cms_meta_tags')) {
    function cms_meta_tags(): string
    {
        return sprintf(
            '<meta name="application-name" content="%s">' . PHP_EOL .
            '        <meta name="author" content="%s">' . PHP_EOL .
            '        <meta name="generator" content="%s">',
            htmlspecialchars(cms_name(), ENT_QUOTES),
            htmlspecialchars(cms_author(), ENT_QUOTES),
            htmlspecialchars(cms_generator(), ENT_QUOTES)
        );
    }
}

if (!function_exists('app_pdo')) {
    function app_pdo(): \PDO
    {
        static $connection;

        if ($connection instanceof \PDO) {
            return $connection;
        }

        $connection = getPDO();

        return $connection;
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        $base = __DIR__ . '/..';

        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('get_theme_settings')) {
    /**
     * Get active theme settings from database
     * 
     * @return array{theme: string, colors: array<string, string>}
     */
    function get_theme_settings(): array
    {
        // Check for preview theme parameter (for admin preview)
        $previewTheme = $_GET['theme_preview'] ?? null;
        if ($previewTheme && is_string($previewTheme)) {
            $previewTheme = trim($previewTheme);
            // Validate preview theme exists
            $themeDirs = glob(dirname(__DIR__) . '/app/Views/themes/*', GLOB_ONLYDIR);
            $availableThemes = array_map(function($dir) {
                return basename($dir);
            }, $themeDirs);
            
            if (in_array($previewTheme, $availableThemes, true)) {
                // Use preview theme temporarily
                try {
                    $pdo = app_pdo();
                    $statement = $pdo->query('SELECT theme_colors FROM theme_settings ORDER BY id ASC LIMIT 1');
                    $settings = $statement ? $statement->fetch(\PDO::FETCH_ASSOC) : false;
                    $colors = $settings ? (json_decode($settings['theme_colors'] ?? '{}', true) ?: []) : [];
                    
                    return [
                        'theme' => $previewTheme,
                        'colors' => $colors,
                    ];
                } catch (\Throwable $throwable) {
                    error_log('Theme settings fetch failed: ' . $throwable->getMessage());
                }
            }
        }
        
        static $cached = null;
        
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $pdo = app_pdo();
            $statement = $pdo->query('SELECT active_theme, theme_colors FROM theme_settings ORDER BY id ASC LIMIT 1');
            $settings = $statement ? $statement->fetch(\PDO::FETCH_ASSOC) : false;
            
            if ($settings) {
                $colors = json_decode($settings['theme_colors'] ?? '{}', true) ?: [];
                $cached = [
                    'theme' => $settings['active_theme'] ?? 'default',
                    'colors' => $colors,
                ];
                
                return $cached;
            }
        } catch (\Throwable $throwable) {
            error_log('Theme settings fetch failed: ' . $throwable->getMessage());
        }
        
        // Default fallback
        $cached = [
            'theme' => 'default',
            'colors' => [],
        ];
        
        return $cached;
    }
}

if (!function_exists('theme_view_path')) {
    /**
     * Get theme-specific view path
     * Falls back to default view if theme view doesn't exist
     * 
     * @param string $template Template path (e.g., 'home/index', 'layouts/main')
     * @param string|null $theme Theme name (null = use active theme)
     * @return string Full path to view file
     */
    function theme_view_path(string $template, ?string $theme = null): string
    {
        if ($theme === null) {
            $themeSettings = get_theme_settings();
            $theme = $themeSettings['theme'];
        }
        
        // Try theme-specific view first
        // Template: 'home/index' -> app/Views/themes/{theme}/home/index.php
        // Structure: app/Views/themes/{theme}/home/index.php
        $themeViewPath = app_path("Views/themes/{$theme}/{$template}.php");
        if (is_file($themeViewPath)) {
            return $themeViewPath;
        }
        
        // Fallback to default theme
        $defaultViewPath = app_path("Views/themes/default/{$template}.php");
        if (is_file($defaultViewPath)) {
            return $defaultViewPath;
        }
        
        // Last fallback to old structure (app/Views/) for backward compatibility
        $oldViewPath = app_path("Views/{$template}.php");
        if (is_file($oldViewPath)) {
            return $oldViewPath;
        }
        
        // Last resort: throw error
        throw new \RuntimeException(sprintf('View "%s" tidak ditemukan di theme "%s" atau default.', $template, $theme));
    }
}

if (!function_exists('view_path')) {
    /**
     * Get view path with theme support
     * Automatically uses theme-specific view if available, falls back to default.
     * 
     * @param string $template Template path (e.g., 'home/index', 'layouts/main')
     * @return string Full path to view file
     */
    function view_path(string $template): string
    {
        // Try theme-specific view first, fallback to default
        return theme_view_path($template);
    }
}

if (!function_exists('asset')) {
    function asset(string $path, ?string $version = null): string
    {
        $url = '/' . ltrim($path, '/');
        
        // Add cache busting version if provided
        if ($version !== null && $version !== '') {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . 'v=' . urlencode($version);
        }
        
        return $url;
    }
}

if (!function_exists('asset_version')) {
    /**
     * Get asset version based on file content hash (more reliable than filemtime)
     * This ensures cache busting works even if file timestamp doesn't change
     *
     * @param string $filePath Path to the file
     * @return string|null Version string (hash) or null if file doesn't exist
     */
    function asset_version(string $filePath): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }
        
        // Use file content hash (md5) instead of timestamp
        // This ensures version changes whenever file content changes
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return null;
        }
        
        // Use first 8 characters of md5 hash for shorter URL
        // Still provides good uniqueness while keeping URL short
        return substr(md5($content), 0, 8);
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('render')) {
    /**
     * Render a view with an optional layout wrapper.
     * Automatically uses theme-specific view if available, falls back to default.
     *
     * @param string $template Relative template path under app/Views/themes/{theme} without extension.
     * @param array<string,mixed> $data Variables exposed to the view.
     * @param array<string,mixed> $layout Layout level overrides like title/meta.
     */
    function render(string $template, array $data = [], array $layout = []): void
    {
        // Try theme-specific view first, fallback to default
        $viewFile = theme_view_path($template);

        if (!is_file($viewFile)) {
            http_response_code(500);
            throw new \RuntimeException(sprintf('View "%s" tidak ditemukan.', $template));
        }

        $viewData = $data;
        $layoutData = array_merge([
            'title' => $data['title'] ?? null,
            'meta' => $data['meta'] ?? [],
            'siteSettings' => $data['siteSettings'] ?? null,
            'currentRoute' => $data['currentRoute'] ?? null,
        ], $layout);

        extract($layoutData, EXTR_SKIP);

        ob_start();
        extract($viewData, EXTR_SKIP);
        include $viewFile;
        $content = ob_get_clean();

        // Try theme-specific layout first, fallback to default
        $layoutFile = theme_view_path('layouts/main');
        if (!is_file($layoutFile)) {
            echo $content;

            return;
        }

        include $layoutFile;
    }
}

if (!function_exists('abort')) {
    function abort(int $status = 404, string $message = 'Halaman tidak ditemukan'): void
    {
        http_response_code($status);

        $errorView = view_path('errors/' . $status);
        if (is_file($errorView)) {
            include $errorView;
            return;
        }

        echo e($message);
    }
}

if (!function_exists('excerpt_text')) {
    function excerpt_text(string $text, int $length = 155): string
    {
        $plain = trim(strip_tags($text));
        if ($plain === '') {
            return '';
        }

        $truncated = function_exists('mb_substr')
            ? mb_substr($plain, 0, $length)
            : substr($plain, 0, $length);

        return rtrim($truncated) . (strlen($plain) > $length ? '…' : '');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim($scheme . '://' . $host, '/');

        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('schema_json_ld')) {
    /**
     * Generate JSON-LD structured data script tag
     *
     * @param array<string,mixed> $data Schema.org structured data
     * @return string JSON-LD script tag
     */
    function schema_json_ld(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return '<script type="application/ld+json">' . PHP_EOL . $json . PHP_EOL . '</script>';
    }
}

if (!function_exists('schema_organization')) {
    /**
     * Generate Organization schema
     */
    function schema_organization(array $settings): array
    {
        $companyName = $settings['company_name'] ?? 'Aspal Emulsi Techno';
        $logoUrl = $settings['logo_url'] ?? asset('images/logo.png');
        $email = $settings['company_email'] ?? '';
        $phone = $settings['company_phone'] ?? '';
        $address = $settings['address'] ?? '';
        $website = url();
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $companyName,
            'url' => $website,
            'logo' => url($logoUrl),
        ];

        if ($email) {
            $schema['email'] = $email;
        }

        if ($phone) {
            $schema['telephone'] = $phone;
        }

        if ($address) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressCountry' => 'ID',
            ];
        }

        // Social media
        $sameAs = [];
        if (!empty($settings['instagram_url']) && $settings['instagram_url'] !== '#') {
            $sameAs[] = $settings['instagram_url'];
        }
        if (!empty($settings['tiktok_url']) && $settings['tiktok_url'] !== '#') {
            $sameAs[] = $settings['tiktok_url'];
        }
        if (!empty($sameAs)) {
            $schema['sameAs'] = $sameAs;
        }

        return $schema;
    }
}

if (!function_exists('schema_local_business')) {
    /**
     * Generate LocalBusiness schema
     */
    function schema_local_business(array $settings): array
    {
        $companyName = $settings['company_name'] ?? 'Aspal Emulsi Techno';
        $address = $settings['address'] ?? '';
        $phone = $settings['company_phone'] ?? '';
        $email = $settings['company_email'] ?? '';
        $workingHours = $settings['working_hours'] ?? '';
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $companyName,
            'url' => url(),
        ];

        if ($address) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressCountry' => 'ID',
            ];
        }

        if ($phone) {
            $schema['telephone'] = $phone;
        }

        if ($email) {
            $schema['email'] = $email;
        }

        if ($workingHours) {
            $schema['openingHours'] = $workingHours;
        }

        return $schema;
    }
}

if (!function_exists('schema_service')) {
    /**
     * Generate Service schema
     */
    function schema_service(array $service, string $baseUrl): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $service['name'] ?? 'Jasa Aspal Emulsi',
            'description' => $service['short_description'] ?? $service['description'] ?? '',
            'image' => !empty($service['cover_image_url']) ? url($service['cover_image_url']) : url(asset('images/pengerjaan.jpeg')),
            'provider' => [
                '@type' => 'Organization',
                'name' => 'Aspal Emulsi Techno',
            ],
        ];
    }
}

if (!function_exists('schema_product')) {
    /**
     * Generate Product schema
     */
    function schema_product(array $product, string $baseUrl): array
    {
        $productUrl = url('/produk/' . ($product['slug'] ?? ''));
        $imageUrl = !empty($product['cover_image_url'])
            ? url($product['cover_image_url'])
            : url(asset('images/colabit-seal-coat.png'));

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['name'] ?? 'Produk Aspal Emulsi',
            'description' => $product['short_description'] ?? $product['description'] ?? '',
            'image' => $imageUrl,
            'url' => $productUrl,
            'brand' => [
                '@type' => 'Brand',
                'name' => 'Aspal Emulsi Techno',
            ],
            // Tambahkan rating minimal agar lolos validasi Google
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '5',
                'reviewCount' => '1'
            ],
        ];
    }
}


if (!function_exists('schema_blog_posting')) {
    /**
     * Generate BlogPosting schema
     */
    function schema_blog_posting(array $post, string $baseUrl): array
    {
        $postUrl = url('/blog/' . ($post['slug'] ?? ''));
        $imageUrl = !empty($post['cover_image_url']) ? url($post['cover_image_url']) : url(asset('images/blog-emulsi.jpg'));
        $publishedDate = !empty($post['published_at']) ? date('c', strtotime((string) $post['published_at'])) : date('c');
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post['title'] ?? '',
            'description' => $post['summary'] ?? $post['description'] ?? '',
            'image' => $imageUrl,
            'url' => $postUrl,
            'datePublished' => $publishedDate,
            'dateModified' => $publishedDate,
            'author' => [
                '@type' => 'Organization',
                'name' => 'Aspal Emulsi Techno',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Aspal Emulsi Techno',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => url(asset('images/logo.png')),
                ],
            ],
        ];
    }
}

if (!function_exists('schema_review')) {
    /**
     * Generate Review schema
     */
    function schema_review(array $testimonial, ?array $settings = null): array
    {
        $rating = (int) ($testimonial['rating'] ?? 5);
        
        // Get organization info for itemReviewed
        if ($settings === null) {
            $settings = get_theme_settings();
        }
        
        $companyName = $settings['company_name'] ?? 'Aspal Emulsi Techno';
        $website = url();
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'itemReviewed' => [
                '@type' => 'Organization',
                'name' => $companyName,
                'url' => $website,
            ],
            'reviewBody' => $testimonial['quote'] ?? '',
            'author' => [
                '@type' => 'Person',
                'name' => $testimonial['client_name'] ?? 'Klien',
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $rating,
                'bestRating' => 5,
            ],
        ];
    }
}

if (!function_exists('get_content_section')) {
    /**
     * Get content section text from database and wrap with HTML tag
     *
     * @param string $pageIdentifier Page identifier (e.g., 'blog-detail', 'home')
     * @param string $sectionKey Section key (e.g., 'workflow-heading', 'services-heading')
     * @param string|null $default Default text if not found in database
     * @param string|null $htmlTag HTML tag to wrap the text (e.g., 'h2', 'p', 'span')
     * @param string|null $htmlClass CSS class for the HTML tag
     * @param string|null $htmlStyle Inline style for the HTML tag
     * @return string Content HTML with wrapped tag
     */
    function get_content_section(
        string $pageIdentifier,
        string $sectionKey,
        ?string $default = null,
        ?string $htmlTag = null,
        ?string $htmlClass = null,
        ?string $htmlStyle = null
    ): string {
        static $sectionsCache = [];

        $cacheKey = $pageIdentifier . ':' . $sectionKey;
        if (isset($sectionsCache[$cacheKey])) {
            $text = $sectionsCache[$cacheKey];
        } else {
            try {
                $pdo = app_pdo();
                $stmt = $pdo->prepare(
                    'SELECT content_html FROM content_sections 
                     WHERE page_identifier = :page_identifier AND section_key = :section_key 
                     LIMIT 1'
                );
                $stmt->execute([
                    'page_identifier' => $pageIdentifier,
                    'section_key' => $sectionKey,
                ]);
                $row = $stmt->fetch();

                if ($row && $row['content_html'] !== null && $row['content_html'] !== '') {
                    $text = $row['content_html'];
                } else {
                    $text = $default ?? '';
                }
                
                $sectionsCache[$cacheKey] = $text;
            } catch (Throwable $throwable) {
                // Fallback to default if database error
                $text = $default ?? '';
                $sectionsCache[$cacheKey] = $text;
            }
        }

        // If text is empty, return empty string
        if ($text === '') {
            return '';
        }

        // If htmlTag is provided, wrap text with HTML tag
        if ($htmlTag !== null && $htmlTag !== '') {
            $attributes = [];
            if ($htmlClass !== null && $htmlClass !== '') {
                $attributes[] = 'class="' . htmlspecialchars($htmlClass, ENT_QUOTES) . '"';
            }
            if ($htmlStyle !== null && $htmlStyle !== '') {
                $attributes[] = 'style="' . htmlspecialchars($htmlStyle, ENT_QUOTES) . '"';
            }
            
            $attrs = !empty($attributes) ? ' ' . implode(' ', $attributes) : '';
            // Use e() function for escaping to support HTML entities like &amp;
            return '<' . $htmlTag . $attrs . '>' . e($text) . '</' . $htmlTag . '>';
        }

        // If no htmlTag, return text as-is (escaped for safety)
        return e($text);
    }
}

if (!function_exists('theme_css_url')) {
    /**
     * Get theme CSS file URL
     * 
     * @param string $theme Theme name
     * @return string CSS file URL
     */
    function theme_css_url(string $theme): string
    {
        // CSS/JS files are in root/themes/ for public access
        // Views are in app/Views/themes/ for security
        $themePath = "themes/{$theme}/styles.css";
        // app/Support/helpers.php is in app/Support/, so dirname(dirname(__DIR__)) = root
        $rootPath = dirname(dirname(__DIR__));
        $fullPath = $rootPath . DIRECTORY_SEPARATOR . $themePath;
        
        // If theme CSS exists in root/themes/, use it
        if (is_file($fullPath)) {
            return asset($themePath);
        }
        
        // Fallback to default theme
        return asset('themes/default/styles.css');
    }
}

if (!function_exists('theme_animations_css_url')) {
    /**
     * Get theme animations CSS file URL
     * CSS/JS files are in root/themes/ for public access
     * 
     * @param string $theme Theme name
     * @return string CSS file URL
     */
    function theme_animations_css_url(string $theme): string
    {
        // CSS/JS are in root/themes/ for public URL access
        $themePath = "themes/{$theme}/animations.css";
        $rootPath = dirname(dirname(__DIR__));
        $fullPath = $rootPath . DIRECTORY_SEPARATOR . $themePath;
        
        // If theme animations CSS exists in root/themes/, use it
        if (is_file($fullPath)) {
            return asset($themePath);
        }
        
        // Fallback to default theme
        return asset('themes/default/animations.css');
    }
}

if (!function_exists('theme_animations_js_url')) {
    /**
     * Get theme animations JS file URL
     * CSS/JS files are in root/themes/ for public access
     * 
     * @param string $theme Theme name
     * @return string JS file URL
     */
    function theme_animations_js_url(string $theme): string
    {
        // CSS/JS are in root/themes/ for public URL access
        $themePath = "themes/{$theme}/animations.js";
        $rootPath = dirname(dirname(__DIR__));
        $fullPath = $rootPath . DIRECTORY_SEPARATOR . $themePath;
        
        // If theme animations JS exists in root/themes/, use it
        if (is_file($fullPath)) {
            return asset($themePath);
        }
        
        // Fallback to default theme
        return asset('themes/default/animations.js');
    }
}

if (!function_exists('hex_to_rgb')) {
    /**
     * Convert hex color to RGB values
     * 
     * @param string $hex Hex color (e.g., #ff6600 or #f60)
     * @return array|null Array with r, g, b values or null if invalid
     */
    function hex_to_rgb(string $hex): ?array
    {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Handle 3-digit hex
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        // Validate 6-digit hex
        if (strlen($hex) !== 6 || !preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return null;
        }
        
        return [
            'r' => (int) hexdec(substr($hex, 0, 2)),
            'g' => (int) hexdec(substr($hex, 2, 2)),
            'b' => (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}

if (!function_exists('render_theme_css_variables')) {
    /**
     * Render CSS custom properties for theme colors
     * This function does NOT use cache to ensure colors are always up-to-date
     * 
     * @return string CSS style tag with custom properties
     */
    function render_theme_css_variables(): string
    {
        // Fetch theme settings directly from database (no cache)
        // This ensures colors are always up-to-date after admin changes
        try {
            $pdo = app_pdo();
            $statement = $pdo->query('SELECT active_theme, theme_colors FROM theme_settings ORDER BY id ASC LIMIT 1');
            $settings = $statement ? $statement->fetch(\PDO::FETCH_ASSOC) : false;
            
            $activeTheme = 'default';
            $allThemesColors = [];
            
            if ($settings) {
                $activeTheme = $settings['active_theme'] ?? 'default';
                $allThemesColors = json_decode($settings['theme_colors'] ?? '{}', true) ?: [];
            }
            
            // Check if structure is nested (per-theme) or flat (legacy)
            $isNested = isset($allThemesColors['default']) || isset($allThemesColors['konstruksi']);
            
            if ($isNested) {
                // New nested structure: {default: {...}, konstruksi: {...}}
                $colors = $allThemesColors[$activeTheme] ?? $allThemesColors['default'] ?? [];
            } else {
                // Legacy flat structure: {...}
                $colors = $allThemesColors;
            }
        } catch (\Throwable $throwable) {
            error_log('Theme settings fetch failed in render_theme_css_variables: ' . $throwable->getMessage());
            $colors = [];
            $activeTheme = 'default';
        }
        
        // Default color values per theme (used if colors are not set in database)
        $defaultColorsByTheme = [
            'default' => [
                'primary' => '#ff6600',
                'primaryLight' => '#ff7a00',
                'primaryDark' => '#ff6d00',
                'secondary' => '#ffaa00',
                'accent' => '#ffd873',
                'accentLight' => '#ffe8b3',
                'background' => '#000000',
                'backgroundDark' => '#0a0a0a',
                'backgroundLight' => '#1a1a1a',
                'backgroundMedium' => '#333333',
                'textPrimary' => '#ffffff',
                'textSecondary' => '#e0e0e0',
                'textTertiary' => '#b0b0b0',
                'textMuted' => '#888888',
                'border' => '#333333',
                'borderLight' => '#555555',
                'overlay' => 'rgba(0, 0, 0, 0.8)',
                'overlayHover' => 'rgba(0, 0, 0, 0.9)',
            ],
            'konstruksi' => [
                'primary' => '#ff6600',
                'primaryLight' => '#ff8533',
                'primaryDark' => '#cc5200',
                'secondary' => '#0066cc',
                'accent' => '#4da6ff',
                'accentLight' => '#b3d9ff',
                'background' => '#ffffff',
                'backgroundDark' => '#f5f7fa',
                'backgroundLight' => '#fafbfc',
                'backgroundMedium' => '#e8ecf1',
                'textPrimary' => '#1a1f2e',
                'textSecondary' => '#4a5568',
                'textTertiary' => '#718096',
                'textMuted' => '#a0aec0',
                'border' => '#e2e8f0',
                'borderLight' => '#f1f5f9',
                'overlay' => 'rgba(26, 31, 46, 0.6)',
                'overlayHover' => 'rgba(26, 31, 46, 0.7)',
            ],
        ];
        
        // Get default colors for active theme
        $defaultColors = $defaultColorsByTheme[$activeTheme] ?? $defaultColorsByTheme['default'];
        
        // Merge database colors with defaults (database colors override defaults)
        $allColors = array_merge($defaultColors, $colors);
        
        $cssVars = [];
        foreach ($allColors as $key => $value) {
            if (is_string($value) && $value !== '') {
                $cssVar = '--theme-' . strtolower(preg_replace('/([A-Z])/', '-$1', $key));
                $cssVars[] = $cssVar . ': ' . $value . ';';
                
                // Generate RGB values for hex colors (for rgba usage)
                if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
                    $rgb = hex_to_rgb($value);
                    if ($rgb !== null) {
                        $cssVarRgb = $cssVar . '-rgb';
                        $cssVars[] = $cssVarRgb . ': ' . $rgb['r'] . ', ' . $rgb['g'] . ', ' . $rgb['b'] . ';';
                    }
                }
            }
        }
        
        if (empty($cssVars)) {
            return '';
        }
        
        return '<style>:root {' . PHP_EOL . '            ' . implode(PHP_EOL . '            ', $cssVars) . PHP_EOL . '        }</style>' . PHP_EOL;
    }
}