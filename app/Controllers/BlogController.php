<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\AnalyticsRecorder;
use DateTimeImmutable;
use PDO;
use PDOException;
use Throwable;

/**
 * MUNG CMS V1
 * File: app/Controllers/BlogController.php
 * Author: Firmanda Himawan
 * Description: Frontend controller for listing and displaying blog content with engagement tracking.
 */

final class BlogController extends Controller
{
    public function index(): void
    {
        $settings = $this->siteSettings();
        $perPage = 9;
        $requestedPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $currentPage = $requestedPage > 0 ? $requestedPage : 1;

        $availableTags = $this->fetchBlogTags();
        $tagSlug = $this->normalizeTagSlug($_GET['tag'] ?? null);
        $activeTag = null;
        $tagName = null;

        foreach ($availableTags as $tagCandidate) {
            if ($tagCandidate['slug'] === $tagSlug) {
                $activeTag = $tagCandidate;
                $tagName = $tagCandidate['name'];
                break;
            }
        }

        $totalPosts = $this->countBlogPosts($tagName);
        $totalPages = $totalPosts > 0 ? (int) ceil($totalPosts / $perPage) : 1;
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $offset = ($currentPage - 1) * $perPage;
        $posts = $this->fetchBlogPosts($perPage, $offset, $tagName);

        $featuredPost = $posts[0] ?? null;
        $listPosts = $featuredPost ? array_slice($posts, 1) : $posts;

        $pageTitle = $tagName
            ? sprintf('Artikel %s | %s', mb_convert_case($tagName, MB_CASE_TITLE, 'UTF-8'), $settings['company_name'] ?? 'Aspal Emulsi')
            : 'Blog & Insight | ' . ($settings['company_name'] ?? 'Aspal Emulsi');

        $descriptionDefault = $tagName
            ? sprintf('Kumpulan insight dan studi kasus %s dari tim engineer Aspal Emulsi. Ikuti best practice slurry seal, waterproofing, serta pemeliharaan jalan.', $tagName)
            : 'Insight teknis, studi kasus, dan strategi aplikatif slurry seal, waterproofing, dan material aspal emulsi dari engineer Aspal Emulsi.';

        $canonical = $this->buildBlogUrl($currentPage, $tagSlug);
        $extraHead = [];
        if ($currentPage > 1) {
            $extraHead[] = '<link rel="prev" href="' . e($this->buildBlogUrl($currentPage - 1, $tagSlug)) . '">';
        }
        if ($currentPage < $totalPages) {
            $extraHead[] = '<link rel="next" href="' . e($this->buildBlogUrl($currentPage + 1, $tagSlug)) . '">';
        }

        try {
            $recorder = new AnalyticsRecorder($this->pdo);
            $recorder->recordContentView('blog_list', [
                'content_slug' => $tagSlug ? 'blog-' . $tagSlug : 'blog',
                'content_title' => $tagName
                    ? sprintf('Blog - %s', mb_convert_case($tagName, MB_CASE_TITLE, 'UTF-8'))
                    : 'Blog & Insight',
                'page_url' => $canonical,
                'metadata' => [
                    'page' => $currentPage,
                    'tag' => $tagSlug,
                ],
            ]);
        } catch (Throwable $throwable) {
            error_log('Blog list analytics failed: ' . $throwable->getMessage());
        }

        $this->render('blog/index', [
            'title' => $pageTitle,
            'meta' => [
                'description' => $descriptionDefault,
                'bodyClass' => 'page-blog-index',
                'canonical' => $canonical,
                'og:type' => 'collection',
                'og:url' => $canonical,
                'extraHead' => $extraHead,
            ],
            'posts' => $posts,
            'featuredPost' => $featuredPost,
            'listPosts' => $listPosts,
            'availableTags' => $availableTags,
            'activeTag' => $activeTag,
            'pagination' => [
                'current' => $currentPage,
                'total' => $totalPages,
                'perPage' => $perPage,
                'totalItems' => $totalPosts,
                'hasPrevious' => $currentPage > 1,
                'hasNext' => $currentPage < $totalPages,
                'previousUrl' => $currentPage > 1 ? $this->buildBlogUrl($currentPage - 1, $tagSlug) : null,
                'nextUrl' => $currentPage < $totalPages ? $this->buildBlogUrl($currentPage + 1, $tagSlug) : null,
                'baseUrl' => $this->buildBlogUrl(1, $tagSlug),
                'tagSlug' => $tagSlug,
            ],
            'currentRoute' => 'blog',
        ]);
    }

