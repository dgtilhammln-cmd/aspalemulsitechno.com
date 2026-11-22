<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireAuth();

$pdo = getPDO();

/**
 * MUNG CMS V1
 * File: admin/testimonials.php
 * Author: Firmanda Himawan
 * Description: Testimonial management suite for curating client feedback and ratings.
 */

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
    'client_name' => '',
    'company' => '',
    'role' => '',
    'quote' => '',
    'rating' => 5,
    'status' => 'published',
    'published_at' => '',
    'avatar_url' => '',
];

if ($mode === 'edit') {
    $testimonialStmt = $pdo->prepare(
        'SELECT id, client_name, company, role, quote, rating, avatar_url, status, published_at
         FROM testimonials WHERE id = :id LIMIT 1'
    );
    $testimonialStmt->execute(['id' => $editId]);
    $testimonial = $testimonialStmt->fetch();

    if ($testimonial === false) {
        $mode = 'create';
        $editId = null;
        $notices[] = ['type' => 'warning', 'message' => 'Testimoni tidak ditemukan.'];
    } else {
        $formData = [
            'client_name' => (string) $testimonial['client_name'],
            'company' => (string) ($testimonial['company'] ?? ''),
            'role' => (string) ($testimonial['role'] ?? ''),
            'quote' => (string) ($testimonial['quote'] ?? ''),
            'rating' => (int) ($testimonial['rating'] ?? 5),
            'status' => in_array($testimonial['status'], $validStatuses, true) ? $testimonial['status'] : 'published',
            'published_at' => toInputDate($testimonial['published_at'] ?? null) ?? '',
            'avatar_url' => (string) ($testimonial['avatar_url'] ?? ''),
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $requestId = isset($_POST['id']) ? (int) $_POST['id'] : null;

    if ($action === 'delete') {
        if ($requestId === null) {
            $errors[] = 'ID testimoni tidak valid.';
        } else {
            $pdo->prepare('DELETE FROM testimonials WHERE id = :id')->execute(['id' => $requestId]);
            admin_redirect('testimonials', ['notice' => 'deleted']);
        }
    } else {
        $clientName = trim($_POST['client_name'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $quote = trim($_POST['quote'] ?? '');
        $rating = (int) ($_POST['rating'] ?? 5);
        $status = $_POST['status'] ?? 'published';
        $publishedAtInput = $_POST['published_at'] ?? '';
        $existingAvatar = trim($_POST['existing_avatar'] ?? '');

        if ($clientName === '') {
            $errors[] = 'Nama klien wajib diisi.';
        }

        if ($quote === '') {
            $errors[] = 'Isi testimoni wajib diisi.';
        }

        if ($rating < 1 || $rating > 5) {
            $errors[] = 'Rating harus antara 1 sampai 5.';
        }

        if (!in_array($status, $validStatuses, true)) {
            $errors[] = 'Status testimoni tidak valid.';
        }

        $normalizedPublishedAt = normalizeDateTime($publishedAtInput);
        if ($status === 'published' && $normalizedPublishedAt === null) {
            $normalizedPublishedAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        }

        if ($status !== 'published') {
            $normalizedPublishedAt = null;
        }

        $uploadDir = dirname(__DIR__) . '/uploads';
        $uploadedImage = adminHandleImageUpload($_FILES['avatar'] ?? null, 'testimonial', $uploadDir, $errors);
        if ($uploadedImage !== null) {
            $avatarUrl = $uploadedImage;
        } elseif ($action === 'update' && $existingAvatar !== '') {
            // Keep existing avatar if no new upload
            $avatarUrl = $existingAvatar;
        } else {
            // For new testimonials, start with empty
            $avatarUrl = '';
        }

        $formData = [
            'client_name' => $clientName,
            'company' => $company,
            'role' => $role,
            'quote' => $quote,
            'rating' => $rating,
            'status' => $status,
            'published_at' => $normalizedPublishedAt ? toInputDate($normalizedPublishedAt) : '',
            'avatar_url' => $avatarUrl,
        ];

        if ($errors === []) {
            if ($action === 'update') {
                if ($requestId === null) {
                    $errors[] = 'ID testimoni tidak valid.';
                } else {
                    $updateStmt = $pdo->prepare(
                        'UPDATE testimonials
                         SET client_name = :client_name,
                             company = :company,
                             role = :role,
                             quote = :quote,
                             rating = :rating,
                             avatar_url = :avatar_url,
                             status = :status,
                             published_at = :published_at,
                             updated_at = NOW()
                         WHERE id = :id'
                    );
                    $updateStmt->execute([
                        'id' => $requestId,
                        'client_name' => $clientName,
                        'company' => $company !== '' ? $company : null,
                        'role' => $role !== '' ? $role : null,
                        'quote' => $quote,
                        'rating' => $rating,
                        'avatar_url' => $avatarUrl !== '' ? $avatarUrl : null,
                        'status' => $status,
                        'published_at' => $normalizedPublishedAt,
                    ]);

                    admin_redirect('testimonials', ['notice' => 'updated']);
                }
            } else {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO testimonials (client_name, company, role, quote, rating, avatar_url, status, published_at)
                     VALUES (:client_name, :company, :role, :quote, :rating, :avatar_url, :status, :published_at)'
                );
                $insertStmt->execute([
                    'client_name' => $clientName,
                    'company' => $company !== '' ? $company : null,
                    'role' => $role !== '' ? $role : null,
                    'quote' => $quote,
                    'rating' => $rating,
                    'avatar_url' => $avatarUrl !== '' ? $avatarUrl : null,
                    'status' => $status,
                    'published_at' => $normalizedPublishedAt,
                ]);

                admin_redirect('testimonials', ['notice' => 'created']);
            }
        }
    }
}

if (isset($_GET['notice'])) {
    $noticeKey = $_GET['notice'];
    $messages = [
        'created' => 'Testimoni berhasil ditambahkan.',
        'updated' => 'Testimoni berhasil diperbarui.',
        'deleted' => 'Testimoni berhasil dihapus.',
    ];

    if (isset($messages[$noticeKey])) {
        $notices[] = ['type' => 'success', 'message' => $messages[$noticeKey]];
    }
}

$testimonialsStmt = $pdo->query(
    'SELECT id, client_name, company, role, quote, rating, status, avatar_url, published_at
     FROM testimonials
     ORDER BY COALESCE(published_at, created_at) DESC'
);
$testimonials = $testimonialsStmt->fetchAll() ?: [];

$testimonialStats = [
    'published' => 0,
    'draft' => 0,
    'avg_rating' => 0,
];

if ($testimonials !== []) {
    $totalRating = 0;
    foreach ($testimonials as $testimonialRow) {
        $status = (string) ($testimonialRow['status'] ?? '');
        if ($status === 'published') {
            $testimonialStats['published']++;
        } elseif ($status === 'draft') {
            $testimonialStats['draft']++;
        }
        $totalRating += (int) ($testimonialRow['rating'] ?? 0);
    }
    $testimonialStats['avg_rating'] = round($totalRating / count($testimonials), 1);
}

$totalTestimonials = count($testimonials);

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Kelola Testimoni | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
        <script src="app.js" defer></script>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'testimonials';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Kelola Testimoni',
                    'pageDescription' => 'Kumpulkan pengalaman klien untuk meningkatkan kredibilitas brand.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Katalog'],
                        ['label' => 'Testimoni'],
                    ],
                    'quickActions' => [
                        [
                            'label' => 'Testimoni Baru',
                            'href' => admin_url('testimonials'),
                            'variant' => 'primary',
                        ],
                    ],
                ]);
            ?>
            <div class="workspace-content">
                <section class="workspace-meta">
                    <span class="status-pill">Total Testimoni: <?= number_format($totalTestimonials) ?></span>
                    <span class="chip chip--success">Published <?= number_format($testimonialStats['published']) ?></span>
                    <span class="chip chip--warning">Draft <?= number_format($testimonialStats['draft']) ?></span>
                    <?php if ($totalTestimonials > 0): ?>
                        <span class="chip chip--neutral">Rata-rata <?= number_format($testimonialStats['avg_rating'], 1) ?>★</span>
                    <?php endif; ?>
                </section>

                <section class="card">
                <header>
                    <h2><?= $mode === 'edit' ? 'Edit Testimoni' : 'Testimoni Baru' ?></h2>
                    <?php if ($mode === 'edit'): ?>
                        <a class="btn btn-outline" href="<?= admin_url('testimonials') ?>">Testimoni Baru</a>
                    <?php endif; ?>
                </header>
                <form method="post" class="form-grid form-testimonial" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">
                    <?php if ($mode === 'edit' && $editId !== null): ?>
                        <input type="hidden" name="id" value="<?= (int) $editId ?>">
                    <?php endif; ?>
                    <label class="form-field">
                        <span>Nama Klien</span>
                        <input type="text" name="client_name" required value="<?= htmlspecialchars($formData['client_name'], ENT_QUOTES) ?>" placeholder="Nama klien">
                    </label>
                    <label class="form-field">
                        <span>Perusahaan</span>
                        <input type="text" name="company" value="<?= htmlspecialchars($formData['company'], ENT_QUOTES) ?>" placeholder="Perusahaan">
                    </label>
                    <label class="form-field">
                        <span>Posisi/Jabatan</span>
                        <input type="text" name="role" value="<?= htmlspecialchars($formData['role'], ENT_QUOTES) ?>" placeholder="Posisi">
                    </label>
                    <label class="form-field">
                        <span>Rating</span>
                        <select name="rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= (int) $formData['rating'] === $i ? 'selected' : '' ?>><?= $i ?> bintang</option>
                            <?php endfor; ?>
                        </select>
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
                        <span>Testimoni</span>
                        <textarea name="quote" rows="4" placeholder="Tulis pesan klien"><?= htmlspecialchars($formData['quote'], ENT_QUOTES) ?></textarea>
                    </label>
                    <label class="form-field">
                        <span>Foto Klien</span>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
                        <small style="display: block; margin-top: 4px; color: #666;">
                            <strong>Note:</strong> Ukuran maksimal 10MB. Gambar akan otomatis dikompres dan dikonversi ke format WebP untuk optimasi kualitas dan ukuran file yang lebih kecil.
                        </small>
                        <?php if ($formData['avatar_url'] !== ''): ?>
                            <input type="hidden" name="existing_avatar" value="<?= htmlspecialchars($formData['avatar_url'], ENT_QUOTES) ?>">
                            <div class="preview-thumb">
                                <img src="<?= htmlspecialchars($formData['avatar_url'], ENT_QUOTES) ?>" alt="Avatar">
                                <span>Upload foto baru untuk mengganti.</span>
                            </div>
                        <?php endif; ?>
                    </label>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Testimoni' ?>
                        </button>
                    </div>
                </form>
            </section>

            <section class="card table-card">
                <header>
                    <h2>Daftar Testimoni</h2>
                </header>
                <div class="table-responsive table-responsive--flush">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Klien</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th>Publish</th>
                                <th>Testimoni</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($testimonials === []): ?>
                                <tr>
                                    <td colspan="6">Belum ada testimoni.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($testimonials as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="table-testimonial">
                                                <?php if (!empty($row['avatar_url'])): ?>
                                                    <img src="<?= htmlspecialchars($row['avatar_url'], ENT_QUOTES) ?>" alt="avatar">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($row['client_name'], ENT_QUOTES) ?></strong>
                                                    <?php if (!empty($row['company'])): ?>
                                                        <div class="slug"><?= htmlspecialchars($row['company'], ENT_QUOTES) ?><?= !empty($row['role']) ? ' — ' . htmlspecialchars($row['role'], ENT_QUOTES) : '' ?></div>
                                                    <?php elseif (!empty($row['role'])): ?>
                                                        <div class="slug"><?= htmlspecialchars($row['role'], ENT_QUOTES) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= (int) $row['rating'] ?>★</td>
                                        <td>
                                            <?php if ($row['status'] === 'published'): ?>
                                                <span class="badge badge-success">Published</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $published = '—';
                                            if (!empty($row['published_at'])) {
                                                try {
                                                    $published = (new DateTime($row['published_at']))->format('d M Y');
                                                } catch (Throwable $ignored) {
                                                    $published = (string) $row['published_at'];
                                                }
                                            }
                                            ?>
                                            <?= htmlspecialchars($published, ENT_QUOTES) ?>
                                        </td>
                                        <td class="table-quote">“<?= htmlspecialchars($row['quote'], ENT_QUOTES) ?>”</td>
                                        <td class="table-actions">
                                            <a class="btn btn-outline" href="<?= admin_url('testimonials') ?>?edit=<?= (int) $row['id'] ?>">Edit</a>
                                            <form method="post" onsubmit="return confirm('Hapus testimoni ini?');">
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
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


