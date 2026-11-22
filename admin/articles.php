<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireAuth();

$pdo = getPDO();

/**
 * MUNG CMS V1
 * File: admin/articles.php
 * Author: Firmanda Himawan
 * Description: Editorial interface for creating, editing, and publishing blog articles.
 */

function slugify(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($transliterated !== false) {
        $value = $transliterated;
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value;
}

function normalizeDateTime(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    $value = str_replace('T', ' ', $value);

    try {
        $dateTime = new DateTime($value);
        return $dateTime->format('Y-m-d H:i:s');
    } catch (Throwable $throwable) {
        return null;
    }
}

function toInputDate(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    try {
        $dateTime = new DateTime($value);
        return $dateTime->format('Y-m-d\TH:i');
    } catch (Throwable $throwable) {
        return null;
    }
}

$errors = [];
$notices = [];

$validStatuses = ['draft', 'published', 'archived'];

$categoriesList = $pdo->query('SELECT id, name FROM blog_categories ORDER BY name ASC')->fetchAll() ?: [];

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$mode = $editId ? 'edit' : 'create';

$formData = [
    'title' => '',
    'slug' => '',
    'hero_tag' => '',
    'hero_author_override' => '',
    'hero_reading_time' => '',
    'summary' => '',
    'content' => '',
    'intro_quote' => '',
    'intro_quote_cite' => '',
    'timeline' => '',
    'timeline_label_left' => 'Tahap',
    'timeline_label_right' => 'Deskripsi',
    'stat_blocks' => '',
    'stat_blocks_label_left' => 'Label',
    'stat_blocks_label_right' => 'Nilai',
    'checklist' => '',
    'checklist_label' => 'Item',
    'status' => 'draft',
    'published_at' => '',
    'categories' => [],
    'cover_image_url' => '',
    'cover_image_alt' => '',
    'meta_title' => '',
    'meta_description' => '',
    'meta_keywords' => '',
    'cta_heading' => '',
    'cta_subheading' => '',
    'cta_shopee_url' => '',
    'cta_tokopedia_url' => '',
    'cta_whatsapp_url' => '',
];

if ($mode === 'edit') {
    $postStmt = $pdo->prepare(
        'SELECT
            id,
            author_id,
            title,
            slug,
            hero_tag,
            hero_author_override,
            hero_reading_time,
            summary,
            content,
            cover_image_url,
            cover_image_alt,
            intro_quote,
            intro_quote_cite,
            timeline_json,
            stat_blocks_json,
            checklist_json,
            cta_heading,
            cta_subheading,
            cta_shopee_url,
            cta_tokopedia_url,
            cta_whatsapp_url,
            status,
            published_at
         FROM blog_posts WHERE id = :id LIMIT 1'
    );
    $postStmt->execute(['id' => $editId]);
    $post = $postStmt->fetch();

    if ($post === false) {
        $mode = 'create';
        $editId = null;
        $notices[] = ['type' => 'warning', 'message' => 'Artikel tidak ditemukan.'];
    } else {
        $categoriesStmt = $pdo->prepare('SELECT category_id FROM blog_post_categories WHERE post_id = :post_id');
        $categoriesStmt->execute(['post_id' => $post['id']]);
        $selectedCategories = array_map('intval', array_column($categoriesStmt->fetchAll() ?: [], 'category_id'));

        $seoStmt = $pdo->prepare(
            'SELECT meta_title, meta_description, meta_keywords
             FROM seo_meta WHERE owner_type = "post" AND owner_id = :owner_id'
        );
        $seoStmt->execute(['owner_id' => $post['id']]);
        $seo = $seoStmt->fetch() ?: [];

        $timelineString = '';
        $timelineLabelLeft = 'Tahap';
        $timelineLabelRight = 'Deskripsi';
        if (!empty($post['timeline_json'])) {
            $decodedTimeline = json_decode((string) $post['timeline_json'], true);
            if (is_array($decodedTimeline)) {
                // Check if new format with columnLabels
                if (isset($decodedTimeline['columnLabels']) && is_array($decodedTimeline['columnLabels'])) {
                    $timelineLabelLeft = trim((string) ($decodedTimeline['columnLabels']['left'] ?? 'Tahap')) ?: 'Tahap';
                    $timelineLabelRight = trim((string) ($decodedTimeline['columnLabels']['right'] ?? 'Deskripsi')) ?: 'Deskripsi';
                    $items = $decodedTimeline['items'] ?? [];
                } else {
                    // Old format - direct array
                    $items = $decodedTimeline;
                }
                
                $lines = [];
                foreach ($items as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    $label = trim((string) ($entry['label'] ?? ''));
                    $value = trim((string) ($entry['value'] ?? ''));
                    if ($label === '' && $value === '') {
                        continue;
                    }
                    $lines[] = $label . ' | ' . $value;
                }
                $timelineString = implode(PHP_EOL, $lines);
            }
        }

        $statsString = '';
        $statBlocksLabelLeft = 'Label';
        $statBlocksLabelRight = 'Nilai';
        if (!empty($post['stat_blocks_json'])) {
            $decodedStats = json_decode((string) $post['stat_blocks_json'], true);
            if (is_array($decodedStats)) {
                // Check if new format with columnLabels
                if (isset($decodedStats['columnLabels']) && is_array($decodedStats['columnLabels'])) {
                    $statBlocksLabelLeft = trim((string) ($decodedStats['columnLabels']['left'] ?? 'Label')) ?: 'Label';
                    $statBlocksLabelRight = trim((string) ($decodedStats['columnLabels']['right'] ?? 'Nilai')) ?: 'Nilai';
                    $items = $decodedStats['items'] ?? [];
                } else {
                    // Old format - direct array
                    $items = $decodedStats;
                }
                
                $lines = [];
                foreach ($items as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    $label = trim((string) ($entry['label'] ?? ''));
                    $value = trim((string) ($entry['value'] ?? ''));
                    if ($label === '' && $value === '') {
                        continue;
                    }
                    $lines[] = $label . ' | ' . $value;
                }
                $statsString = implode(PHP_EOL, $lines);
            }
        }

        $checklistString = '';
        $checklistLabel = 'Item';
        if (!empty($post['checklist_json'])) {
            $decodedChecklist = json_decode((string) $post['checklist_json'], true);
            if (is_array($decodedChecklist)) {
                // Check if new format with columnLabel
                if (isset($decodedChecklist['columnLabel'])) {
                    $checklistLabel = trim((string) ($decodedChecklist['columnLabel'] ?? 'Item')) ?: 'Item';
                    $items = $decodedChecklist['items'] ?? [];
                } else {
                    // Old format - direct array
                    $items = $decodedChecklist;
                }
                
                $lines = [];
                foreach ($items as $item) {
                    $item = trim((string) $item);
                    if ($item === '') {
                        continue;
                    }
                    $lines[] = $item;
                }
                $checklistString = implode(PHP_EOL, $lines);
            }
        }

        $formData = [
            'title' => (string) $post['title'],
            'slug' => (string) $post['slug'],
            'hero_tag' => (string) ($post['hero_tag'] ?? ''),
            'hero_author_override' => (string) ($post['hero_author_override'] ?? ''),
            'hero_reading_time' => (string) ($post['hero_reading_time'] ?? ''),
            'summary' => (string) ($post['summary'] ?? ''),
            'content' => (string) ($post['content'] ?? ''),
            'intro_quote' => (string) ($post['intro_quote'] ?? ''),
            'intro_quote_cite' => (string) ($post['intro_quote_cite'] ?? ''),
            'timeline' => $timelineString,
            'timeline_label_left' => $timelineLabelLeft,
            'timeline_label_right' => $timelineLabelRight,
            'stat_blocks' => $statsString,
            'stat_blocks_label_left' => $statBlocksLabelLeft,
            'stat_blocks_label_right' => $statBlocksLabelRight,
            'checklist' => $checklistString,
            'checklist_label' => $checklistLabel,
            'status' => in_array($post['status'], $validStatuses, true) ? $post['status'] : 'draft',
            'published_at' => toInputDate($post['published_at'] ?? null) ?? '',
            'categories' => $selectedCategories,
            'cover_image_url' => (string) ($post['cover_image_url'] ?? ''),
            'cover_image_alt' => (string) ($post['cover_image_alt'] ?? ''),
            'meta_title' => (string) ($seo['meta_title'] ?? ''),
            'meta_description' => (string) ($seo['meta_description'] ?? ''),
            'meta_keywords' => (string) ($seo['meta_keywords'] ?? ''),
            'cta_heading' => (string) ($post['cta_heading'] ?? ''),
            'cta_subheading' => (string) ($post['cta_subheading'] ?? ''),
            'cta_shopee_url' => (string) ($post['cta_shopee_url'] ?? ''),
            'cta_tokopedia_url' => (string) ($post['cta_tokopedia_url'] ?? ''),
            'cta_whatsapp_url' => (string) ($post['cta_whatsapp_url'] ?? ''),
        ];
    }
}

$currentUser = adminCurrentUser();
$currentUserId = $currentUser['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $requestId = isset($_POST['id']) ? (int) $_POST['id'] : null;

    if ($action === 'delete') {
        if ($requestId === null) {
            $errors[] = 'ID artikel tidak valid.';
        } else {
            try {
                $pdo->beginTransaction();

                $deletePivot = $pdo->prepare('DELETE FROM blog_post_categories WHERE post_id = :id');
                $deletePivot->execute(['id' => $requestId]);

                $deleteSeo = $pdo->prepare('DELETE FROM seo_meta WHERE owner_type = "post" AND owner_id = :id');
                $deleteSeo->execute(['id' => $requestId]);

                $deleteStmt = $pdo->prepare('DELETE FROM blog_posts WHERE id = :id');
                $deleteStmt->execute(['id' => $requestId]);

                $pdo->commit();
                admin_redirect('articles', ['notice' => 'deleted']);
                exit;
            } catch (Throwable $throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Terjadi kesalahan: ' . $throwable->getMessage();
            }
        }
    } else {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $heroTag = trim($_POST['hero_tag'] ?? '');
        $heroAuthorOverride = trim($_POST['hero_author_override'] ?? '');
        $heroReadingTime = trim($_POST['hero_reading_time'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $content = $_POST['content'] ?? '';
        $introQuote = trim($_POST['intro_quote'] ?? '');
        $introQuoteCite = trim($_POST['intro_quote_cite'] ?? '');
        $timelineInput = trim($_POST['timeline'] ?? '');
        $timelineLabelLeft = trim($_POST['timeline_label_left'] ?? 'Tahap') ?: 'Tahap';
        $timelineLabelRight = trim($_POST['timeline_label_right'] ?? 'Deskripsi') ?: 'Deskripsi';
        $statBlocksInput = trim($_POST['stat_blocks'] ?? '');
        $statBlocksLabelLeft = trim($_POST['stat_blocks_label_left'] ?? 'Label') ?: 'Label';
        $statBlocksLabelRight = trim($_POST['stat_blocks_label_right'] ?? 'Nilai') ?: 'Nilai';
        $checklistInput = trim($_POST['checklist'] ?? '');
        $checklistLabel = trim($_POST['checklist_label'] ?? 'Item') ?: 'Item';
        $status = $_POST['status'] ?? 'draft';
        $publishedAtInput = $_POST['published_at'] ?? '';
        $selectedCategories = array_values(array_unique(array_filter(array_map('intval', $_POST['categories'] ?? []))));
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDescription = trim($_POST['meta_description'] ?? '');
        $metaKeywords = trim($_POST['meta_keywords'] ?? '');
        $existingCover = trim($_POST['existing_cover'] ?? '');
        $coverImageAlt = trim($_POST['cover_image_alt'] ?? '');
        $ctaHeading = trim($_POST['cta_heading'] ?? '');
        $ctaSubheading = trim($_POST['cta_subheading'] ?? '');
        $ctaShopeeUrl = trim($_POST['cta_shopee_url'] ?? '');
        $ctaTokopediaUrl = trim($_POST['cta_tokopedia_url'] ?? '');
        $ctaWhatsappUrl = trim($_POST['cta_whatsapp_url'] ?? '');

        if ($slug === '' && $title !== '') {
            $slug = slugify($title);
        }

        if ($metaTitle === '' && $title !== '') {
            $metaTitle = $title . ' | Aspal Emulsi';
        }

        if ($metaDescription === '' && $summary !== '') {
            $plainSummary = strip_tags($summary);
            $metaDescription = function_exists('mb_substr')
                ? mb_substr($plainSummary, 0, 155)
                : substr($plainSummary, 0, 155);
        }

        if ($metaKeywords === '' && $title !== '') {
            $metaKeywords = implode(', ', array_filter(['aspal emulsi', 'material jalan', $title]));
        }

        foreach (
            [
                ['label' => 'CTA Shopee', 'value' => $ctaShopeeUrl],
                ['label' => 'CTA Tokopedia', 'value' => $ctaTokopediaUrl],
                ['label' => 'CTA WhatsApp', 'value' => $ctaWhatsappUrl],
            ] as $ctaLink
        ) {
            if ($ctaLink['value'] !== '' && !filter_var($ctaLink['value'], FILTER_VALIDATE_URL)) {
                $errors[] = $ctaLink['label'] . ' tidak valid.';
            }
        }

        $timelineArray = [];
        if ($timelineInput !== '') {
            foreach (preg_split('/\r\n|\n|\r/', $timelineInput) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = array_map('trim', explode('|', $line, 2));
                if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
                    $errors[] = sprintf('Format timeline harus "%s | %s".', $timelineLabelLeft, $timelineLabelRight);
                    continue;
                }
                $timelineArray[] = [
                    'label' => $parts[0],
                    'value' => $parts[1],
                ];
            }
        }

        $statBlocksArray = [];
        if ($statBlocksInput !== '') {
            foreach (preg_split('/\r\n|\n|\r/', $statBlocksInput) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = array_map('trim', explode('|', $line, 2));
                if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
                    $errors[] = sprintf('Format statistik harus "%s | %s".', $statBlocksLabelLeft, $statBlocksLabelRight);
                    continue;
                }
                $statBlocksArray[] = [
                    'label' => $parts[0],
                    'value' => $parts[1],
                ];
            }
        }

        $checklistArray = [];
        if ($checklistInput !== '') {
            foreach (preg_split('/\r\n|\n|\r/', $checklistInput) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $checklistArray[] = $line;
            }
        }

        if ($title === '') {
            $errors[] = 'Judul wajib diisi.';
        }

        if ($slug === '') {
            $errors[] = 'Slug wajib diisi.';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            $errors[] = 'Slug hanya boleh berisi huruf kecil, angka, dan tanda minus.';
        }

        if (!in_array($status, $validStatuses, true)) {
            $errors[] = 'Status artikel tidak valid.';
        }

        $normalizedPublishedAt = normalizeDateTime($publishedAtInput);
        if ($status === 'published' && $normalizedPublishedAt === null) {
            $normalizedPublishedAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        }

        if ($status !== 'published') {
            $normalizedPublishedAt = null;
        }

        $uploadDir = dirname(__DIR__) . '/uploads';
        $uploadedImage = adminHandleImageUpload($_FILES['cover_image'] ?? null, 'post', $uploadDir, $errors);
        if ($uploadedImage !== null) {
            $coverImageUrl = $uploadedImage;
        } elseif ($action === 'update' && $existingCover !== '') {
            // Keep existing image if no new upload
            $coverImageUrl = $existingCover;
        } else {
            // For new posts, start with empty
            $coverImageUrl = '';
        }

        $formData = [
            'title' => $title,
            'slug' => $slug,
            'hero_tag' => $heroTag,
            'hero_author_override' => $heroAuthorOverride,
            'hero_reading_time' => $heroReadingTime,
            'summary' => $summary,
            'content' => $content,
            'intro_quote' => $introQuote,
            'intro_quote_cite' => $introQuoteCite,
            'timeline' => $timelineInput,
            'timeline_label_left' => $timelineLabelLeft,
            'timeline_label_right' => $timelineLabelRight,
            'stat_blocks' => $statBlocksInput,
            'stat_blocks_label_left' => $statBlocksLabelLeft,
            'stat_blocks_label_right' => $statBlocksLabelRight,
            'checklist' => $checklistInput,
            'checklist_label' => $checklistLabel,
            'status' => $status,
            'published_at' => $normalizedPublishedAt ? toInputDate($normalizedPublishedAt) : '',
            'categories' => $selectedCategories,
            'cover_image_url' => $coverImageUrl,
            'cover_image_alt' => $coverImageAlt,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
            'cta_heading' => $ctaHeading,
            'cta_subheading' => $ctaSubheading,
            'cta_shopee_url' => $ctaShopeeUrl,
            'cta_tokopedia_url' => $ctaTokopediaUrl,
            'cta_whatsapp_url' => $ctaWhatsappUrl,
        ];

        if ($errors === []) {
            try {
                $pdo->beginTransaction();

                if ($action === 'update') {
                    if ($requestId === null) {
                        throw new RuntimeException('ID artikel tidak valid.');
                    }

                    $slugCheckStmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = :slug AND id != :currentId LIMIT 1');
                    $slugCheckStmt->execute([
                        'slug' => $slug,
                        'currentId' => $requestId,
                    ]);
                } else {
                    $slugCheckStmt = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = :slug LIMIT 1');
                    $slugCheckStmt->execute(['slug' => $slug]);
                }

                if ($slugCheckStmt->fetch()) {
                    $pdo->rollBack();
                    $errors[] = 'Slug artikel sudah digunakan.';
                } else {
                    if ($action === 'update') {
                        $updateStmt = $pdo->prepare(
                            'UPDATE blog_posts
                             SET title = :title,
                                 slug = :slug,
                                 hero_tag = :hero_tag,
                                 hero_author_override = :hero_author_override,
                                 hero_reading_time = :hero_reading_time,
                                 summary = :summary,
                                 content = :content,
                                 cover_image_url = :cover_image_url,
                                 cover_image_alt = :cover_image_alt,
                                 intro_quote = :intro_quote,
                                 intro_quote_cite = :intro_quote_cite,
                                 timeline_json = :timeline_json,
                                 stat_blocks_json = :stat_blocks_json,
                                 checklist_json = :checklist_json,
                                 cta_heading = :cta_heading,
                                 cta_subheading = :cta_subheading,
                                 cta_shopee_url = :cta_shopee_url,
                                 cta_tokopedia_url = :cta_tokopedia_url,
                                 cta_whatsapp_url = :cta_whatsapp_url,
                                 status = :status,
                                 published_at = :published_at,
                                 updated_at = NOW()
                             WHERE id = :id'
                        );
                        $updateStmt->execute([
                            'id' => $requestId,
                            'title' => $title,
                            'slug' => $slug,
                            'hero_tag' => $heroTag !== '' ? $heroTag : null,
                            'hero_author_override' => $heroAuthorOverride !== '' ? $heroAuthorOverride : null,
                            'hero_reading_time' => $heroReadingTime !== '' ? $heroReadingTime : null,
                            'summary' => $summary !== '' ? $summary : null,
                            'content' => $content !== '' ? $content : null,
                            'cover_image_url' => $coverImageUrl !== '' ? $coverImageUrl : null,
                            'cover_image_alt' => $coverImageAlt !== '' ? $coverImageAlt : null,
                            'intro_quote' => $introQuote !== '' ? $introQuote : null,
                            'intro_quote_cite' => $introQuoteCite !== '' ? $introQuoteCite : null,
                            'timeline_json' => $timelineArray !== [] ? json_encode([
                                'columnLabels' => [
                                    'left' => $timelineLabelLeft,
                                    'right' => $timelineLabelRight,
                                ],
                                'items' => $timelineArray,
                            ], JSON_UNESCAPED_UNICODE) : null,
                            'stat_blocks_json' => $statBlocksArray !== [] ? json_encode([
                                'columnLabels' => [
                                    'left' => $statBlocksLabelLeft,
                                    'right' => $statBlocksLabelRight,
                                ],
                                'items' => $statBlocksArray,
                            ], JSON_UNESCAPED_UNICODE) : null,
                            'checklist_json' => $checklistArray !== [] ? json_encode([
                                'columnLabel' => $checklistLabel,
                                'items' => $checklistArray,
                            ], JSON_UNESCAPED_UNICODE) : null,
                            'cta_heading' => $ctaHeading !== '' ? $ctaHeading : null,
                            'cta_subheading' => $ctaSubheading !== '' ? $ctaSubheading : null,
                            'cta_shopee_url' => $ctaShopeeUrl !== '' ? $ctaShopeeUrl : null,
                            'cta_tokopedia_url' => $ctaTokopediaUrl !== '' ? $ctaTokopediaUrl : null,
                            'cta_whatsapp_url' => $ctaWhatsappUrl !== '' ? $ctaWhatsappUrl : null,
                            'status' => $status,
                            'published_at' => $normalizedPublishedAt,
                        ]);

                        $pivotDelete = $pdo->prepare('DELETE FROM blog_post_categories WHERE post_id = :post_id');
                        $pivotDelete->execute(['post_id' => $requestId]);

                        $postId = $requestId;
                    } else {
                        $insertStmt = $pdo->prepare(
                            'INSERT INTO blog_posts (
                                author_id,
                                title,
                                slug,
                                hero_tag,
                                hero_author_override,
                                hero_reading_time,
                                summary,
                                content,
                                cover_image_url,
                                cover_image_alt,
                                intro_quote,
                                intro_quote_cite,
                                timeline_json,
                                stat_blocks_json,
                                checklist_json,
                                cta_heading,
                                cta_subheading,
                                cta_shopee_url,
                                cta_tokopedia_url,
                                cta_whatsapp_url,
                                status,
                                published_at
                             )
                             VALUES (
                                :author_id,
                                :title,
                                :slug,
                                :hero_tag,
                                :hero_author_override,
                                :hero_reading_time,
                                :summary,
                                :content,
                                :cover_image_url,
                                :cover_image_alt,
                                :intro_quote,
                                :intro_quote_cite,
                                :timeline_json,
                                :stat_blocks_json,
                                :checklist_json,
                                :cta_heading,
                                :cta_subheading,
                                :cta_shopee_url,
                                :cta_tokopedia_url,
                                :cta_whatsapp_url,
                                :status,
                                :published_at
                             )'
                        );
                        $insertStmt->execute([
                            'author_id' => $currentUserId,
                            'title' => $title,
                            'slug' => $slug,
                            'hero_tag' => $heroTag !== '' ? $heroTag : null,
                            'hero_author_override' => $heroAuthorOverride !== '' ? $heroAuthorOverride : null,
                            'hero_reading_time' => $heroReadingTime !== '' ? $heroReadingTime : null,
                            'summary' => $summary !== '' ? $summary : null,
                            'content' => $content !== '' ? $content : null,
                            'cover_image_url' => $coverImageUrl !== '' ? $coverImageUrl : null,
                            'cover_image_alt' => $coverImageAlt !== '' ? $coverImageAlt : null,
                            'intro_quote' => $introQuote !== '' ? $introQuote : null,
                            'intro_quote_cite' => $introQuoteCite !== '' ? $introQuoteCite : null,
                            'timeline_json' => $timelineArray !== [] ? json_encode([
                                'columnLabels' => [
                                    'left' => $timelineLabelLeft,
                                    'right' => $timelineLabelRight,
                                ],
                                'items' => $timelineArray,
                            ], JSON_UNESCAPED_UNICODE) : null,
                            'stat_blocks_json' => $statBlocksArray !== [] ? json_encode([
                                'columnLabels' => [
                                    'left' => $statBlocksLabelLeft,
                                    'right' => $statBlocksLabelRight,
                                ],
                                'items' => $statBlocksArray,
                            ], JSON_UNESCAPED_UNICODE) : null,
                            'checklist_json' => $checklistArray !== [] ? json_encode([
                                'columnLabel' => $checklistLabel,
                                'items' => $checklistArray,
                            ], JSON_UNESCAPED_UNICODE) : null,
                            'cta_heading' => $ctaHeading !== '' ? $ctaHeading : null,
                            'cta_subheading' => $ctaSubheading !== '' ? $ctaSubheading : null,
                            'cta_shopee_url' => $ctaShopeeUrl !== '' ? $ctaShopeeUrl : null,
                            'cta_tokopedia_url' => $ctaTokopediaUrl !== '' ? $ctaTokopediaUrl : null,
                            'cta_whatsapp_url' => $ctaWhatsappUrl !== '' ? $ctaWhatsappUrl : null,
                            'status' => $status,
                            'published_at' => $normalizedPublishedAt,
                        ]);

                        $postId = (int) $pdo->lastInsertId();
                    }

                    if ($selectedCategories !== []) {
                        $pivotInsert = $pdo->prepare('INSERT INTO blog_post_categories (post_id, category_id) VALUES (:post_id, :category_id)');
                        foreach ($selectedCategories as $categoryId) {
                            $pivotInsert->execute([
                                'post_id' => $postId,
                                'category_id' => $categoryId,
                            ]);
                        }
                    }

                    $seoStmt = $pdo->prepare(
                        'INSERT INTO seo_meta (owner_type, owner_id, meta_title, meta_description, meta_keywords)
                         VALUES ("post", :owner_id, :meta_title, :meta_description, :meta_keywords)
                         ON DUPLICATE KEY UPDATE meta_title = VALUES(meta_title), meta_description = VALUES(meta_description), meta_keywords = VALUES(meta_keywords)'
                    );
                    $seoStmt->execute([
                        'owner_id' => $postId,
                        'meta_title' => $metaTitle !== '' ? $metaTitle : null,
                        'meta_description' => $metaDescription !== '' ? $metaDescription : null,
                        'meta_keywords' => $metaKeywords !== '' ? $metaKeywords : null,
                    ]);

                    $pdo->commit();
                    $redirectNotice = $action === 'update' ? 'updated' : 'created';
                    admin_redirect('articles', ['notice' => $redirectNotice]);
                    exit;
                }
            } catch (Throwable $throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Terjadi kesalahan: ' . $throwable->getMessage();
            }
        }
    }
}

