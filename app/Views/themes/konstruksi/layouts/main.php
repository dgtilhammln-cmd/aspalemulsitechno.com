<?php

$settings = $siteSettings ?? [];
$companyName = $settings['company_name'] ?? 'Aspal Emulsi Techno';
$metaTitle = $title ?: ($settings['meta_title'] ?? $companyName);
$metaDescription = $meta['description'] ?? ($settings['meta_description'] ?? '');
$metaKeywords = $meta['keywords'] ?? ($settings['meta_keywords'] ?? '');
$metaImage = $meta['image'] ?? ($settings['logo_url'] ?? asset('images/logo.png'));
$favicon = $settings['favicon_url'] ?? asset('images/logo.png');
$bodyClass = trim((string) ($meta['bodyClass'] ?? ''));

?><!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
        <?php
        // SEO: Robots meta tag - allow indexing unless explicitly disabled
        // Priority: 1. Page-specific meta['robots'], 2. Site settings meta_robots, 3. Default
        $robotsContent = $meta['robots'] 
            ?? ($settings['meta_robots'] ?? null)
            ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
        ?>
        <meta name="robots" content="<?= e($robotsContent) ?>">
        <?= cms_meta_tags() ?>
        <title><?= e($metaTitle) ?></title>
        <?php if ($metaDescription !== ''): ?>
            <meta name="description" content="<?= e($metaDescription) ?>">
        <?php endif; ?>
        <?php if ($metaKeywords !== ''): ?>
            <meta name="keywords" content="<?= e($metaKeywords) ?>">
        <?php endif; ?>
        
        <?php
        // SEO: Canonical URL - prevent duplicate content
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestUri = parse_url($requestUri, PHP_URL_PATH) ?: '/';
        $canonicalUrl = $meta['canonical'] ?? url($requestUri);
        ?>
        <link rel="canonical" href="<?= e($canonicalUrl) ?>">
        
        <?php
        // SEO: Open Graph tags - complete set for social sharing
        $ogType = $meta['og:type'] ?? 'website';
        $ogUrl = $meta['og:url'] ?? $canonicalUrl;
        $ogLocale = $meta['og:locale'] ?? 'id_ID';
        $ogSiteName = $companyName;
        ?>
        <meta property="og:type" content="<?= e($ogType) ?>">
        <meta property="og:title" content="<?= e($metaTitle) ?>">
        <?php if ($metaDescription !== ''): ?>
            <meta property="og:description" content="<?= e($metaDescription) ?>">
        <?php endif; ?>
        <meta property="og:image" content="<?= e(url($metaImage)) ?>">
        <meta property="og:url" content="<?= e($ogUrl) ?>">
        <meta property="og:locale" content="<?= e($ogLocale) ?>">
        <meta property="og:site_name" content="<?= e($ogSiteName) ?>">
        
        <?php
        // SEO: Twitter Card tags
        ?>
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?= e($metaTitle) ?>">
        <?php if ($metaDescription !== ''): ?>
            <meta name="twitter:description" content="<?= e($metaDescription) ?>">
        <?php endif; ?>
        <meta name="twitter:image" content="<?= e(url($metaImage)) ?>">
        
        <link rel="icon" href="<?= e($favicon) ?>">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
        <?php
        // Load theme CSS and variables with cache busting
        $themeSettings = get_theme_settings();
        $activeTheme = $themeSettings['theme'];
        
        // Cache busting: Use file content hash as version (more reliable than filemtime)
        // Hash will change whenever file content changes, regardless of file timestamp
        // CSS/JS files are in root/themes/ for public URL access
        // Views are in app/Views/themes/ (protected by .htaccess)
        $rootPath = dirname(dirname(dirname(dirname(__DIR__))));
        // CSS/JS paths are in root/themes/ (publicly accessible)
        $themeCssFullPath = $rootPath . DIRECTORY_SEPARATOR . "themes/{$activeTheme}/styles.css";
        $themeAnimationsCssFullPath = $rootPath . DIRECTORY_SEPARATOR . "themes/{$activeTheme}/animations.css";
        $themeAnimationsJsFullPath = $rootPath . DIRECTORY_SEPARATOR . "themes/{$activeTheme}/animations.js";
        
        $themeCssVersion = asset_version($themeCssFullPath);
        $themeAnimationsCssVersion = asset_version($themeAnimationsCssFullPath);
        $themeAnimationsJsVersion = asset_version($themeAnimationsJsFullPath);
        
        // Theme CSS URL with cache busting
        $themeCssUrl = theme_css_url($activeTheme);
        if ($themeCssVersion) {
            $separator = str_contains($themeCssUrl, '?') ? '&' : '?';
            $themeCssUrl .= $separator . 'v=' . urlencode($themeCssVersion);
        }
        
        // Theme animations CSS URL with cache busting
        $themeAnimationsCssUrl = theme_animations_css_url($activeTheme);
        if ($themeAnimationsCssVersion) {
            $separator = str_contains($themeAnimationsCssUrl, '?') ? '&' : '?';
            $themeAnimationsCssUrl .= $separator . 'v=' . urlencode($themeAnimationsCssVersion);
        }
        
        // Theme animations JS URL with cache busting
        $themeAnimationsJsUrl = theme_animations_js_url($activeTheme);
        if ($themeAnimationsJsVersion) {
            $separator = str_contains($themeAnimationsJsUrl, '?') ? '&' : '?';
            $themeAnimationsJsUrl .= $separator . 'v=' . urlencode($themeAnimationsJsVersion);
        }
        ?>
        <link rel="stylesheet" href="<?= e($themeCssUrl) ?>">
        <link rel="stylesheet" href="<?= e($themeAnimationsCssUrl) ?>">
        <?= render_theme_css_variables() ?>
        <?php if (!empty($meta['extraHead'] ?? [])): ?>
            <?php foreach ($meta['extraHead'] as $headSnippet): ?>
                <?= $headSnippet ?>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php
        // Organization Schema
        if (!empty($siteSettings)) {
            echo schema_json_ld(schema_organization($siteSettings));
            echo PHP_EOL . '        ';
            echo schema_json_ld(schema_local_business($siteSettings));
        }
        
        // WebSite Schema
        echo PHP_EOL . '        ';
        echo schema_json_ld([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $metaTitle,
            'url' => url(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/search?q={search_term_string}'),
                'query-input' => 'required name=search_term_string',
            ],
        ]);
        ?>
    </head>
    <body class="<?= e($bodyClass) ?>" data-page-route="<?= e($currentRoute ?? '') ?>">
        <?php 
        // Use theme-specific partials if available, fallback to default
        $headerPath = theme_view_path('partials/header');
        $footerPath = theme_view_path('partials/footer');
        include $headerPath; 
        ?>
        <?= $content ?>
        <?php include $footerPath; ?>
        <div class="nav-overlay"></div>
        <script src="<?= e($themeAnimationsJsUrl) ?>" defer></script>
        <?php if (!empty($meta['scripts'] ?? [])): ?>
            <?php foreach ($meta['scripts'] as $script): ?>
                <script src="<?= e($script) ?>" defer></script>
            <?php endforeach; ?>
        <?php endif; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const backToTopBtn = document.querySelector('.floating-btn--top');
                if (!backToTopBtn) {
                    return;
                }

                const toggleVisibility = () => {
                    const shouldShow = window.scrollY > 320;
                    backToTopBtn.classList.toggle('is-visible', shouldShow);
                };

                backToTopBtn.addEventListener('click', () => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });

                toggleVisibility();
                window.addEventListener('scroll', toggleVisibility, { passive: true });
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const links = Array.from(document.querySelectorAll('[data-market-channel]')).filter(function (link) {
                    return !link.dataset.trackCta;
                });

                if (!links.length) {
                    return;
                }

                const endpoint = '/api/track/marketplace-click';
                const pageType = document.body.dataset.pageRoute || null;

                const sendPayload = function (payload) {
                    try {
                        const body = JSON.stringify(payload);

                        if (navigator.sendBeacon) {
                            const blob = new Blob([body], { type: 'application/json' });
                            navigator.sendBeacon(endpoint, blob);
                            return;
                        }

                        fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body,
                            keepalive: true,
                        }).catch(function () {
                            /* silent */
                        });
                    } catch (error) {
                        console.error('Marketplace tracking failed', error);
                    }
                };

                links.forEach(function (link) {
                    link.addEventListener('click', function () {
                        const dataset = link.dataset;
                        const payload = {
                            channel: (dataset.marketChannel || '').toLowerCase(),
                            page_type: dataset.marketPage || pageType,
                            context: dataset.marketContext || null,
                            label: dataset.marketLabel || link.getAttribute('aria-label') || link.textContent.trim(),
                            target_url: link.getAttribute('href'),
                            page_url: window.location.href,
                            referrer: document.referrer || null,
                            client_event_time: new Date().toISOString(),
                        };

                        sendPayload(payload);
                    }, { passive: true });
                });
            });
        </script>
    </body>
</html>
