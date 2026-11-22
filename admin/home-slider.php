<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireAuth();

$pdo = getPDO();
$errors = [];
$notices = [];
$validStatuses = ['draft', 'published'];

$action = $_POST['action'] ?? null;

if ($action === 'create' || $action === 'update') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $subtitle = trim((string) ($_POST['subtitle'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $existingImage = trim((string) ($_POST['existing_image'] ?? ''));
    $imageUrl = trim((string) ($_POST['image_url'] ?? $existingImage));
    $linkUrl = trim((string) ($_POST['link_url'] ?? ''));
    $status = in_array($_POST['status'] ?? '', $validStatuses, true) ? $_POST['status'] : 'draft';
    $sortOrder = $_POST['sort_order'] !== '' ? (int) $_POST['sort_order'] : null;

    if ($title === '') {
        $errors[] = 'Judul wajib diisi.';
    }

    if ($sortOrder !== null && $sortOrder < 0) {
        $errors[] = 'Urutan tidak boleh bernilai negatif.';
    }

    $uploadDir = dirname(__DIR__) . '/uploads';
    $uploadedImage = adminHandleImageUpload($_FILES['image_file'] ?? null, 'slider', $uploadDir, $errors);
    if ($uploadedImage !== null) {
        $imageUrl = $uploadedImage;
    } elseif ($action === 'update' && $existingImage !== '') {
        // Keep existing image if no new upload
        $imageUrl = $existingImage;
    }
    // For new slides, $imageUrl already initialized from POST above (line 21)

    if ($errors === []) {
        try {
            if ($sortOrder === null) {
                $sortOrder = (int) ($pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM home_slider_slides')->fetchColumn() ?: 0);
            }

            if ($action === 'update') {
                $id = (int) ($_POST['id'] ?? 0);
                $stmt = $pdo->prepare(
                    'UPDATE home_slider_slides
                     SET title = :title,
                         subtitle = :subtitle,
                         description = :description,
                         image_url = :image_url,
                         link_url = :link_url,
                         status = :status,
                         sort_order = :sort_order
                     WHERE id = :id'
                );
                $stmt->execute([
                    'title' => $title,
                    'subtitle' => $subtitle !== '' ? $subtitle : null,
                    'description' => $description !== '' ? $description : null,
                    'image_url' => $imageUrl !== '' ? $imageUrl : null,
                    'link_url' => $linkUrl !== '' ? $linkUrl : null,
                    'status' => $status,
                    'sort_order' => $sortOrder,
                    'id' => $id,
                ]);
                admin_redirect('home-slider', ['notice' => 'updated']);
                exit;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO home_slider_slides (title, subtitle, description, image_url, link_url, status, sort_order)
                 VALUES (:title, :subtitle, :description, :image_url, :link_url, :status, :sort_order)'
            );
            $stmt->execute([
                'title' => $title,
                'subtitle' => $subtitle !== '' ? $subtitle : null,
                'description' => $description !== '' ? $description : null,
                'image_url' => $imageUrl !== '' ? $imageUrl : null,
                'link_url' => $linkUrl !== '' ? $linkUrl : null,
                'status' => $status,
                'sort_order' => $sortOrder,
            ]);

            admin_redirect('home-slider', ['notice' => 'created']);
            exit;
        } catch (Throwable $throwable) {
            $errors[] = 'Gagal menyimpan slider: ' . $throwable->getMessage();
        }
    }
}

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    try {
        $stmt = $pdo->prepare('DELETE FROM home_slider_slides WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        admin_redirect('home-slider', ['notice' => 'deleted']);
        exit;
    } catch (Throwable $throwable) {
        $errors[] = 'Gagal menghapus slide: ' . $throwable->getMessage();
    }
}

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$mode = $editId ? 'edit' : 'create';

$formData = [
    'title' => '',
    'subtitle' => '',
    'description' => '',
    'image_url' => '',
    'link_url' => '',
    'status' => 'published',
    'sort_order' => '',
];

if ($mode === 'edit') {
    $stmt = $pdo->prepare('SELECT * FROM home_slider_slides WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) $editId]);
    $slide = $stmt->fetch();

    if ($slide === false) {
        $mode = 'create';
        $editId = null;
        $notices[] = ['type' => 'warning', 'message' => 'Slide tidak ditemukan.'];
    } else {
        $formData = [
            'title' => (string) ($slide['title'] ?? ''),
            'subtitle' => (string) ($slide['subtitle'] ?? ''),
            'description' => (string) ($slide['description'] ?? ''),
            'image_url' => (string) ($slide['image_url'] ?? ''),
            'link_url' => (string) ($slide['link_url'] ?? ''),
            'status' => (string) ($slide['status'] ?? 'draft'),
            'sort_order' => (string) ($slide['sort_order'] ?? ''),
        ];
    }
}

if (isset($_GET['notice'])) {
    $messages = [
        'created' => 'Slide baru berhasil ditambahkan.',
        'updated' => 'Slide berhasil diperbarui.',
        'deleted' => 'Slide berhasil dihapus.',
    ];

    $key = $_GET['notice'];
    if (isset($messages[$key])) {
        $notices[] = ['type' => 'success', 'message' => $messages[$key]];
    }
}

$slides = $pdo->query(
    'SELECT id, title, subtitle, image_url, link_url, status, sort_order, updated_at
     FROM home_slider_slides
     ORDER BY sort_order ASC, created_at DESC'
)->fetchAll() ?: [];

$publishedSlides = array_sum(array_map(static fn ($slide) => (int) ((($slide['status'] ?? '') === 'published') ? 1 : 0), $slides));
$draftSlides = count($slides) - $publishedSlides;

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Home Slider | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
        <script src="app.js" defer></script>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'home-slider';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Home Slider',
                    'pageDescription' => 'Kelola konten slider hero beranda.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Homepage'],
                        ['label' => 'Slider'],
                    ],
                    'quickActions' => [
                        [
                            'label' => 'Preview Landing',
                            'href' => '/',
                            'variant' => 'ghost',
                        ],
                    ],
                ]);
            ?>
            <div class="workspace-content">
                <section class="workspace-meta">
                    <span class="status-pill">Total Slide: <?= number_format(count($slides)) ?></span>
                    <span class="chip chip--success">Published <?= number_format($publishedSlides) ?></span>
                    <span class="chip chip--warning">Draft <?= number_format($draftSlides) ?></span>
                </section>

                <?php if ($errors !== []): ?>
                <section class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error, ENT_QUOTES) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>

                <?php if ($notices !== []): ?>
                    <?php foreach ($notices as $notice): ?>
                    <section class="alert alert-success">
                        <?= htmlspecialchars($notice['message'], ENT_QUOTES) ?>
                    </section>
                    <?php endforeach; ?>
                <?php endif; ?>

                <section class="card">
                <header>
                    <h2><?= $mode === 'edit' ? 'Edit Slide' : 'Slide Baru' ?></h2>
                    <?php if ($mode === 'edit'): ?>
                        <a class="btn btn-outline" href="<?= admin_url('home-slider') ?>">Buat Baru</a>
                    <?php endif; ?>
                </header>
                <form method="post" class="form-grid" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">
                    <?php if ($mode === 'edit' && $editId !== null): ?>
                        <input type="hidden" name="id" value="<?= (int) $editId ?>">
                    <?php endif; ?>
                    <label class="form-field">
                        <span>Judul</span>
                        <input type="text" name="title" required value="<?= htmlspecialchars($formData['title'], ENT_QUOTES) ?>" placeholder="Judul besar"> 
                    </label>
                    <label class="form-field">
                        <span>Subjudul</span>
                        <input type="text" name="subtitle" value="<?= htmlspecialchars($formData['subtitle'], ENT_QUOTES) ?>" placeholder="Label kecil di atas judul">
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
                        <span>Urutan</span>
                        <input type="number" name="sort_order" value="<?= htmlspecialchars((string) $formData['sort_order'], ENT_QUOTES) ?>" placeholder="0">
                        <small>Biarkan kosong untuk otomatis menempatkan di urutan terakhir.</small>
                    </label>
                    <label class="form-field full">
                        <span>Deskripsi</span>
                        <textarea name="description" rows="4" placeholder="Deskripsi singkat slide"><?= htmlspecialchars($formData['description'], ENT_QUOTES) ?></textarea>
                    </label>
                    <label class="form-field">
                        <span>Gambar</span>
                        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
                        <small style="display: block; margin-top: 4px; color: #666;">
                            <strong>Note:</strong> Ukuran maksimal 10MB. Gambar akan otomatis dikompres dan dikonversi ke format WebP untuk optimasi kualitas dan ukuran file yang lebih kecil.
                        </small>
                        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($formData['image_url'], ENT_QUOTES) ?>">
                        <?php if ($formData['image_url'] !== ''): ?>
                            <div class="preview-thumb">
                                <img src="<?= htmlspecialchars($formData['image_url'], ENT_QUOTES) ?>" alt="Preview slide">
                                <span>Biarkan kosong bila tidak ingin mengganti gambar.</span>
                            </div>
                        <?php endif; ?>
                        <small style="display: block; margin-top: 8px;">Atau masukkan URL manual:</small>
                        <input type="text" name="image_url" value="<?= htmlspecialchars($formData['image_url'], ENT_QUOTES) ?>" placeholder="https://... atau path /uploads/..." style="margin-top: 4px;">
                    </label>
                    <label class="form-field">
                        <span>Link CTA</span>
                        <input type="text" name="link_url" value="<?= htmlspecialchars($formData['link_url'], ENT_QUOTES) ?>" placeholder="Tautan tombol utama">
                    </label>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?= $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Slide' ?></button>
                    </div>
                </form>
            </section>

            <section class="card table-card">
                <header>
                    <h2>Daftar Slide</h2>
                </header>
                <div class="table-responsive table-responsive--flush">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Status</th>
                                <th>Urutan</th>
                                <th>Updated</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($slides === []): ?>
                                <tr>
                                    <td colspan="5">Belum ada slide.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($slides as $slide): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($slide['title'], ENT_QUOTES) ?></strong>
                                            <?php if (!empty($slide['subtitle'])): ?>
                                                <div class="table-meta"><?= htmlspecialchars($slide['subtitle'], ENT_QUOTES) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $slide['status'] === 'published' ? '<span class="badge badge-success">Published</span>' : '<span class="badge badge-warning">Draft</span>' ?></td>
                                        <td><?= (int) $slide['sort_order'] ?></td>
                                        <td><?= htmlspecialchars($slide['updated_at'], ENT_QUOTES) ?></td>
                                        <td class="table-actions">
                                            <a class="btn btn-outline" href="<?= admin_url('home-slider', ['edit' => (int) $slide['id']]) ?>">Edit</a>
                                            <form method="post" onsubmit="return confirm('Yakin ingin menghapus slide ini?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $slide['id'] ?>">
                                                <button type="submit" class="btn btn-danger">Hapus</button>
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
    </body>
</html>