if (isset($_GET['notice'])) {
    $noticeKey = $_GET['notice'];
    $messages = [
        'created' => 'Artikel berhasil diterbitkan.',
        'updated' => 'Artikel berhasil diperbarui.',
        'deleted' => 'Artikel berhasil dihapus.',
    ];

    if (isset($messages[$noticeKey])) {
        $notices[] = ['type' => 'success', 'message' => $messages[$noticeKey]];
    }
}

$postsStmt = $pdo->query(
    'SELECT bp.id, bp.title, bp.slug, bp.status, bp.published_at, bp.updated_at, bp.cover_image_url,
            u.full_name AS author_name,
            GROUP_CONCAT(DISTINCT bc.name ORDER BY bc.name SEPARATOR ", ") AS category_names
     FROM blog_posts bp
     LEFT JOIN users u ON u.id = bp.author_id
     LEFT JOIN blog_post_categories bpc ON bpc.post_id = bp.id
     LEFT JOIN blog_categories bc ON bc.id = bpc.category_id
     GROUP BY bp.id
     ORDER BY COALESCE(bp.published_at, bp.created_at) DESC'
);
$posts = $postsStmt->fetchAll() ?: [];

$viewTotals = [];
$view30Days = [];
$totalViews30Days = 0;

try {
    $viewsStmt = $pdo->query(
        'SELECT content_id, COUNT(*) AS total
         FROM content_view_events
         WHERE content_type = "blog_post"
         GROUP BY content_id'
    );

    foreach ($viewsStmt->fetchAll() ?: [] as $row) {
        if (!isset($row['content_id'])) {
            continue;
        }
        $viewTotals[(int) $row['content_id']] = (int) ($row['total'] ?? 0);
    }

    $views30Stmt = $pdo->query(
        'SELECT content_id, COUNT(*) AS total
         FROM content_view_events
         WHERE content_type = "blog_post" AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY content_id'
    );

    foreach ($views30Stmt->fetchAll() ?: [] as $row) {
        if (!isset($row['content_id'])) {
            continue;
        }

        $contentId = (int) $row['content_id'];
        $count = (int) ($row['total'] ?? 0);
        $view30Days[$contentId] = $count;
        $totalViews30Days += $count;
    }
} catch (Throwable $throwable) {
    error_log('Fetch blog post views failed: ' . $throwable->getMessage());
}

