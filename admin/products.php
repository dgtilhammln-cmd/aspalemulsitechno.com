<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireAuth();

$pdo = getPDO();

/**
 * MUNG CMS V1
 * File: admin/products.php
 * Author: Firmanda Himawan
 * Description: Catalog manager for product publishing, CTA links, and supporting assets.
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
    return trim($value, '-');
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

if (!function_exists('admin_product_array_is_list')) {
    function admin_product_array_is_list(array $array): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }

        $i = 0;
        foreach ($array as $key => $_) {
            if ($key !== $i) {
                return false;
            }
            $i++;
        }

        return true;
    }
}

if (!function_exists('admin_decode_list_block')) {
    function admin_decode_list_block(mixed $value, string $defaultTitle): array
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
        if (admin_product_array_is_list($decoded)) {
            foreach ($decoded as $item) {
                $item = trim((string) $item);
                if ($item === '') {
                    continue;
                }
                $items[] = $item;
            }
        }

        $block['items'] = $items;

        return $block;
    }
}

if (!function_exists('admin_decode_pairs_block')) {
    function admin_decode_pairs_block(mixed $value, string $defaultTitle): array
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
}

if (!function_exists('admin_decode_documents_block')) {
    function admin_decode_documents_block(mixed $value, string $defaultTitle): array
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

if (!defined('PRODUCT_HIGHLIGHT_DEFAULT_TITLE')) {
    define('PRODUCT_HIGHLIGHT_DEFAULT_TITLE', 'Benefit Teknis');
}
if (!defined('PRODUCT_SPEC_DEFAULT_TITLE')) {
    define('PRODUCT_SPEC_DEFAULT_TITLE', 'Rangkuman Teknis');
}
if (!defined('PRODUCT_SUMMARY_DEFAULT_TITLE')) {
    define('PRODUCT_SUMMARY_DEFAULT_TITLE', 'Benefit Teknis');
}
if (!defined('PRODUCT_DOCUMENT_DEFAULT_TITLE')) {
    define('PRODUCT_DOCUMENT_DEFAULT_TITLE', 'Dokumen Pendukung');
}

$errors = [];
$notices = [];

$validStatuses = ['draft', 'published', 'archived'];

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$mode = $editId ? 'edit' : 'create';

$formData = [
    'name' => '',
    'slug' => '',
    'hero_badge' => '',
    'hero_eyebrow' => '',
    'highlights' => [
        'title' => PRODUCT_HIGHLIGHT_DEFAULT_TITLE,
        'items' => [],
    ],
    'specs' => [
        'title' => PRODUCT_SPEC_DEFAULT_TITLE,
        'items' => [],
    ],
    'summary_points' => [
        'title' => PRODUCT_SUMMARY_DEFAULT_TITLE,
        'items' => [],
    ],
    'documents' => [
        'title' => PRODUCT_DOCUMENT_DEFAULT_TITLE,
        'items' => [],
    ],
    'short_description' => '',
    'description' => '',
    'status' => 'draft',
    'published_at' => '',
    'cover_image_url' => '',
    'shopee_url' => '',
    'tokopedia_url' => '',
    'whatsapp_url' => '',
    'meta_title' => '',
    'meta_description' => '',
    'meta_keywords' => '',
];

if ($mode === 'edit') {
    $productStmt = $pdo->prepare(
        'SELECT
            id,
            name,
            slug,
            hero_badge,
            hero_eyebrow,
            highlights_json,
            specs_json,
            summary_json,
            documents_json,
            short_description,
            description,
            cover_image_url,
            shopee_url,
            tokopedia_url,
            whatsapp_url,
            status,
            published_at
         FROM products WHERE id = :id LIMIT 1'
    );
    $productStmt->execute(['id' => $editId]);
    $product = $productStmt->fetch();

    if ($product === false) {
        $mode = 'create';
        $editId = null;
        $notices[] = ['type' => 'warning', 'message' => 'Produk tidak ditemukan.'];
    } else {
        $seoStmt = $pdo->prepare(
            'SELECT meta_title, meta_description, meta_keywords
             FROM seo_meta WHERE owner_type = "product" AND owner_id = :owner_id'
        );
        $seoStmt->execute(['owner_id' => $product['id']]);
        $seo = $seoStmt->fetch() ?: [];

        $highlightsBlock = admin_decode_list_block($product['highlights_json'] ?? null, PRODUCT_HIGHLIGHT_DEFAULT_TITLE);
        $specsBlock = admin_decode_pairs_block($product['specs_json'] ?? null, PRODUCT_SPEC_DEFAULT_TITLE);
        $summaryBlock = admin_decode_pairs_block($product['summary_json'] ?? null, PRODUCT_SUMMARY_DEFAULT_TITLE);
        $documentsBlock = admin_decode_documents_block($product['documents_json'] ?? null, PRODUCT_DOCUMENT_DEFAULT_TITLE);

        $formData = [
            'name' => (string) $product['name'],
            'slug' => (string) $product['slug'],
            'hero_badge' => (string) ($product['hero_badge'] ?? ''),
            'hero_eyebrow' => (string) ($product['hero_eyebrow'] ?? ''),
            'highlights' => $highlightsBlock,
            'specs' => $specsBlock,
            'summary_points' => $summaryBlock,
            'documents' => $documentsBlock,
            'short_description' => (string) ($product['short_description'] ?? ''),
            'description' => (string) ($product['description'] ?? ''),
            'status' => in_array($product['status'], $validStatuses, true) ? $product['status'] : 'draft',
            'published_at' => toInputDate($product['published_at'] ?? null) ?? '',
            'cover_image_url' => (string) ($product['cover_image_url'] ?? ''),
            'shopee_url' => (string) ($product['shopee_url'] ?? ''),
            'tokopedia_url' => (string) ($product['tokopedia_url'] ?? ''),
            'whatsapp_url' => (string) ($product['whatsapp_url'] ?? ''),
            'meta_title' => (string) ($seo['meta_title'] ?? ''),
            'meta_description' => (string) ($seo['meta_description'] ?? ''),
            'meta_keywords' => (string) ($seo['meta_keywords'] ?? ''),
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $requestId = isset($_POST['id']) ? (int) $_POST['id'] : null;

    if ($action === 'delete') {
        if ($requestId === null) {
            $errors[] = 'ID produk tidak valid.';
        } else {
            try {
                $pdo->beginTransaction();

                $pdo->prepare('DELETE FROM seo_meta WHERE owner_type = "product" AND owner_id = :id')
                    ->execute(['id' => $requestId]);

                $pdo->prepare('DELETE FROM products WHERE id = :id')
                    ->execute(['id' => $requestId]);

                $pdo->commit();
                admin_redirect('products', ['notice' => 'deleted']);
                exit;
            } catch (Throwable $throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Terjadi kesalahan: ' . $throwable->getMessage();
            }
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $heroBadge = trim($_POST['hero_badge'] ?? '');
        $heroEyebrow = trim($_POST['hero_eyebrow'] ?? '');
        $highlightsInput = $_POST['highlights'] ?? [];
        $highlightsTitleInput = trim($_POST['highlights_title'] ?? PRODUCT_HIGHLIGHT_DEFAULT_TITLE);
        $specLabelsInput = $_POST['specs_label'] ?? [];
        $specValuesInput = $_POST['specs_value'] ?? [];
        $specTitleInput = trim($_POST['specs_title'] ?? PRODUCT_SPEC_DEFAULT_TITLE);
        $summaryLabelsInput = $_POST['summary_label'] ?? [];
        $summaryValuesInput = $_POST['summary_value'] ?? [];
        $summaryTitleInput = trim($_POST['summary_title'] ?? PRODUCT_SUMMARY_DEFAULT_TITLE);
        $documentTitlesInput = $_POST['documents_title'] ?? [];
        $documentUrlsInput = $_POST['documents_url'] ?? [];
        $documentsTitleInput = trim($_POST['documents_section_title'] ?? PRODUCT_DOCUMENT_DEFAULT_TITLE);
        $shortDescription = trim($_POST['short_description'] ?? '');
        $description = $_POST['description'] ?? '';
        $status = $_POST['status'] ?? 'draft';
        $publishedAtInput = $_POST['published_at'] ?? '';
        $shopeeUrl = trim($_POST['shopee_url'] ?? '');
        $tokopediaUrl = trim($_POST['tokopedia_url'] ?? '');
        $whatsappUrl = trim($_POST['whatsapp_url'] ?? '');
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDescription = trim($_POST['meta_description'] ?? '');
        $metaKeywords = trim($_POST['meta_keywords'] ?? '');
        $existingCover = trim($_POST['existing_cover'] ?? '');

        if ($slug === '' && $name !== '') {
            $slug = slugify($name);
        }

        if ($metaTitle === '' && $name !== '') {
            $metaTitle = $name . ' | Aspal Emulsi';
        }

        if ($metaDescription === '' && ($shortDescription !== '' || $description !== '')) {
            $raw = strip_tags($shortDescription !== '' ? $shortDescription : $description);
            $metaDescription = function_exists('mb_substr')
                ? mb_substr($raw, 0, 155)
                : substr($raw, 0, 155);
        }

        if ($metaKeywords === '' && $name !== '') {
            $metaKeywords = implode(', ', array_filter(['produk aspal', 'aspal emulsi', $name]));
        }

        $highlightsArray = [];
        if (is_array($highlightsInput)) {
            foreach ($highlightsInput as $item) {
                $item = trim((string) $item);
                if ($item === '') {
                    continue;
                }
                $highlightsArray[] = $item;
            }
        }

        $specsArray = [];
        if (is_array($specLabelsInput) && is_array($specValuesInput)) {
            $count = max(count($specLabelsInput), count($specValuesInput));
            for ($i = 0; $i < $count; $i++) {
                $label = isset($specLabelsInput[$i]) ? trim((string) $specLabelsInput[$i]) : '';
                $value = isset($specValuesInput[$i]) ? trim((string) $specValuesInput[$i]) : '';
                if ($label === '' && $value === '') {
                    continue;
                }
                if ($label === '' || $value === '') {
                    $errors[] = 'Format spesifikasi harus memiliki label dan nilai.';
                    continue;
                }
                $specsArray[] = ['label' => $label, 'value' => $value];
            }
        }

        $summaryArray = [];
        if (is_array($summaryLabelsInput) && is_array($summaryValuesInput)) {
            $count = max(count($summaryLabelsInput), count($summaryValuesInput));
            for ($i = 0; $i < $count; $i++) {
                $label = isset($summaryLabelsInput[$i]) ? trim((string) $summaryLabelsInput[$i]) : '';
                $value = isset($summaryValuesInput[$i]) ? trim((string) $summaryValuesInput[$i]) : '';
                if ($label === '' && $value === '') {
                    continue;
                }
                if ($label === '' || $value === '') {
                    $errors[] = 'Format ringkasan teknis harus memiliki label dan nilai.';
                    continue;
                }
                $summaryArray[] = ['label' => $label, 'value' => $value];
            }
        }

        $documentsArray = [];
        if (is_array($documentTitlesInput) && is_array($documentUrlsInput)) {
            $count = max(count($documentTitlesInput), count($documentUrlsInput));
            for ($i = 0; $i < $count; $i++) {
                $title = isset($documentTitlesInput[$i]) ? trim((string) $documentTitlesInput[$i]) : '';
                $url = isset($documentUrlsInput[$i]) ? trim((string) $documentUrlsInput[$i]) : '';
                if ($title === '' && $url === '') {
                    continue;
                }
                if ($title === '' || $url === '') {
                    $errors[] = 'Format dokumen harus memiliki judul dan URL.';
                    continue;
                }
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors[] = sprintf('URL dokumen "%s" tidak valid.', $title === '' ? 'Tanpa Judul' : $title);
                    continue;
                }
                $documentsArray[] = ['title' => $title, 'url' => $url];
            }
        }

        if ($name === '') {
            $errors[] = 'Nama produk wajib diisi.';
        }

        if ($slug === '') {
            $errors[] = 'Slug produk wajib diisi.';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            $errors[] = 'Slug hanya boleh berisi huruf kecil, angka, dan tanda minus.';
        }

        if (!in_array($status, $validStatuses, true)) {
            $errors[] = 'Status produk tidak valid.';
        }

        foreach ([['label' => 'Shopee', 'value' => $shopeeUrl], ['label' => 'Tokopedia', 'value' => $tokopediaUrl]] as $marketUrl) {
            if ($marketUrl['value'] !== '' && !filter_var($marketUrl['value'], FILTER_VALIDATE_URL)) {
                $errors[] = $marketUrl['label'] . ' URL tidak valid.';
            }
        }

        $normalizedPublishedAt = normalizeDateTime($publishedAtInput);
        if ($status === 'published' && $normalizedPublishedAt === null) {
            $normalizedPublishedAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        }

        if ($status !== 'published') {
            $normalizedPublishedAt = null;
        }

        $uploadDir = dirname(__DIR__) . '/uploads';
        $uploadedImage = adminHandleImageUpload($_FILES['cover_image'] ?? null, 'product', $uploadDir, $errors);
        if ($uploadedImage !== null) {
            $coverImageUrl = $uploadedImage;
        } elseif ($action === 'update' && $existingCover !== '') {
            // Keep existing image if no new upload
            $coverImageUrl = $existingCover;
        } else {
            // For new products, start with empty
            $coverImageUrl = '';
        }

        if ($whatsappUrl !== '' && !filter_var($whatsappUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Link WhatsApp tidak valid.';
        }

        $highlightsTitle = $highlightsTitleInput !== '' ? $highlightsTitleInput : PRODUCT_HIGHLIGHT_DEFAULT_TITLE;
        $specsTitle = $specTitleInput !== '' ? $specTitleInput : PRODUCT_SPEC_DEFAULT_TITLE;
        $summaryTitle = $summaryTitleInput !== '' ? $summaryTitleInput : PRODUCT_SUMMARY_DEFAULT_TITLE;
        $documentsTitle = $documentsTitleInput !== '' ? $documentsTitleInput : PRODUCT_DOCUMENT_DEFAULT_TITLE;

        $formData = [
            'name' => $name,
            'slug' => $slug,
            'hero_badge' => $heroBadge,
            'hero_eyebrow' => $heroEyebrow,
            'highlights' => [
                'title' => $highlightsTitle,
                'items' => $highlightsArray,
            ],
            'specs' => [
                'title' => $specsTitle,
                'items' => $specsArray,
            ],
            'summary_points' => [
                'title' => $summaryTitle,
                'items' => $summaryArray,
            ],
            'documents' => [
                'title' => $documentsTitle,
                'items' => $documentsArray,
            ],
            'short_description' => $shortDescription,
            'description' => $description,
            'status' => $status,
            'published_at' => $normalizedPublishedAt ? toInputDate($normalizedPublishedAt) : '',
            'cover_image_url' => $coverImageUrl,
            'shopee_url' => $shopeeUrl,
            'tokopedia_url' => $tokopediaUrl,
            'whatsapp_url' => $whatsappUrl,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
        ];

        if ($errors === []) {
            try {
                $pdo->beginTransaction();

                if ($action === 'update') {
                    if ($requestId === null) {
                        throw new RuntimeException('ID produk tidak valid.');
                    }

                    $slugCheckStmt = $pdo->prepare('SELECT id FROM products WHERE slug = :slug AND id != :currentId LIMIT 1');
                    $slugCheckStmt->execute([
                        'slug' => $slug,
                        'currentId' => $requestId,
                    ]);
                } else {
                    $slugCheckStmt = $pdo->prepare('SELECT id FROM products WHERE slug = :slug LIMIT 1');
                    $slugCheckStmt->execute(['slug' => $slug]);
                }

                if ($slugCheckStmt->fetch()) {
                    $pdo->rollBack();
                    $errors[] = 'Slug produk sudah digunakan.';
                } else {
                    if ($action === 'update') {
                        $updateStmt = $pdo->prepare(
                            'UPDATE products
                             SET name = :name,
                                 slug = :slug,
                                 hero_badge = :hero_badge,
                                 hero_eyebrow = :hero_eyebrow,
                                 highlights_json = :highlights_json,
                                 specs_json = :specs_json,
                                 summary_json = :summary_json,
                                 documents_json = :documents_json,
                                 short_description = :short_description,
                                 description = :description,
                                 cover_image_url = :cover_image_url,
                                 shopee_url = :shopee_url,
                                 tokopedia_url = :tokopedia_url,
                                 whatsapp_url = :whatsapp_url,
                                 status = :status,
                                 published_at = :published_at,
                                 updated_at = NOW()
                             WHERE id = :id'
                        );
                        $updateStmt->execute([
                            'id' => $requestId,
                            'name' => $name,
                            'slug' => $slug,
                            'hero_badge' => $heroBadge !== '' ? $heroBadge : null,
                            'hero_eyebrow' => $heroEyebrow !== '' ? $heroEyebrow : null,
                            'highlights_json' => json_encode([
                                'title' => $highlightsTitle,
                                'items' => $highlightsArray,
                            ], JSON_UNESCAPED_UNICODE),
                            'specs_json' => json_encode([
                                'title' => $specsTitle,
                                'items' => $specsArray,
                            ], JSON_UNESCAPED_UNICODE),
                            'summary_json' => json_encode([
                                'title' => $summaryTitle,
                                'items' => $summaryArray,
                            ], JSON_UNESCAPED_UNICODE),
                            'documents_json' => json_encode([
                                'title' => $documentsTitle,
                                'items' => $documentsArray,
                            ], JSON_UNESCAPED_UNICODE),
                            'short_description' => $shortDescription !== '' ? $shortDescription : null,
                            'description' => $description !== '' ? $description : null,
                            'cover_image_url' => $coverImageUrl !== '' ? $coverImageUrl : null,
                            'shopee_url' => $shopeeUrl !== '' ? $shopeeUrl : null,
                            'tokopedia_url' => $tokopediaUrl !== '' ? $tokopediaUrl : null,
                            'whatsapp_url' => $whatsappUrl !== '' ? $whatsappUrl : null,
                            'status' => $status,
                            'published_at' => $normalizedPublishedAt,
                        ]);

                        $productId = $requestId;
                    } else {
                        $insertStmt = $pdo->prepare(
                            'INSERT INTO products (
                                name,
                                slug,
                                hero_badge,
                                hero_eyebrow,
                                highlights_json,
                                specs_json,
                                summary_json,
                                documents_json,
                                short_description,
                                description,
                                cover_image_url,
                                shopee_url,
                                tokopedia_url,
                                whatsapp_url,
                                status,
                                published_at
                             )
                             VALUES (
                                :name,
                                :slug,
                                :hero_badge,
                                :hero_eyebrow,
                                :highlights_json,
                                :specs_json,
                                :summary_json,
                                :documents_json,
                                :short_description,
                                :description,
                                :cover_image_url,
                                :shopee_url,
                                :tokopedia_url,
                                :whatsapp_url,
                                :status,
                                :published_at
                             )'
                        );
                        $insertStmt->execute([
                            'name' => $name,
                            'slug' => $slug,
                            'hero_badge' => $heroBadge !== '' ? $heroBadge : null,
                            'hero_eyebrow' => $heroEyebrow !== '' ? $heroEyebrow : null,
                            'highlights_json' => json_encode([
                                'title' => $highlightsTitle,
                                'items' => $highlightsArray,
                            ], JSON_UNESCAPED_UNICODE),
                            'specs_json' => json_encode([
                                'title' => $specsTitle,
                                'items' => $specsArray,
                            ], JSON_UNESCAPED_UNICODE),
                            'summary_json' => json_encode([
                                'title' => $summaryTitle,
                                'items' => $summaryArray,
                            ], JSON_UNESCAPED_UNICODE),
                            'documents_json' => json_encode([
                                'title' => $documentsTitle,
                                'items' => $documentsArray,
                            ], JSON_UNESCAPED_UNICODE),
                            'short_description' => $shortDescription !== '' ? $shortDescription : null,
                            'description' => $description !== '' ? $description : null,
                            'cover_image_url' => $coverImageUrl !== '' ? $coverImageUrl : null,
                            'shopee_url' => $shopeeUrl !== '' ? $shopeeUrl : null,
                            'tokopedia_url' => $tokopediaUrl !== '' ? $tokopediaUrl : null,
                            'whatsapp_url' => $whatsappUrl !== '' ? $whatsappUrl : null,
                            'status' => $status,
                            'published_at' => $normalizedPublishedAt,
                        ]);

                        $productId = (int) $pdo->lastInsertId();
                    }

                    $seoStmt = $pdo->prepare(
                        'INSERT INTO seo_meta (owner_type, owner_id, meta_title, meta_description, meta_keywords)
                         VALUES ("product", :owner_id, :meta_title, :meta_description, :meta_keywords)
                         ON DUPLICATE KEY UPDATE meta_title = VALUES(meta_title), meta_description = VALUES(meta_description), meta_keywords = VALUES(meta_keywords)'
                    );
                    $seoStmt->execute([
                        'owner_id' => $productId,
                        'meta_title' => $metaTitle !== '' ? $metaTitle : null,
                        'meta_description' => $metaDescription !== '' ? $metaDescription : null,
                        'meta_keywords' => $metaKeywords !== '' ? $metaKeywords : null,
                    ]);

                    $pdo->commit();
                    $redirectNotice = $action === 'update' ? 'updated' : 'created';
                    admin_redirect('products', ['notice' => $redirectNotice]);
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
        'created' => 'Produk berhasil disimpan.',
        'updated' => 'Produk berhasil diperbarui.',
        'deleted' => 'Produk berhasil dihapus.',
    ];

    if (isset($messages[$noticeKey])) {
        $notices[] = ['type' => 'success', 'message' => $messages[$noticeKey]];
    }
}

$productsStmt = $pdo->query(
    'SELECT id, name, slug, status, shopee_url, tokopedia_url, published_at, updated_at, cover_image_url
     FROM products
     ORDER BY COALESCE(published_at, created_at) DESC'
);
$products = $productsStmt->fetchAll() ?: [];

$productStatusBreakdown = [
    'draft' => 0,
    'published' => 0,
    'archived' => 0,
];

foreach ($products as $productRow) {
    $status = (string) ($productRow['status'] ?? '');
    if (array_key_exists($status, $productStatusBreakdown)) {
        $productStatusBreakdown[$status]++;
    }
}

$totalProducts = count($products);

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Kelola Produk | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">
        <script src="app.js" defer></script>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'products';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Kelola Produk',
                    'pageDescription' => 'Update katalog, kelola link marketplace, dan optimalkan SEO produk.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Katalog'],
                        ['label' => 'Produk'],
                    ],
                    'quickActions' => [
                        [
                            'label' => 'Produk Baru',
                            'href' => admin_url('products'),
                            'variant' => 'primary',
                        ],
                        [
                            'label' => 'Analytics',
                            'href' => admin_url('analytics'),
                            'variant' => 'ghost',
                        ],
                    ],
                ]);
            ?>
            <div class="workspace-content">
                <section class="workspace-meta">
                    <span class="status-pill">Total Produk: <?= number_format($totalProducts) ?></span>
                    <span class="chip chip--success">Published <?= number_format($productStatusBreakdown['published']) ?></span>
                    <span class="chip chip--warning">Draft <?= number_format($productStatusBreakdown['draft']) ?></span>
                    <span class="chip chip--neutral">Archived <?= number_format($productStatusBreakdown['archived']) ?></span>
                </section>

                <section class="card">
                <header>
                    <h2><?= $mode === 'edit' ? 'Edit Produk' : 'Produk Baru' ?></h2>
                    <?php if ($mode === 'edit'): ?>
                        <a class="btn btn-outline" href="<?= admin_url('products') ?>">Produk Baru</a>
                    <?php endif; ?>
                </header>
                <form method="post" class="form-grid form-product" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">
                    <?php if ($mode === 'edit' && $editId !== null): ?>
                        <input type="hidden" name="id" value="<?= (int) $editId ?>">
                    <?php endif; ?>
                    <label class="form-field">
                        <span>Nama Produk</span>
                        <input type="text" name="name" required value="<?= htmlspecialchars($formData['name'], ENT_QUOTES) ?>" placeholder="Nama produk">
                    </label>
                    <label class="form-field">
                        <span>Slug</span>
                        <input type="text" name="slug" value="<?= htmlspecialchars($formData['slug'], ENT_QUOTES) ?>" placeholder="nama-produk">
                        <small>Slug otomatis dari nama jika dikosongkan.</small>
                    </label>
                    <label class="form-field">
                        <span>Badge Hero</span>
                        <input type="text" name="hero_badge" value="<?= htmlspecialchars($formData['hero_badge'], ENT_QUOTES) ?>" placeholder="Contoh: Produk Premium">
                    </label>
                    <label class="form-field">
                        <span>Eyebrow Hero</span>
                        <input type="text" name="hero_eyebrow" value="<?= htmlspecialchars($formData['hero_eyebrow'], ENT_QUOTES) ?>" placeholder="Contoh: Solusi Pelapisan Kedap Air">
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
                        <span>Tanggal Publish</span>
                        <input type="datetime-local" name="published_at" value="<?= htmlspecialchars($formData['published_at'], ENT_QUOTES) ?>">
                        <small>Terisi otomatis saat status "published" tanpa tanggal.</small>
                    </label>
                    <label class="form-field full">
                        <span>Deskripsi Singkat</span>
                        <textarea name="short_description" rows="3" placeholder="Highlight fitur utama produk"><?= htmlspecialchars($formData['short_description'], ENT_QUOTES) ?></textarea>
                    </label>
                    <div class="form-field full">
                        <span>Highlight Produk</span>
                        <input type="text" name="highlights_title" value="<?= htmlspecialchars($formData['highlights']['title'] ?? PRODUCT_HIGHLIGHT_DEFAULT_TITLE, ENT_QUOTES) ?>" placeholder="Judul highlight (contoh: Benefit Teknis)">
                        <?php
                            $highlightsItems = $formData['highlights']['items'] ?? [];
                            if ($highlightsItems === []) {
                                $highlightsItems = [''];
                            }
                        ?>
                        <div class="repeater" data-repeater="highlights">
                            <?php foreach ($highlightsItems as $highlightItem): ?>
                                <div class="repeater__row" data-repeater-item>
                                    <input type="text" name="highlights[]" value="<?= htmlspecialchars($highlightItem, ENT_QUOTES) ?>" placeholder="Contoh: Formulasi tahan UV">
                                    <button type="button" class="btn btn-outline btn-icon" data-repeater-remove aria-label="Hapus highlight">
                                        &times;
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" data-repeater-add="highlights">Tambah Highlight</button>
                    </div>
                    <label class="form-field full">
                        <span>Deskripsi Lengkap</span>
                        <textarea id="description-editor" name="description" rows="8"></textarea>
                    </label>
                    <label class="form-field">
                        <span>Link Shopee</span>
                        <input type="url" name="shopee_url" value="<?= htmlspecialchars($formData['shopee_url'], ENT_QUOTES) ?>" placeholder="https://shopee.co.id/...">
                    </label>
                    <label class="form-field">
                        <span>Link Tokopedia</span>
                        <input type="url" name="tokopedia_url" value="<?= htmlspecialchars($formData['tokopedia_url'], ENT_QUOTES) ?>" placeholder="https://www.tokopedia.com/...">
                    </label>
                    <label class="form-field">
                        <span>Link WhatsApp</span>
                        <input type="url" name="whatsapp_url" value="<?= htmlspecialchars($formData['whatsapp_url'], ENT_QUOTES) ?>" placeholder="https://wa.me/62...">
                        <small>Gunakan format <code>https://wa.me/</code> dengan pesan yang telah di-encode.</small>
                    </label>
                    <div class="form-field full">
                        <span>Spesifikasi Teknis</span>
                        <input type="text" name="specs_title" value="<?= htmlspecialchars($formData['specs']['title'] ?? PRODUCT_SPEC_DEFAULT_TITLE, ENT_QUOTES) ?>" placeholder="Judul spesifikasi (contoh: Rangkuman Teknis)">
                        <?php
                            $specsItems = $formData['specs']['items'] ?? [];
                            if ($specsItems === []) {
                                $specsItems = [['label' => '', 'value' => '']];
                            }
                        ?>
                        <div class="repeater" data-repeater="specs">
                            <?php foreach ($specsItems as $specRow): ?>
                                <div class="repeater__row repeater__row--split" data-repeater-item>
                                    <input type="text" name="specs_label[]" value="<?= htmlspecialchars($specRow['label'] ?? '', ENT_QUOTES) ?>" placeholder="Label (contoh: Kemasan)">
                                    <input type="text" name="specs_value[]" value="<?= htmlspecialchars($specRow['value'] ?? '', ENT_QUOTES) ?>" placeholder="Nilai (contoh: Pail 20 kg)">
                                    <button type="button" class="btn btn-outline btn-icon" data-repeater-remove aria-label="Hapus spesifikasi">
                                        &times;
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" data-repeater-add="specs">Tambah Spesifikasi</button>
                    </div>
                    <div class="form-field full">
                        <span>Ringkasan Teknis</span>
                        <input type="text" name="summary_title" value="<?= htmlspecialchars($formData['summary_points']['title'] ?? PRODUCT_SUMMARY_DEFAULT_TITLE, ENT_QUOTES) ?>" placeholder="Judul ringkasan (contoh: Benefit Teknis)">
                        <?php
                            $summaryItems = $formData['summary_points']['items'] ?? [];
                            if ($summaryItems === []) {
                                $summaryItems = [['label' => '', 'value' => '']];
                            }
                        ?>
                        <div class="repeater" data-repeater="summary">
                            <?php foreach ($summaryItems as $summaryRow): ?>
                                <div class="repeater__row repeater__row--split" data-repeater-item>
                                    <input type="text" name="summary_label[]" value="<?= htmlspecialchars($summaryRow['label'] ?? '', ENT_QUOTES) ?>" placeholder="Label (contoh: Rekomendasi Substrat)">
                                    <input type="text" name="summary_value[]" value="<?= htmlspecialchars($summaryRow['value'] ?? '', ENT_QUOTES) ?>" placeholder="Nilai (contoh: Dek beton)">
                                    <button type="button" class="btn btn-outline btn-icon" data-repeater-remove aria-label="Hapus ringkasan">
                                        &times;
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" data-repeater-add="summary">Tambah Ringkasan</button>
                    </div>
                    <div class="form-field full">
                        <span>Dokumen Pendukung</span>
                        <input type="text" name="documents_section_title" value="<?= htmlspecialchars($formData['documents']['title'] ?? PRODUCT_DOCUMENT_DEFAULT_TITLE, ENT_QUOTES) ?>" placeholder="Judul dokumen (contoh: Dokumen Pendukung)">
                        <?php
                            $documentItems = $formData['documents']['items'] ?? [];
                            if ($documentItems === []) {
                                $documentItems = [['title' => '', 'url' => '']];
                            }
                        ?>
                        <div class="repeater" data-repeater="documents">
                            <?php foreach ($documentItems as $documentRow): ?>
                                <div class="repeater__row repeater__row--split" data-repeater-item>
                                    <input type="text" name="documents_title[]" value="<?= htmlspecialchars($documentRow['title'] ?? '', ENT_QUOTES) ?>" placeholder="Judul dokumen">
                                    <input type="url" name="documents_url[]" value="<?= htmlspecialchars($documentRow['url'] ?? '', ENT_QUOTES) ?>" placeholder="https://example.com/dokumen.pdf">
                                    <button type="button" class="btn btn-outline btn-icon" data-repeater-remove aria-label="Hapus dokumen">
                                        &times;
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" data-repeater-add="documents">Tambah Dokumen</button>
                    </div>
                    <label class="form-field">
                        <span>Sampul</span>
                        <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp">
                        <small style="display: block; margin-top: 4px; color: #666;">
                            <strong>Note:</strong> Ukuran maksimal 10MB. Gambar akan otomatis dikompres dan dikonversi ke format WebP untuk optimasi kualitas dan ukuran file yang lebih kecil.
                        </small>
                        <?php if ($formData['cover_image_url'] !== ''): ?>
                            <input type="hidden" name="existing_cover" value="<?= htmlspecialchars($formData['cover_image_url'], ENT_QUOTES) ?>">
                            <div class="preview-thumb">
                                <img src="<?= htmlspecialchars($formData['cover_image_url'], ENT_QUOTES) ?>" alt="Cover Produk">
                                <span>Upload file baru untuk mengganti gambar.</span>
                            </div>
                        <?php endif; ?>
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
                    <template data-repeater-template="highlights">
                        <div class="repeater__row" data-repeater-item>
                            <input type="text" name="highlights[]" placeholder="Contoh: Formulasi tahan UV">
                            <button type="button" class="btn btn-outline btn-icon" data-repeater-remove aria-label="Hapus highlight">&times;</button>
                        </div>
                    </template>
                    <template data-repeater-template="specs">
                        <div class="repeater__row repeater__row--split" data-repeater-item>
                            <input type="text" name="specs_label[]" placeholder="Label (contoh: Kemasan)">
                            <input type="text" name="specs_value[]" placeholder="Nilai (contoh: Pail 20 kg)">
                            <button type="button" class="btn btn-outline btn-icon" data-repeater-remove aria-label="Hapus spesifikasi">&times;</button>
                        </div>
                    </template>
                    <template data-repeater-template="summary">
                        <div class="repeater__row repeater__row--split" data-repeater-item>
                            <input type="text" name="summary_label[]" placeholder="Label (contoh: Benefit)">
                            <input type="text" name="summary_value[]" placeholder="Nilai (contoh: Menutup pori aspal)">
                            <button type="button" class="btn btn-outline btn-icon" data-repeater-remove aria-label="Hapus ringkasan">&times;</button>
                        </div>
                    </template>
                    <template data-repeater-template="documents">
                        <div class="repeater__row repeater__row--split" data-repeater-item>
                            <input type="text" name="documents_title[]" placeholder="Judul dokumen">
                            <input type="url" name="documents_url[]" placeholder="https://example.com/dokumen.pdf">
                            <button type="button" class="btn btn-outline btn-icon" data-repeater-remove aria-label="Hapus dokumen">&times;</button>
                        </div>
                    </template>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Produk' ?>
                        </button>
                    </div>
                </form>
            </section>

            <section class="card table-card">
                <header>
                    <h2>Daftar Produk</h2>
                </header>
                <div class="table-responsive table-responsive--flush">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Status</th>
                                <th>Marketplace</th>
                                <th>Publish</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($products === []): ?>
                                <tr>
                                    <td colspan="5">Belum ada produk.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $productRow): ?>
                                    <tr>
                                        <td>
                                            <div class="table-product">
                                                <?php if (!empty($productRow['cover_image_url'])): ?>
                                                    <img src="<?= htmlspecialchars($productRow['cover_image_url'], ENT_QUOTES) ?>" alt="thumb">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($productRow['name'], ENT_QUOTES) ?></strong>
                                                    <div class="slug">/produk/<?= htmlspecialchars($productRow['slug'], ENT_QUOTES) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($productRow['status'] === 'published'): ?>
                                                <span class="badge badge-success">Published</span>
                                            <?php elseif ($productRow['status'] === 'draft'): ?>
                                                <span class="badge badge-warning">Draft</span>
                                            <?php else: ?>
                                                <span class="badge badge-neutral">Archived</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="market-links">
                                            <?php if (!empty($productRow['shopee_url'])): ?>
                                                <a href="<?= htmlspecialchars($productRow['shopee_url'], ENT_QUOTES) ?>" target="_blank" rel="noopener">Shopee</a>
                                            <?php endif; ?>
                                            <?php if (!empty($productRow['tokopedia_url'])): ?>
                                                <a href="<?= htmlspecialchars($productRow['tokopedia_url'], ENT_QUOTES) ?>" target="_blank" rel="noopener">Tokopedia</a>
                                            <?php endif; ?>
                                            <?php if (empty($productRow['shopee_url']) && empty($productRow['tokopedia_url'])): ?>
                                                <span>—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $published = '—';
                                            if (!empty($productRow['published_at'])) {
                                                try {
                                                    $published = (new DateTime($productRow['published_at']))->format('d M Y H:i');
                                                } catch (Throwable $ignored) {
                                                    $published = (string) $productRow['published_at'];
                                                }
                                            }
                                            ?>
                                            <?= htmlspecialchars($published, ENT_QUOTES) ?>
                                        </td>
                                        <td class="table-actions">
                                            <a class="btn btn-outline" href="<?= admin_url('products') ?>?edit=<?= (int) $productRow['id'] ?>">Edit</a>
                                            <form method="post" onsubmit="return confirm('Hapus produk ini?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $productRow['id'] ?>">
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
                const initialContent = <?= json_encode($formData['description'] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

                $('#description-editor').summernote({
                    placeholder: 'Tulis detail produk di sini...',
                    height: 260,
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

                $('#description-editor').summernote('code', initialContent || '');
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

            document.addEventListener('DOMContentLoaded', function () {
                const templates = new Map();
                document.querySelectorAll('template[data-repeater-template]').forEach(function (template) {
                    const key = template.getAttribute('data-repeater-template');
                    if (key) {
                        templates.set(key, template);
                    }
                });

                const initRepeater = function (key) {
                    const container = document.querySelector(`[data-repeater="${key}"]`);
                    if (!container) {
                        return;
                    }

                    const template = templates.get(key);
                    const addBtn = document.querySelector(`[data-repeater-add="${key}"]`);

                    const addRow = function () {
                        if (!template) {
                            return;
                        }
                        const fragment = template.content.cloneNode(true);
                        const element = fragment.firstElementChild;
                        if (!element) {
                            return;
                        }
                        container.appendChild(element);
                        const firstInput = element.querySelector('input');
                        if (firstInput) {
                            firstInput.focus();
                        }
                    };

                    container.addEventListener('click', function (event) {
                        const trigger = event.target.closest('[data-repeater-remove]');
                        if (!trigger) {
                            return;
                        }
                        const item = trigger.closest('[data-repeater-item]');
                        if (item) {
                            item.remove();
                        }
                    });

                    if (addBtn) {
                        addBtn.addEventListener('click', function () {
                            addRow();
                        });
                    }
                };

                ['highlights', 'specs', 'summary', 'documents'].forEach(initRepeater);
            });
        </script>
    </body>
</html>