    public function show(string $slug): void
    {
        $post = $this->findPostBySlug($slug);

        if ($post === null) {
            abort(404, 'Artikel tidak ditemukan.');
            return;
        }

        $seo = $this->fetchSeoMeta('post', (int) $post['id']);
        $settings = $this->siteSettings();

        $title = $seo['meta_title'] ?? ($post['title'] . ' | ' . ($settings['company_name'] ?? 'Aspal Emulsi'));
        $metaDescription = $seo['meta_description'] ?? $this->excerpt($post['summary'] ?? $post['content'] ?? '', 160);
        $metaKeywords = $seo['meta_keywords'] ?? null;
        $metaImage = $post['cover_image_url'] ?? ($seo['og_image_url'] ?? ($settings['logo_url'] ?? asset('images/logo.png')));

        $relatedPosts = $this->fetchRelatedPosts((int) $post['id']);

        try {
            $recorder = new AnalyticsRecorder($this->pdo);
            $recorder->recordContentView('blog_post', [
                'content_id' => (int) $post['id'],
                'content_slug' => $post['slug'] ?? null,
                'content_title' => $post['title'] ?? null,
                'page_url' => url('blog/' . ($post['slug'] ?? '')),
                'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
            ]);
        } catch (Throwable $throwable) {
            error_log('Blog view analytics failed: ' . $throwable->getMessage());
        }

        $this->render('blog/show', [
            'title' => $title,
            'meta' => [
                'description' => $metaDescription,
                'keywords' => $metaKeywords,
                'image' => $metaImage,
                'bodyClass' => 'page-blog-detail',
            ],
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'currentRoute' => 'blog',
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchBlogPosts(int $limit = 12, int $offset = 0, ?string $tag = null): array
    {
        try {
            $sql = 'SELECT bp.id, bp.title, bp.slug, bp.summary, bp.cover_image_url, bp.hero_tag,
                           bp.published_at, bp.hero_reading_time,
                           u.full_name AS author
                    FROM blog_posts bp
                    LEFT JOIN users u ON u.id = bp.author_id
                    WHERE bp.status = "published"';

            if ($tag !== null) {
                $sql .= ' AND bp.hero_tag = :tag';
            }

            $sql .= ' ORDER BY COALESCE(bp.published_at, bp.created_at) DESC
                      LIMIT :limit OFFSET :offset';

            $statement = $this->pdo->prepare($sql);
            $statement->bindValue('limit', $limit, PDO::PARAM_INT);
            $statement->bindValue('offset', $offset, PDO::PARAM_INT);

            if ($tag !== null) {
                $statement->bindValue('tag', $tag, PDO::PARAM_STR);
            }

            $statement->execute();

            $results = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return array_map(function (array $row): array {
                $row['published_label'] = $this->formatDate($row['published_at'] ?? null);
                $row['cover_image_url'] = $row['cover_image_url'] ?: asset('images/blog-emulsi.jpg');

                return $row;
            }, $results);
        } catch (PDOException $exception) {
            error_log('Fetch blog posts failed: ' . $exception->getMessage());

            return [];
        }
    }

    private function countBlogPosts(?string $tag = null): int
    {
        try {
            $sql = 'SELECT COUNT(*) FROM blog_posts WHERE status = "published"';
            if ($tag !== null) {
                $sql .= ' AND hero_tag = :tag';
            }

            $statement = $this->pdo->prepare($sql);
            if ($tag !== null) {
                $statement->bindValue('tag', $tag, PDO::PARAM_STR);
            }

            $statement->execute();

            return (int) $statement->fetchColumn();
        } catch (PDOException $exception) {
            error_log('Count blog posts failed: ' . $exception->getMessage());

            return 0;
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchBlogTags(): array
    {
        try {
            $statement = $this->pdo->query(
                'SELECT hero_tag, COUNT(*) AS total
                 FROM blog_posts
                 WHERE status = "published" AND hero_tag IS NOT NULL AND hero_tag != ""
                 GROUP BY hero_tag
                 ORDER BY total DESC, hero_tag ASC'
            );

            $rows = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
            if (!$rows) {
                return [];
            }

            return array_map(function (array $row): array {
                $name = trim((string) $row['hero_tag']);
                $slug = $this->normalizeTagSlug($name);

                return [
                    'name' => $name,
                    'slug' => $slug,
                    'total' => (int) ($row['total'] ?? 0),
                ];
            }, $rows);
        } catch (PDOException $exception) {
            error_log('Fetch blog tags failed: ' . $exception->getMessage());

            return [];
        }
    }

    private function buildBlogUrl(int $page, ?string $tagSlug = null): string
    {
        $params = [];

        if ($tagSlug) {
            $params['tag'] = $tagSlug;
        }

        if ($page > 1) {
            $params['page'] = $page;
        }

        $query = $params ? '?' . http_build_query($params) : '';

        return url('/blog' . $query);
    }

    private function normalizeTagSlug(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/i', '-', $normalized) ?? $normalized;
        $normalized = trim($normalized, '-');

        return $normalized === '' ? null : $normalized;
    }

    private function findPostBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT bp.id, bp.title, bp.slug, bp.summary, bp.content, bp.cover_image_url, bp.cover_image_alt,
                    bp.hero_tag, bp.hero_author_override, bp.hero_reading_time, bp.intro_quote, bp.intro_quote_cite,
                    bp.table_of_contents_json, bp.timeline_json, bp.stat_blocks_json, bp.checklist_json,
                    bp.cta_heading, bp.cta_subheading, bp.cta_shopee_url, bp.cta_tokopedia_url, bp.cta_whatsapp_url,
                    bp.published_at,
                    u.full_name AS author
             FROM blog_posts bp
             LEFT JOIN users u ON u.id = bp.author_id
             WHERE bp.slug = :slug AND bp.status = "published"
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);

        $post = $statement->fetch(PDO::FETCH_ASSOC);
        if ($post === false) {
            return null;
        }

        // Auto-generate table of contents dari konten
        $content = $post['content'] ?? '';
        if (!empty($content)) {
            $autoToc = $this->generateTableOfContents($content);
            if (!empty($autoToc)) {
                // Inject anchor ID ke heading di konten
                $post['content'] = $this->injectHeadingAnchors($content);
                $post['table_of_contents'] = $autoToc;
            } else {
                // Fallback ke manual TOC jika tidak ada heading di konten
                $post['table_of_contents'] = $this->decodeKeyValueJson($post['table_of_contents_json'] ?? null, 'anchor');
            }
        } else {
            // Jika konten kosong, gunakan manual TOC
            $post['table_of_contents'] = $this->decodeKeyValueJson($post['table_of_contents_json'] ?? null, 'anchor');
        }

        $timelineData = $this->decodeKeyValueJsonWithLabels($post['timeline_json'] ?? null, 'value', 'Tahap', 'Deskripsi');
        $post['timeline'] = $timelineData['items'];
        $post['timeline_labels'] = $timelineData['labels'];

        $statBlocksData = $this->decodeKeyValueJsonWithLabels($post['stat_blocks_json'] ?? null, 'value', 'Label', 'Nilai');
        $post['stat_blocks'] = $statBlocksData['items'];
        $post['stat_blocks_labels'] = $statBlocksData['labels'];

        $checklistData = $this->decodeListJsonWithLabel($post['checklist_json'] ?? null);
        $post['checklist'] = $checklistData['items'];
        $post['checklist_label'] = $checklistData['label'];

        $post['author_display'] = $post['hero_author_override'] ?: ($post['author'] ?? 'Tim Aspal Emulsi');
        $post['published_label'] = $this->formatDate($post['published_at'] ?? null);

        return $post;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchRelatedPosts(int $excludeId, int $limit = 3): array
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT id, title, slug, hero_tag, cover_image_url
                 FROM blog_posts
                 WHERE status = "published" AND id != :id
                 ORDER BY COALESCE(published_at, created_at) DESC
                 LIMIT :limit'
            );
            $statement->bindValue('id', $excludeId, PDO::PARAM_INT);
            $statement->bindValue('limit', $limit, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $exception) {
            error_log('Fetch related posts failed: ' . $exception->getMessage());

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

    private function formatDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value);

            return $date->format('j F Y');
        } catch (Throwable $throwable) {
            error_log('Date formatting error: ' . $throwable->getMessage());

            return $value;
        }
    }

    /**
     * @param mixed $value
     * @return array<int,array<string,string>>
     */
    private function decodeKeyValueJson(mixed $value, string $valueKey = 'value'): array
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
            $val = trim((string) ($item[$valueKey] ?? ''));

            if ($label === '' && $val === '') {
                continue;
            }

            $items[] = ['label' => $label, $valueKey => $val];
        }