foreach ($posts as &$postRow) {
    $postId = (int) ($postRow['id'] ?? 0);
    $postRow['views_total'] = $viewTotals[$postId] ?? 0;
    $postRow['views_30d'] = $view30Days[$postId] ?? 0;
}
unset($postRow);

$statusBreakdown = [
    'draft' => 0,
    'published' => 0,
    'archived' => 0,
];

foreach ($posts as $postRow) {
    $postStatus = (string) ($postRow['status'] ?? '');
    if (array_key_exists($postStatus, $statusBreakdown)) {
        $statusBreakdown[$postStatus]++;
    }
}

$totalPosts = count($posts);

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Kelola Artikel | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">
        <script src="app.js" defer></script>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'articles';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Kelola Artikel',
                    'pageDescription' => 'Susun konten blog, atur status publikasi, dan optimalkan SEO.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Konten'],
                        ['label' => 'Artikel'],
                    ],
                    'quickActions' => [
                        [
                            'label' => 'Kelola Kategori',
                            'href' => admin_url('categories'),
                            'variant' => 'ghost',
                        ],
                    ],
                ]);
            ?>
            <div class="workspace-content">
                <section class="workspace-meta">
                    <span class="status-pill">Total Artikel: <?= number_format($totalPosts) ?></span>
                    <span class="chip chip--warning">Draft <?= number_format($statusBreakdown['draft']) ?></span>
                    <span class="chip chip--success">Published <?= number_format($statusBreakdown['published']) ?></span>
                    <span class="chip chip--neutral">Archived <?= number_format($statusBreakdown['archived']) ?></span>
                    <span class="chip chip--default">View 30 Hari: <?= number_format($totalViews30Days) ?></span>
                </section>

                <section class="card">
                <header>
                    <h2><?= $mode === 'edit' ? 'Edit Artikel' : 'Artikel Baru' ?></h2>
                </header>
                <form method="post" class="form-grid form-article" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">
                    <?php if ($mode === 'edit' && $editId !== null): ?>
                        <input type="hidden" name="id" value="<?= (int) $editId ?>">
                    <?php endif; ?>
                    <label class="form-field">
                        <span>Judul</span>
                        <input type="text" name="title" required value="<?= htmlspecialchars($formData['title'], ENT_QUOTES) ?>" placeholder="Judul artikel">
                    </label>
                    <label class="form-field">
                        <span>Slug</span>
                        <input type="text" name="slug" value="<?= htmlspecialchars($formData['slug'], ENT_QUOTES) ?>" placeholder="judul-artikel">
                        <small>Slug otomatis dari judul jika kosong.</small>
                    </label>
                    <label class="form-field">
                        <span>Hero Tag</span>
                        <input type="text" name="hero_tag" value="<?= htmlspecialchars($formData['hero_tag'], ENT_QUOTES) ?>" placeholder="Contoh: Perawatan">
                    </label>
                    <label class="form-field">
                        <span>Hero Author Override</span>
                        <input type="text" name="hero_author_override" value="<?= htmlspecialchars($formData['hero_author_override'], ENT_QUOTES) ?>" placeholder="Opsional, contoh: Tim Engineer Aspal Emulsi">
                        <small>Kalau dikosongkan, sistem pakai nama user penulis.</small>
                    </label>
                    <label class="form-field">
                        <span>Durasi Baca</span>
                        <input type="text" name="hero_reading_time" value="<?= htmlspecialchars($formData['hero_reading_time'], ENT_QUOTES) ?>" placeholder="Contoh: 7 menit">
                    </label>
                    <label class="form-field">
                        <span>Status</span>
                        <select name="status">
                            <?php foreach ($validStatuses as $statusOption): ?>
                                <option value="<?= $statusOption ?>" <?= $formData['status'] === $statusOption ? 'selected' : '' ?>><?= ucfirst($statusOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="form-field">
                        <span>Tanggal Terbit</span>
                        <input type="datetime-local" name="published_at" value="<?= htmlspecialchars($formData['published_at'], ENT_QUOTES) ?>">
                        <small>Diisi otomatis saat status "published" tanpa tanggal.</small>
                    </label>
                    <label class="form-field full">
                        <span>Ringkasan</span>
                        <textarea name="summary" rows="3" placeholder="Teaser / intro artikel"><?= htmlspecialchars($formData['summary'], ENT_QUOTES) ?></textarea>
                    </label>
                    <label class="form-field full">
                        <span>Quote Pembuka</span>
                        <textarea name="intro_quote" rows="2" placeholder="Kutipan penting untuk blokquote pembuka"><?= htmlspecialchars($formData['intro_quote'], ENT_QUOTES) ?></textarea>
                    </label>
                    <label class="form-field">
                        <span>Nama Sumber Quote</span>
                        <input type="text" name="intro_quote_cite" value="<?= htmlspecialchars($formData['intro_quote_cite'], ENT_QUOTES) ?>" placeholder="Contoh: Fajar — Project Lead Kampus ABC">
                    </label>
                    <label class="form-field full">
                        <span>Konten</span>
                        <textarea id="content-editor" name="content" rows="10"></textarea>
                        <small style="display: block; margin-top: 8px; color: var(--text-muted);">
                            <strong>Catatan:</strong> Daftar isi akan otomatis dibuat dari heading (H2, H3, H4, dll) di konten. Pastikan menggunakan heading dengan benar untuk menghasilkan daftar isi yang rapi.
                        </small>
                    </label>
                    <div class="form-field-group">
                        <label class="form-field">
                            <span>Label Kolom Kiri Timeline</span>
                            <input type="text" name="timeline_label_left" value="<?= htmlspecialchars($formData['timeline_label_left'], ENT_QUOTES) ?>" placeholder="Tahap">
                        </label>
                        <label class="form-field">
                            <span>Label Kolom Kanan Timeline</span>
                            <input type="text" name="timeline_label_right" value="<?= htmlspecialchars($formData['timeline_label_right'], ENT_QUOTES) ?>" placeholder="Deskripsi">
                        </label>
                    </div>
                    <label class="form-field full">
                        <span>Timeline (1 per baris, format: <?= htmlspecialchars($formData['timeline_label_left'], ENT_QUOTES) ?> | <?= htmlspecialchars($formData['timeline_label_right'], ENT_QUOTES) ?>)</span>
                        <textarea name="timeline" rows="4" placeholder="Day 0 (Malam) | Briefing dengan pihak kampus + pemasangan signage
Day 1 (Pagi) | Pembersihan permukaan"><?= htmlspecialchars($formData['timeline'], ENT_QUOTES) ?></textarea>
                    </label>
                    <div class="form-field-group">
                        <label class="form-field">
                            <span>Label Kolom Kiri Statistik</span>
                            <input type="text" name="stat_blocks_label_left" value="<?= htmlspecialchars($formData['stat_blocks_label_left'], ENT_QUOTES) ?>" placeholder="Label">
                        </label>
                        <label class="form-field">
                            <span>Label Kolom Kanan Statistik</span>
                            <input type="text" name="stat_blocks_label_right" value="<?= htmlspecialchars($formData['stat_blocks_label_right'], ENT_QUOTES) ?>" placeholder="Nilai">
                        </label>
                    </div>
                    <label class="form-field full">
                        <span>Statistik Highlight (1 per baris, format: <?= htmlspecialchars($formData['stat_blocks_label_left'], ENT_QUOTES) ?> | <?= htmlspecialchars($formData['stat_blocks_label_right'], ENT_QUOTES) ?>)</span>
                        <textarea name="stat_blocks" rows="3" placeholder="Luas Area | 12.000 m²
Downtime | 46 jam"><?= htmlspecialchars($formData['stat_blocks'], ENT_QUOTES) ?></textarea>
                    </label>
                    <label class="form-field">
                        <span>Label Kolom Checklist</span>
                        <input type="text" name="checklist_label" value="<?= htmlspecialchars($formData['checklist_label'], ENT_QUOTES) ?>" placeholder="Item">
                    </label>
                    <label class="form-field full">
                        <span>Checklist Setelah Pekerjaan (1 per baris)</span>
                        <textarea name="checklist" rows="3" placeholder="Bersihkan butiran pasir lepas tiap pagi
Cek drainase bebas sampah tiap pekan"><?= htmlspecialchars($formData['checklist'], ENT_QUOTES) ?></textarea>
                    </label>
                    <label class="form-field full">
                        <span>Kategori</span>
                        <div class="chip-list">
                            <?php if ($categoriesList === []): ?>
                                <p class="empty-hint">Belum ada kategori. Tambahkan di modul kategori.</p>
                            <?php else: ?>
                                <?php foreach ($categoriesList as $category): ?>
                                    <label class="chip">
                                        <input type="checkbox" name="categories[]" value="<?= (int) $category['id'] ?>" <?= in_array((int) $category['id'], $formData['categories'], true) ? 'checked' : '' ?>>
                                        <span><?= htmlspecialchars($category['name'], ENT_QUOTES) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </label>
                    <label class="form-field">
                        <span>Sampul</span>
                        <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp">
                        <small style="display: block; margin-top: 4px; color: #666;">
                            <strong>Note:</strong> Ukuran maksimal 10MB. Gambar akan otomatis dikompres dan dikonversi ke format WebP untuk optimasi kualitas dan ukuran file yang lebih kecil.
                        </small>
                        <?php if ($formData['cover_image_url'] !== ''): ?>
                            <input type="hidden" name="existing_cover" value="<?= htmlspecialchars($formData['cover_image_url'], ENT_QUOTES) ?>">
                            <div class="preview-thumb">
                                <img src="<?= htmlspecialchars($formData['cover_image_url'], ENT_QUOTES) ?>" alt="Cover">
                                <span>Ganti file untuk memperbarui gambar.</span>
                            </div>
                        <?php endif; ?>
                    </label>
                    <label class="form-field">
                        <span>Alt Text Gambar</span>
                        <input type="text" name="cover_image_alt" value="<?= htmlspecialchars($formData['cover_image_alt'], ENT_QUOTES) ?>" placeholder="Deskripsi singkat gambar hero">
                    </label>
                    <label class="form-field full">
                        <span>Meta Title</span>
                        <input type="text" name="meta_title" value="<?= htmlspecialchars($formData['meta_title'], ENT_QUOTES) ?>" placeholder="Judul SEO">
                    </label>
                    <label class="form-field full">
                        <span>Meta Description</span>
                        <textarea name="meta_description" rows="3" placeholder="Deskripsi SEO (maks 160 karakter)"><?= htmlspecialchars($formData['meta_description'], ENT_QUOTES) ?></textarea>
                    </label>
                    <label class="form-field full">
                        <span>Meta Keywords</span>
                        <input type="text" name="meta_keywords" value="<?= htmlspecialchars($formData['meta_keywords'], ENT_QUOTES) ?>" placeholder="kata kunci, dipisahkan koma">
                    </label>
                    <h3 class="form-section-title">CTA & Link Eksternal</h3>
                    <label class="form-field full">
                        <span>CTA Heading</span>
                        <input type="text" name="cta_heading" value="<?= htmlspecialchars($formData['cta_heading'], ENT_QUOTES) ?>" placeholder="Mau Planning Slurry Seal di Kampus Kamu?">
                    </label>
                    <label class="form-field full">
                        <span>CTA Subheading</span>
                        <textarea name="cta_subheading" rows="3" placeholder="Deskripsi singkat CTA"><?= htmlspecialchars($formData['cta_subheading'], ENT_QUOTES) ?></textarea>
                    </label>
                    <label class="form-field">
                        <span>CTA Shopee</span>
                        <input type="url" name="cta_shopee_url" value="<?= htmlspecialchars($formData['cta_shopee_url'], ENT_QUOTES) ?>" placeholder="https://shopee.co.id/...">
                    </label>
                    <label class="form-field">
                        <span>CTA Tokopedia</span>
                        <input type="url" name="cta_tokopedia_url" value="<?= htmlspecialchars($formData['cta_tokopedia_url'], ENT_QUOTES) ?>" placeholder="https://www.tokopedia.com/...">
                    </label>
                    <label class="form-field">
                        <span>CTA WhatsApp</span>
                        <input type="url" name="cta_whatsapp_url" value="<?= htmlspecialchars($formData['cta_whatsapp_url'], ENT_QUOTES) ?>" placeholder="https://wa.me/...">
                    </label>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan &amp; Publikasikan' ?>
                        </button>
                    </div>
                </form>
            </section>

            <section class="card table-card">
                <header>
                    <h2>Daftar Artikel</h2>
                </header>
                <div class="table-responsive table-responsive--flush">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Status</th>
                                <th>Kategori</th>
                                <th>Penulis</th>
                                <th>Terbit</th>
                                <th>View (30 Hari)</th>
                                <th>Total View</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($posts === []): ?>
                                <tr>
                                    <td colspan="8">Belum ada artikel.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($posts as $postRow): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($postRow['title'], ENT_QUOTES) ?></td>
                                        <td>
                                            <?php if ($postRow['status'] === 'published'): ?>
                                                <span class="badge badge-success">Published</span>
                                            <?php elseif ($postRow['status'] === 'draft'): ?>
                                                <span class="badge badge-warning">Draft</span>
                                            <?php else: ?>
                                                <span class="badge badge-neutral">Archived</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($postRow['category_names'] ?? '—', ENT_QUOTES) ?></td>
                                        <td><?= htmlspecialchars($postRow['author_name'] ?? '—', ENT_QUOTES) ?></td>
                                        <td>
                                            <?php
                                            $published = '—';
                                            if (!empty($postRow['published_at'])) {
                                                try {
                                                    $published = (new DateTime($postRow['published_at']))->format('d M Y H:i');
                                                } catch (Throwable $ignored) {
                                                    $published = (string) $postRow['published_at'];
                                                }
                                            }
                                            ?>
                                            <?= htmlspecialchars($published, ENT_QUOTES) ?>
                                        </td>
                                        <td><?= number_format((int) ($postRow['views_30d'] ?? 0)) ?></td>
                                        <td><?= number_format((int) ($postRow['views_total'] ?? 0)) ?></td>
                                        <td class="table-actions">
                                            <a class="btn btn-outline" href="<?= admin_url('articles') ?>?edit=<?= (int) $postRow['id'] ?>">Edit</a>
                                            <form method="post" onsubmit="return confirm('Hapus artikel ini?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $postRow['id'] ?>">
                                                <button type="submit" class="btn btn-outline btn-danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        </main>
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            jQuery(function ($) {
                const initialContent = <?= json_encode($formData['content'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

                $('#content-editor').summernote({
                    placeholder: 'Tulis konten artikel di sini...',
                    height: 280,
                    minHeight: 200,
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'italic', 'underline', 'clear']],
                        ['fontname', ['fontname']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'picture', 'video']],
                        ['view', ['fullscreen', 'codeview', 'help']]
                    ]
                });

                $('#content-editor').summernote('code', initialContent || '');
            });

            const flashPayload = <?php
                echo json_encode([
                    'notices' => $notices,
                    'errors' => $errors,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ?>;

            const escapeHtml = (value) => String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            if (Array.isArray(flashPayload.notices)) {
                flashPayload.notices.forEach(function (notice) {
                    if (!notice || !notice.message) {
                        return;
                    }
                    const icon = notice.type === 'warning' ? 'warning' : 'success';
                    const title = icon === 'warning' ? 'Perhatian' : 'Berhasil';
                    Swal.fire({
                        icon,
                        title,
                        text: notice.message,
                        confirmButtonColor: '#2563eb',
                    });
                });
            }

            if (Array.isArray(flashPayload.errors) && flashPayload.errors.length) {
                const list = flashPayload.errors
                    .map(function (msg) { return `<li>${escapeHtml(msg)}</li>`; })
                    .join('');
                Swal.fire({
                    icon: 'error',
                    title: 'Operasi Gagal',
                    html: `<ul style="text-align:left;margin:0;padding-left:20px;">${list}</ul>`,
                    confirmButtonColor: '#ef4444',
                });
            }
        </script>
    </body>
</html>


