<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireAuth();

$pdo = getPDO();

/**
 * MUNG CMS V1
 * File: admin/portfolio.php
 * Author: Firmanda Himawan
 * Description: Showcase manager for portfolio entries, project highlights, and visual assets.
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
$validStatuses = ['draft', 'published'];

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$mode = $editId ? 'edit' : 'create';

$formData = [
    'title' => '',
    'slug' => '',
    'category' => '',
    'location' => '',
    'featured' => 0,
    'status' => 'published',
    'showcased_at' => '',
    'description' => '',
    'image_url' => '',
];

if ($mode === 'edit') {
    $portfolioStmt = $pdo->prepare(
        'SELECT id, title, slug, category, description, image_url, location, featured, status, showcased_at
         FROM portfolio_items WHERE id = :id LIMIT 1'
    );
    $portfolioStmt->execute(['id' => $editId]);
    $item = $portfolioStmt->fetch();

    if ($item === false) {
        $mode = 'create';
        $editId = null;
        $notices[] = ['type' => 'warning', 'message' => 'Galeri tidak ditemukan.'];
    } else {
        $formData = [
            'title' => (string) $item['title'],
            'slug' => (string) $item['slug'],
            'category' => (string) ($item['category'] ?? ''),
            'location' => (string) ($item['location'] ?? ''),
            'featured' => (int) ($item['featured'] ?? 0),
            'status' => in_array($item['status'], $validStatuses, true) ? $item['status'] : 'published',
            'showcased_at' => toInputDate($item['showcased_at'] ?? null) ?? '',
            'description' => (string) ($item['description'] ?? ''),
            'image_url' => (string) ($item['image_url'] ?? ''),
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $requestId = isset($_POST['id']) ? (int) $_POST['id'] : null;

    if ($action === 'delete') {
        if ($requestId === null) {
            $errors[] = 'ID galeri tidak valid.';
        } else {
            $deleteStmt = $pdo->prepare('DELETE FROM portfolio_items WHERE id = :id');
            $deleteStmt->execute(['id' => $requestId]);
            admin_redirect('portfolio', ['notice' => 'deleted']);
        }
    } else {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $featured = isset($_POST['featured']) ? 1 : 0;
        $status = $_POST['status'] ?? 'published';
        $showcasedAtInput = $_POST['showcased_at'] ?? '';
        $description = $_POST['description'] ?? '';
        $existingImage = trim($_POST['existing_image'] ?? '');

        if ($slug === '' && $title !== '') {
            $slug = slugify($title);
        }

        if ($title === '') {
            $errors[] = 'Judul portofolio wajib diisi.';
        }

        if ($slug === '') {
            $errors[] = 'Slug portofolio wajib diisi.';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            $errors[] = 'Slug hanya boleh berisi huruf kecil, angka, dan tanda minus.';
        }

        if (!in_array($status, $validStatuses, true)) {
            $errors[] = 'Status galeri tidak valid.';
        }

        $normalizedShowcased = normalizeDateTime($showcasedAtInput);

        $uploadDir = dirname(__DIR__) . '/uploads';
        $uploadedImage = adminHandleImageUpload($_FILES['image'] ?? null, 'portfolio', $uploadDir, $errors);
        if ($uploadedImage !== null) {
            $imageUrl = $uploadedImage;
        } elseif ($action === 'update' && $existingImage !== '') {
            // Keep existing image if no new upload
            $imageUrl = $existingImage;
        } else {
            // For new portfolio items, start with empty
            $imageUrl = '';
        }

        $formData = [
            'title' => $title,
            'slug' => $slug,
            'category' => $category,
            'location' => $location,
            'featured' => $featured,
            'status' => $status,
            'showcased_at' => $normalizedShowcased ? toInputDate($normalizedShowcased) : '',
            'description' => $description,
            'image_url' => $imageUrl,
        ];

        if ($errors === []) {
            if ($action === 'update') {
                if ($requestId === null) {
                    $errors[] = 'ID galeri tidak valid.';
                } else {
                    $slugCheckStmt = $pdo->prepare('SELECT id FROM portfolio_items WHERE slug = :slug AND id != :currentId LIMIT 1');
                    $slugCheckStmt->execute([
                        'slug' => $slug,
                        'currentId' => $requestId,
                    ]);

                    if ($slugCheckStmt->fetch()) {
                        $errors[] = 'Slug portofolio sudah digunakan.';
                    } else {
                        $updateStmt = $pdo->prepare(
                            'UPDATE portfolio_items
                             SET title = :title,
                                 slug = :slug,
                                 category = :category,
                                 description = :description,
                                 image_url = :image_url,
                                 location = :location,
                                 featured = :featured,
                                 status = :status,
                                 showcased_at = :showcased_at,
                                 updated_at = NOW()
                             WHERE id = :id'
                        );
                        $updateStmt->execute([
                            'id' => $requestId,
                            'title' => $title,
                            'slug' => $slug,
                            'category' => $category !== '' ? $category : null,
                            'description' => $description !== '' ? $description : null,
                            'image_url' => $imageUrl !== '' ? $imageUrl : null,
                            'location' => $location !== '' ? $location : null,
                            'featured' => $featured,
                            'status' => $status,
                            'showcased_at' => $normalizedShowcased,
                        ]);

                        admin_redirect('portfolio', ['notice' => 'updated']);
                    }
                }
            } else {
                $slugCheckStmt = $pdo->prepare('SELECT id FROM portfolio_items WHERE slug = :slug LIMIT 1');
                $slugCheckStmt->execute(['slug' => $slug]);

                if ($slugCheckStmt->fetch()) {
                    $errors[] = 'Slug portofolio sudah digunakan.';
                } else {
                    $insertStmt = $pdo->prepare(
                        'INSERT INTO portfolio_items (title, slug, category, description, image_url, location, featured, status, showcased_at)
                         VALUES (:title, :slug, :category, :description, :image_url, :location, :featured, :status, :showcased_at)'
                    );
                    $insertStmt->execute([
                        'title' => $title,
                        'slug' => $slug,
                        'category' => $category !== '' ? $category : null,
                        'description' => $description !== '' ? $description : null,
                        'image_url' => $imageUrl !== '' ? $imageUrl : null,
                        'location' => $location !== '' ? $location : null,
                        'featured' => $featured,
                        'status' => $status,
                        'showcased_at' => $normalizedShowcased,
                    ]);

                    admin_redirect('portfolio', ['notice' => 'created']);
                    exit;
                }
            }
        }
    }
}

if (isset($_GET['notice'])) {
    $noticeKey = $_GET['notice'];
    $messages = [
        'created' => 'Galeri berhasil ditambahkan.',
        'updated' => 'Galeri berhasil diperbarui.',
        'deleted' => 'Galeri berhasil dihapus.',
    ];

    if (isset($messages[$noticeKey])) {
        $notices[] = ['type' => 'success', 'message' => $messages[$noticeKey]];
    }
}

$portfolioStmt = $pdo->query(
    'SELECT id, title, slug, category, location, featured, status, showcased_at, image_url
     FROM portfolio_items
     ORDER BY COALESCE(showcased_at, created_at) DESC'
);
$portfolioItems = $portfolioStmt->fetchAll() ?: [];

$portfolioStats = [
    'published' => 0,
    'draft' => 0,
    'featured' => 0,
];

foreach ($portfolioItems as $item) {
    if ((int) ($item['featured'] ?? 0) === 1) {
        $portfolioStats['featured']++;
    }
    $status = (string) ($item['status'] ?? '');
    if ($status === 'published') {
        $portfolioStats['published']++;
    } elseif ($status === 'draft') {
        $portfolioStats['draft']++;
    }
}

$totalPortfolio = count($portfolioItems);

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Galeri Portofolio | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">
        <script src="app.js" defer></script>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'portfolio';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Galeri Portofolio',
                    'pageDescription' => 'Kurasi dokumentasi proyek unggulan lengkap dengan foto dan detail.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Katalog'],
                        ['label' => 'Portofolio'],
                    ],
                    'quickActions' => [
                        [
                            'label' => 'Tambah Galeri',
                            'href' => admin_url('portfolio'),
                            'variant' => 'primary',
                        ],
                    ],
                ]);
            ?>
            <div class="workspace-content">
                <section class="workspace-meta">
                    <span class="status-pill">Total Galeri: <?= number_format($totalPortfolio) ?></span>
                    <span class="chip chip--success">Published <?= number_format($portfolioStats['published']) ?></span>
                    <span class="chip chip--warning">Draft <?= number_format($portfolioStats['draft']) ?></span>
                    <span class="chip chip--neutral">Featured <?= number_format($portfolioStats['featured']) ?></span>
                </section>

                <section class="card">
                <header>
                    <h2><?= $mode === 'edit' ? 'Edit Galeri' : 'Galeri Baru' ?></h2>
                    <?php if ($mode === 'edit'): ?>
                        <a class="btn btn-outline" href="<?= admin_url('portfolio') ?>">Galeri Baru</a>
                    <?php endif; ?>
                </header>
                <form method="post" class="form-grid form-portfolio" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">
                    <?php if ($mode === 'edit' && $editId !== null): ?>
                        <input type="hidden" name="id" value="<?= (int) $editId ?>">
                    <?php endif; ?>
                    <label class="form-field">
                        <span>Judul Proyek</span>
                        <input type="text" name="title" required value="<?= htmlspecialchars($formData['title'], ENT_QUOTES) ?>" placeholder="Judul proyek">
                    </label>
                    <label class="form-field">
                        <span>Slug</span>
                        <input type="text" name="slug" value="<?= htmlspecialchars($formData['slug'], ENT_QUOTES) ?>" placeholder="judul-proyek">
                        <small>Slug otomatis dari judul jika dikosongkan.</small>
                    </label>
                    <label class="form-field">
                        <span>Kategori</span>
                        <input type="text" name="category" value="<?= htmlspecialchars($formData['category'], ENT_QUOTES) ?>" placeholder="Kategori">
                    </label>
                    <label class="form-field">
                        <span>Lokasi</span>
                        <input type="text" name="location" value="<?= htmlspecialchars($formData['location'], ENT_QUOTES) ?>" placeholder="Lokasi proyek">
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
                        <span>Tanggal Show</span>
                        <input type="datetime-local" name="showcased_at" value="<?= htmlspecialchars($formData['showcased_at'], ENT_QUOTES) ?>">
                    </label>
                    <label class="form-field">
                        <span>Highlight</span>
                        <div>
                            <label style="display:flex;align-items:center;gap:8px;">
                                <input type="checkbox" name="featured" <?= $formData['featured'] ? 'checked' : '' ?>>
                                Tandai sebagai proyek unggulan
                            </label>
                        </div>
                    </label>
                    <label class="form-field full">
                        <span>Deskripsi</span>
                        <textarea id="portfolio-description" name="description" rows="6"></textarea>
                    </label>
                    <label class="form-field">
                        <span>Gambar</span>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                        <small style="display: block; margin-top: 4px; color: #666;">
                            <strong>Note:</strong> Ukuran maksimal 10MB. Gambar akan otomatis dikompres dan dikonversi ke format WebP untuk optimasi kualitas dan ukuran file yang lebih kecil.
                        </small>
                        <?php if ($formData['image_url'] !== ''): ?>
                            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($formData['image_url'], ENT_QUOTES) ?>">
                            <div class="preview-thumb">
                                <img src="<?= htmlspecialchars($formData['image_url'], ENT_QUOTES) ?>" alt="Gambar Portfolio">
                                <span>Upload file baru untuk mengganti gambar.</span>
                            </div>
                        <?php endif; ?>
                    </label>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Galeri' ?>
                        </button>
                    </div>
                </form>
            </section>

            <section class="card table-card">
                <header>
                    <h2>Daftar Portofolio</h2>
                </header>
                <div class="table-responsive table-responsive--flush">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Proyek</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Showcase</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($portfolioItems === []): ?>
                                <tr>
                                    <td colspan="5">Belum ada portofolio.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($portfolioItems as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="table-portfolio">
                                                <?php if (!empty($row['image_url'])): ?>
                                                    <img src="<?= htmlspecialchars($row['image_url'], ENT_QUOTES) ?>" alt="thumb">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($row['title'], ENT_QUOTES) ?></strong>
                                                    <div class="slug">/portfolio/<?= htmlspecialchars($row['slug'], ENT_QUOTES) ?></div>
                                                    <?php if (!empty($row['location'])): ?>
                                                        <div class="slug">Lokasi: <?= htmlspecialchars($row['location'], ENT_QUOTES) ?></div>
                                                    <?php endif; ?>
                                                    <?php if ((int) $row['featured'] === 1): ?>
                                                        <span class="tag-chip">Featured</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $row['category'] ? htmlspecialchars($row['category'], ENT_QUOTES) : '—' ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'published'): ?>
                                                <span class="badge badge-success">Published</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $show = '—';
                                            if (!empty($row['showcased_at'])) {
                                                try {
                                                    $show = (new DateTime($row['showcased_at']))->format('d M Y H:i');
                                                } catch (Throwable $ignored) {
                                                    $show = (string) $row['showcased_at'];
                                                }
                                            }
                                            ?>
                                            <?= htmlspecialchars($show, ENT_QUOTES) ?>
                                        </td>
                                        <td class="table-actions">
                                            <a class="btn btn-outline" href="<?= admin_url('portfolio') ?>?edit=<?= (int) $row['id'] ?>">Edit</a>
                                            <form method="post" onsubmit="return confirm('Hapus portofolio ini?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
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

                $('#portfolio-description').summernote({
                    placeholder: 'Tulis deskripsi proyek di sini...',
                    height: 220,
                    minHeight: 180,
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

                $('#portfolio-description').summernote('code', initialContent || '');
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