        return $items;
    }

    /**
     * Decode JSON dengan custom labels untuk key-value format
     * 
     * @param mixed $value JSON string
     * @param string $valueKey Key untuk value field (default: 'value')
     * @param string $defaultLeftLabel Default label kiri
     * @param string $defaultRightLabel Default label kanan
     * @return array{items: array<int,array<string,string>>, labels: array{left: string, right: string}}
     */
    private function decodeKeyValueJsonWithLabels(mixed $value, string $valueKey = 'value', string $defaultLeftLabel = 'Label', string $defaultRightLabel = 'Nilai'): array
    {
        $defaultLabels = [
            'left' => $defaultLeftLabel,
            'right' => $defaultRightLabel,
        ];

        if (!is_string($value) || $value === '') {
            return [
                'items' => [],
                'labels' => $defaultLabels,
            ];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [
                'items' => [],
                'labels' => $defaultLabels,
            ];
        }

        // Check if new format with columnLabels
        $labels = $defaultLabels;
        $items = [];

        if (isset($decoded['columnLabels']) && is_array($decoded['columnLabels'])) {
            $labels = [
                'left' => trim((string) ($decoded['columnLabels']['left'] ?? $defaultLabels['left'])) ?: $defaultLabels['left'],
                'right' => trim((string) ($decoded['columnLabels']['right'] ?? $defaultLabels['right'])) ?: $defaultLabels['right'],
            ];
            $items = $decoded['items'] ?? [];
        } else {
            // Old format - direct array
            $items = $decoded;
        }

        if (!is_array($items)) {
            $items = [];
        }

        $resultItems = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $val = trim((string) ($item[$valueKey] ?? ''));

            if ($label === '' && $val === '') {
                continue;
            }

            $resultItems[] = ['label' => $label, $valueKey => $val];
        }

        return [
            'items' => $resultItems,
            'labels' => $labels,
        ];
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
     * Decode JSON dengan custom label untuk list format
     * 
     * @param mixed $value JSON string
     * @return array{items: array<int,string>, label: string}
     */
    private function decodeListJsonWithLabel(mixed $value): array
    {
        $defaultLabel = 'Item';

        if (!is_string($value) || $value === '') {
            return [
                'items' => [],
                'label' => $defaultLabel,
            ];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [
                'items' => [],
                'label' => $defaultLabel,
            ];
        }

        // Check if new format with columnLabel
        $label = $defaultLabel;
        $items = [];

        if (isset($decoded['columnLabel'])) {
            $label = trim((string) ($decoded['columnLabel'] ?? $defaultLabel)) ?: $defaultLabel;
            $items = $decoded['items'] ?? [];
        } else {
            // Old format - direct array
            $items = $decoded;
        }

        if (!is_array($items)) {
            $items = [];
        }

        $resultItems = array_values(array_filter(array_map(static fn ($item) => trim((string) $item), $items), static fn ($item) => $item !== ''));

        return [
            'items' => $resultItems,
            'label' => $label,
        ];
    }

    /**
     * Generate table of contents otomatis dari heading di konten HTML
     * 
     * @param string $htmlContent Konten HTML artikel
     * @return array<int,array<string,string>> Array of ['label' => string, 'anchor' => string, 'level' => int]
     */
    private function generateTableOfContents(string $htmlContent): array
    {
        if (trim($htmlContent) === '') {
            return [];
        }

        $toc = [];
        $dom = new \DOMDocument();
        
        // Suppress warnings untuk HTML yang tidak valid
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        // Ambil semua heading H2 sampai H6
        $headings = $xpath->query('//h2 | //h3 | //h4 | //h5 | //h6');

        if ($headings === false || $headings->length === 0) {
            return [];
        }

        foreach ($headings as $heading) {
            $text = trim($heading->textContent ?? '');
            if ($text === '') {
                continue;
            }

            $tagName = $heading->nodeName;
            $level = (int) substr($tagName, 1); // Extract angka dari H2, H3, dll

            // Generate anchor dari text heading
            $anchor = $this->generateAnchorFromText($text);

            $toc[] = [
                'label' => $text,
                'anchor' => $anchor,
                'level' => $level,
            ];
        }

        return $toc;
    }

    /**
     * Inject anchor ID ke heading di konten HTML
     * 
     * @param string $htmlContent Konten HTML artikel
     * @return string Konten HTML dengan anchor ID di setiap heading
     */
    private function injectHeadingAnchors(string $htmlContent): string
    {
        if (trim($htmlContent) === '') {
            return $htmlContent;
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $headings = $xpath->query('//h2 | //h3 | //h4 | //h5 | //h6');

        if ($headings === false || $headings->length === 0) {
            return $htmlContent;
        }

        foreach ($headings as $heading) {
            $text = trim($heading->textContent ?? '');
            if ($text === '') {
                continue;
            }

            // Skip jika sudah punya ID
            if ($heading->hasAttribute('id')) {
                continue;
            }

            $anchor = $this->generateAnchorFromText($text);
            $heading->setAttribute('id', $anchor);
        }

        // Convert kembali ke HTML
        $html = $dom->saveHTML();
        
        // Hapus encoding declaration yang ditambahkan DOMDocument
        $html = preg_replace('/^<\?xml encoding="UTF-8"\?>\s*/', '', $html);
        
        return $html ?: $htmlContent;
    }

    /**
     * Generate anchor slug dari text heading
     * 
     * @param string $text Text heading
     * @return string Anchor slug
     */
    private function generateAnchorFromText(string $text): string
    {
        $originalText = $text;
        
        // Transliterate ke ASCII
        $text = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($text === false) {
            $text = mb_convert_encoding($originalText, 'ASCII', 'UTF-8');
        }

        // Convert ke lowercase
        $text = strtolower($text);

        // Replace spaces dan special chars dengan dash
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        // Jika kosong, generate random
        if ($text === '') {
            $text = 'heading-' . bin2hex(random_bytes(4));
        }

        return $text;
    }
}
