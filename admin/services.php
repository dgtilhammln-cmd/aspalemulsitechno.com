<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireAuth();

$pdo = getPDO();

/**
 * MUNG CMS V1
 * File: admin/services.php
 * Author: Firmanda Himawan
 * Description: Backoffice interface for configuring service offerings and related metadata.
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

$errors = [];
$notices = [];
$validStatuses = ['draft', 'published', 'archived'];

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$mode = $editId ? 'edit' : 'create';

$formData = [
    'name' => '',
    'slug' => '',
    'short_description' => '',
    'description' => '',
    'status' => 'draft',
    'published_at' => '',
    'cover_image_url' => '',
    'meta_title' => '',
    'meta_description' => '',
    'meta_keywords' => '',
];

if ($mode === 'edit') {
    $serviceStmt = $pdo->prepare(
        'SELECT id, name, slug, short_description, description, cover_image_url, status, published_at
         FROM services WHERE id = :id LIMIT 1'
    );
    $serviceStmt->execute(['id' => $editId]);
    $service = $serviceStmt->fetch();

    if ($service === false) {
        $mode = 'create';
        $editId = null;
        $notices[] = ['type' => 'warning', 'message' => 'Layanan tidak ditemukan.'];
    } else {
        $seoStmt = $pdo->prepare(
            'SELECT meta_title, meta_description, meta_keywords
             FROM seo_meta WHERE owner_type = "service" AND owner_id = :owner_id'
        );
        $seoStmt->execute(['owner_id' => $service['id']]);
        $seo = $seoStmt->fetch() ?: [];

        $formData = [
            'name' => (string) $service['name'],
            'slug' => (string) $service['slug'],
            'short_description' => (string) ($service['short_description'] ?? ''),
            'description' => (string) ($service['description'] ?? ''),
            'status' => in_array($service['status'], $validStatuses, true) ? $service['status'] : 'draft',
            'published_at' => toInputDate($service['published_at'] ?? null) ?? '',
            'cover_image_url' => (string) ($service['cover_image_url'] ?? ''),
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
            $errors[] = 'ID layanan tidak valid.';
        } else {
            try {
                $pdo->beginTransaction();

                $pdo->prepare('DELETE FROM seo_meta WHERE owner_type = "service" AND owner_id = :id')
                    ->execute(['id' => $requestId]);

                $pdo->prepare('DELETE FROM services WHERE id = :id')
                    ->execute(['id' => $requestId]);

                $pdo->commit();
                admin_redirect('services', ['notice' => 'deleted']);
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
        $shortDescription = trim($_POST['short_description'] ?? '');
        $description = $_POST['description'] ?? '';
        $status = $_POST['status'] ?? 'draft';
        $publishedAtInput = $_POST['published_at'] ?? '';
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
            $metaKeywords = implode(', ', array_filter(['layanan aspal', 'aspal emulsi', $name]));
        }

        if ($name === '') {
            $errors[] = 'Nama layanan wajib diisi.';
        }

        if ($slug === '') {
            $errors[] = 'Slug layanan wajib diisi.';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            $errors[] = 'Slug hanya boleh berisi huruf kecil, angka, dan tanda minus.';
        }

        if (!in_array($status, $validStatuses, true)) {
            $errors[] = 'Status layanan tidak valid.';
        }

        $normalizedPublishedAt = normalizeDateTime($publishedAtInput);
        if ($status === 'published' && $normalizedPublishedAt === null) {
            $normalizedPublishedAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        }

        if ($status !== 'published') {
            $normalizedPublishedAt = null;
        }

        $uploadDir = dirname(__DIR__) . '/uploads';
        $uploadedImage = adminHandleImageUpload($_FILES['cover_image'] ?? null, 'service', $uploadDir, $errors);
        if ($uploadedImage !== null) {
            $coverImageUrl = $uploadedImage;
        } elseif ($action === 'update' && $existingCover !== '') {
            // Keep existing image if no new upload
            $coverImageUrl = $existingCover;
        } else {
            // For new services, start with empty
            $coverImageUrl = '';
        }

        $formData = [
            'name' => $name,
            'slug' => $slug,
            'short_description' => $shortDescription,
            'description' => $description,
            'status' => $status,
            'published_at' => $normalizedPublishedAt ? toInputDate($normalizedPublishedAt) : '',
            'cover_image_url' => $coverImageUrl,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
        ];

        if ($errors === []) {
            try {
                $pdo->beginTransaction();

                if ($action === 'update') {
                    if ($requestId === null) {
                        throw new RuntimeException('ID layanan tidak valid.');
                    }

                    $slugCheckStmt = $pdo->prepare('SELECT id FROM services WHERE slug = :slug AND id != :currentId LIMIT 1');
                    $slugCheckStmt->execute([
                        'slug' => $slug,
                        'currentId' => $requestId,
                    ]);
                } else {
                    $slugCheckStmt = $pdo->prepare('SELECT id FROM services WHERE slug = :slug LIMIT 1');
                    $slugCheckStmt->execute(['slug' => $slug]);
                }

                if ($slugCheckStmt->fetch()) {
                    $pdo->rollBack();
                    $errors[] = 'Slug layanan sudah digunakan.';
                } else {
                    if ($action === 'update') {
                        $updateStmt = $pdo->prepare(
                            'UPDATE services
                             SET name = :name,
                                 slug = :slug,
                                 short_description = :short_description,
                                 description = :description,
                                 cover_image_url = :cover_image_url,
                                 status = :status,
                                 published_at = :published_at,
                                 updated_at = NOW()
                             WHERE id = :id'
                        );
                        $updateStmt->execute([
                            'id' => $requestId,
                            'name' => $name,
                            'slug' => $slug,
                            'short_description' => $shortDescription !== '' ? $shortDescription : null,
                            'description' => $description !== '' ? $description : null,
                            'cover_image_url' => $coverImageUrl !== '' ? $coverImageUrl : null,
                            'status' => $status,
                            'published_at' => $normalizedPublishedAt,
                        ]);

                        $serviceId = $requestId;
                    } else {
                        $insertStmt = $pdo->prepare(
                            'INSERT INTO services (name, slug, short_description, description, cover_image_url, status, published_at)
                             VALUES (:name, :slug, :short_description, :description, :cover_image_url, :status, :published_at)'
                        );
                        $insertStmt->execute([
                            'name' => $name,
                            'slug' => $slug,
                            'short_description' => $shortDescription !== '' ? $shortDescription : null,
                            'description' => $description !== '' ? $description : null,
                            'cover_image_url' => $coverImageUrl !== '' ? $coverImageUrl : null,
                            'status' => $status,
                            'published_at' => $normalizedPublishedAt,
                        ]);

                        $serviceId = (int) $pdo->lastInsertId();
                    }

                    $seoStmt = $pdo->prepare(
                        'INSERT INTO seo_meta (owner_type, owner_id, meta_title, meta_description, meta_keywords)
                         VALUES ("service", :owner_id, :meta_title, :meta_description, :meta_keywords)
                         ON DUPLICATE KEY UPDATE meta_title = VALUES(meta_title), meta_description = VALUES(meta_description), meta_keywords = VALUES(meta_keywords)'
                    );
                    $seoStmt->execute([
                        'owner_id' => $serviceId,
                        'meta_title' => $metaTitle !== '' ? $metaTitle : null,
                        'meta_description' => $metaDescription !== '' ? $metaDescription : null,
                        'meta_keywords' => $metaKeywords !== '' ? $metaKeywords : null,
                    ]);

                    $pdo->commit();
                    $redirectNotice = $action === 'update' ? 'updated' : 'created';
                    admin_redirect('services', ['notice' => $redirectNotice]);
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
        'created' => 'Layanan berhasil disimpan.',
        'updated' => 'Layanan berhasil diperbarui.',
        'deleted' => 'Layanan berhasil dihapus.',
    ];

    if (isset($messages[$noticeKey])) {
        $notices[] = ['type' => 'success', 'message' => $messages[$noticeKey]];
    }
}

$servicesStmt = $pdo->query(
    'SELECT id, name, slug, status, published_at, cover_image_url
     FROM services
     ORDER BY COALESCE(published_at, created_at) DESC'
);
$services = $servicesStmt->fetchAll() ?: [];

$serviceStatusBreakdown = [
    'draft' => 0,
    'published' => 0,
    'archived' => 0,
];

foreach ($services as $serviceRow) {
    $status = (string) ($serviceRow['status'] ?? '');
    if (array_key_exists($status, $serviceStatusBreakdown)) {
        $serviceStatusBreakdown[$status]++;
    }
}

$totalServices = count($services);

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Kelola Layanan | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">
        <script src="app.js" defer></script>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'services';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Kelola Layanan',
                    'pageDescription' => 'Atur layanan inti Aspal Emulsi lengkap dengan deskripsi dan SEO.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Katalog'],
                        ['label' => 'Layanan'],
                    ],
                    'quickActions' => [
                        [
                            'label' => 'Tambah Produk',
                            'href' => admin_url('products'),
                            'variant' => 'ghost',
                        ],
                    ],
                ]);
            ?>
            <div class="workspace-content">
                <section class="workspace-meta">
                    <span class="status-pill">Total Layanan: <?= number_format($totalServices) ?></span>
                    <span class="chip chip--success">Published <?= number_format($serviceStatusBreakdown['published']) ?></span>
                    <span class="chip chip--warning">Draft <?= number_format($serviceStatusBreakdown['draft']) ?></span>
                    <span class="chip chip--neutral">Archived <?= number_format($serviceStatusBreakdown['archived']) ?></span>
                </section>

                <section class="card">
                <header>
                    <h2><?= $mode === 'edit' ? 'Edit Layanan' : 'Layanan Baru' ?></h2>
                    <?php if ($mode === 'edit'): ?>
                        <a class="btn btn-outline" href="<?= admin_url('services') ?>">Layanan Baru</a>
                    <?php endif; ?>
                </header>
                <form method="post" class="form-grid form-service" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">
                    <?php if ($mode === 'edit' && $editId !== null): ?>
                        <input type="hidden" name="id" value="<?= (int) $editId ?>">
                    <?php endif; ?>
                    <label class="form-field">
                        <span>Nama Layanan</span>
                        <input type="text" name="name" required value="<?= htmlspecialchars($formData['name'], ENT_QUOTES) ?>" placeholder="Nama layanan">
                    </label>
                    <label class="form-field">
                        <span>Slug</span>
                        <input type="text" name="slug" value="<?= htmlspecialchars($formData['slug'], ENT_QUOTES) ?>" placeholder="nama-layanan">
                        <small>Slug otomatis dari nama jika dikosongkan.</small>
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
                    </label>
                    <label class="form-field full">
                        <span>Deskripsi Singkat</span>
                        <textarea name="short_description" rows="3" placeholder="Sorotan utama layanan"><?= htmlspecialchars($formData['short_description'], ENT_QUOTES) ?></textarea>
                    </label>
                    <label class="form-field full">
                        <span>Deskripsi Lengkap</span>
                        <textarea id="service-description" name="description" rows="8"></textarea>
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
                                <img src="<?= htmlspecialchars($formData['cover_image_url'], ENT_QUOTES) ?>" alt="Cover Layanan">
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
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Layanan' ?>
                        </button>
                    </div>
                </form>
            </section>

            <section class="card table-card">
                <header>
                    <h2>Daftar Layanan</h2>
                </header>
                <div class="table-responsive table-responsive--flush">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Status</th>
                                <th>Publish</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($services === []): ?>
                                <tr>
                                    <td colspan="4">Belum ada layanan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($services as $serviceRow): ?>
                                    <tr>
                                        <td>
                                            <div class="table-service">
                                                <?php if (!empty($serviceRow['cover_image_url'])): ?>
                                                    <img src="<?= htmlspecialchars($serviceRow['cover_image_url'], ENT_QUOTES) ?>" alt="thumb">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($serviceRow['name'], ENT_QUOTES) ?></strong>
                                                    <div class="slug">/layanan/<?= htmlspecialchars($serviceRow['slug'], ENT_QUOTES) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($serviceRow['status'] === 'published'): ?>
                                                <span class="badge badge-service">Published</span>
                                            <?php elseif ($serviceRow['status'] === 'draft'): ?>
                                                <span class="badge badge-warning">Draft</span>
                                            <?php else: ?>
                                                <span class="badge badge-neutral">Archived</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $published = '—';
                                            if (!empty($serviceRow['published_at'])) {
                                                try {
                                                    $published = (new DateTime($serviceRow['published_at']))->format('d M Y H:i');
                                                } catch (Throwable $ignored) {
                                                    $published = (string) $serviceRow['published_at'];
                                                }
                                            }
                                            ?>
                                            <?= htmlspecialchars($published, ENT_QUOTES) ?>
                                        </td>
                                        <td class="table-actions">
                                            <a class="btn btn-outline" href="<?= admin_url('services') ?>?edit=<?= (int) $serviceRow['id'] ?>">Edit</a>
                                            <form method="post" onsubmit="return confirm('Hapus layanan ini?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $serviceRow['id'] ?>">
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

                $('#service-description').summernote({
                    placeholder: 'Tulis detail layanan di sini...',
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

                $('#service-description').summernote('code', initialContent || '');
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


