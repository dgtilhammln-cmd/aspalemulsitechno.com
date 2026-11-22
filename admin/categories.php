<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireAuth();

$pdo = getPDO();

/**
 * MUNG CMS V1
 * File: admin/categories.php
 * Author: Firmanda Himawan
 * Description: Module for managing blog categories and taxonomy insights within the CMS.
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

$errors = [];
$notices = [];
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$mode = $editId ? 'edit' : 'create';

$formData = [
    'name' => '',
    'slug' => '',
    'description' => '',
];

if ($mode === 'edit') {
    $categoryStmt = $pdo->prepare(
        'SELECT id, name, slug, description FROM blog_categories WHERE id = :id LIMIT 1'
    );
    $categoryStmt->execute(['id' => $editId]);
    $category = $categoryStmt->fetch();

    if ($category === false) {
        $mode = 'create';
        $editId = null;
        $notices[] = ['type' => 'warning', 'message' => 'Kategori tidak ditemukan.'];
    } else {
        $formData = [
            'name' => (string) $category['name'],
            'slug' => (string) $category['slug'],
            'description' => (string) ($category['description'] ?? ''),
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $requestId = isset($_POST['id']) ? (int) $_POST['id'] : null;

    if ($action === 'delete') {
        if ($requestId === null) {
        $errors[] = 'ID kategori tidak valid.';
    } else {
            try {
                $pdo->beginTransaction();

                $deletePivot = $pdo->prepare('DELETE FROM blog_post_categories WHERE category_id = :id');
                $deletePivot->execute(['id' => $requestId]);

                $deleteSeo = $pdo->prepare('DELETE FROM seo_meta WHERE owner_type = "category" AND owner_id = :id');
                $deleteSeo->execute(['id' => $requestId]);

                $deleteStmt = $pdo->prepare('DELETE FROM blog_categories WHERE id = :id');
                $deleteStmt->execute(['id' => $requestId]);

                $pdo->commit();
                admin_redirect('categories', ['notice' => 'deleted']);
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
        $description = trim($_POST['description'] ?? '');

        if ($slug === '' && $name !== '') {
            $slug = slugify($name);
        }

        if ($name === '') {
            $errors[] = 'Nama kategori wajib diisi.';
        }

        if ($slug === '') {
            $errors[] = 'Slug kategori wajib diisi.';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            $errors[] = 'Slug hanya boleh berisi huruf kecil, angka, dan tanda minus.';
        }

        $formData = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
        ];

        if ($errors === []) {
            try {
                $pdo->beginTransaction();

                if ($action === 'update') {
                    if ($requestId === null) {
                        throw new RuntimeException('ID kategori tidak valid.');
                    }

                    $slugCheckStmt = $pdo->prepare(
                        'SELECT id FROM blog_categories WHERE slug = :slug AND id != :currentId LIMIT 1'
                    );
                    $slugCheckStmt->execute([
                        'slug' => $slug,
                        'currentId' => $requestId,
                    ]);
                } else {
                    $slugCheckStmt = $pdo->prepare('SELECT id FROM blog_categories WHERE slug = :slug LIMIT 1');
                    $slugCheckStmt->execute(['slug' => $slug]);
                }

                if ($slugCheckStmt->fetch()) {
                    $pdo->rollBack();
                    $errors[] = 'Slug sudah digunakan kategori lain.';
                } else {
                    if ($action === 'update') {
                        $updateStmt = $pdo->prepare(
                            'UPDATE blog_categories
                             SET name = :name, slug = :slug, description = :description
                             WHERE id = :id'
                        );
                        $updateStmt->execute([
                            'id' => $requestId,
                            'name' => $name,
                            'slug' => $slug,
                            'description' => $description !== '' ? $description : null,
                        ]);

                        $pdo->prepare(
                            'UPDATE seo_meta
                             SET meta_title = :title, meta_description = :description, meta_keywords = :keywords
                             WHERE owner_type = "category" AND owner_id = :id'
                        )->execute([
                            'id' => $requestId,
                            'title' => $name . ' | Aspal Emulsi Indonesia',
                            'description' => $description !== '' ? $description : null,
                            'keywords' => implode(', ', array_filter([$name, 'aspal emulsi', 'infrastruktur jalan'])),
                        ]);

                        $pdo->commit();
                        admin_redirect('categories', ['notice' => 'updated']);
                        exit;
                    }

                    $insertStmt = $pdo->prepare(
                        'INSERT INTO blog_categories (name, slug, description)
                         VALUES (:name, :slug, :description)'
                    );
                    $insertStmt->execute([
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description !== '' ? $description : null,
                    ]);

                    $categoryId = (int) $pdo->lastInsertId();

                    $seoStmt = $pdo->prepare(
                        'INSERT INTO seo_meta (owner_type, owner_id, meta_title, meta_description, meta_keywords)
                         VALUES ("category", :owner_id, :title, :description, :keywords)
                         ON DUPLICATE KEY UPDATE meta_title = VALUES(meta_title), meta_description = VALUES(meta_description), meta_keywords = VALUES(meta_keywords)'
                    );
                    $seoStmt->execute([
                        'owner_id' => $categoryId,
                        'title' => $name . ' | Aspal Emulsi Indonesia',
                        'description' => $description !== '' ? $description : null,
                        'keywords' => implode(', ', array_filter([$name, 'aspal emulsi', 'infrastruktur jalan'])),
                    ]);

                    $pdo->commit();
                    admin_redirect('categories', ['notice' => 'created']);
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
        'created' => 'Kategori berhasil ditambahkan.',
        'updated' => 'Kategori berhasil diperbarui.',
        'deleted' => 'Kategori berhasil dihapus.',
    ];

    if (isset($messages[$noticeKey])) {
        $notices[] = ['type' => 'success', 'message' => $messages[$noticeKey]];
    }
}

$categoriesStmt = $pdo->query(
    'SELECT bc.id, bc.name, bc.slug, bc.description, bc.created_at, bc.updated_at,
            COUNT(bpc.post_id) AS posts_count
     FROM blog_categories bc
     LEFT JOIN blog_post_categories bpc ON bpc.category_id = bc.id
     GROUP BY bc.id
     ORDER BY bc.created_at DESC'
);
$categories = $categoriesStmt->fetchAll() ?: [];

$totalCategories = count($categories);
$totalTaggedArticles = array_sum(array_map(static fn ($row) => (int) ($row['posts_count'] ?? 0), $categories));

$currentUser = adminCurrentUser();

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Kategori Blog | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
        <script src="app.js" defer></script>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'categories';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Kelola Kategori Blog',
                    'pageDescription' => 'Halo ' . htmlspecialchars($currentUser['name'] ?? $currentUser['username'], ENT_QUOTES) . ', kelola taksonomi kontenmu di sini.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Konten'],
                        ['label' => 'Kategori'],
                    ],
                    'quickActions' => [
                        [
                            'label' => 'Artikel',
                            'href' => admin_url('articles'),
                            'variant' => 'ghost',
                        ],
                    ],
                ]);
            ?>
            <div class="workspace-content">
                <section class="workspace-meta">
                    <span class="status-pill">Kategori: <?= number_format($totalCategories) ?></span>
                    <span class="chip chip--success">Artikel tertag: <?= number_format($totalTaggedArticles) ?></span>
                </section>

                <section class="card">
                <header>
                    <h2><?= $mode === 'edit' ? 'Edit Kategori' : 'Tambah Kategori' ?></h2>
                    <?php if ($mode === 'edit'): ?>
                        <a class="btn btn-outline" href="<?= admin_url('categories') ?>">Batal</a>
                    <?php endif; ?>
                </header>
                <form method="post" class="form-grid">
                    <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">
                    <?php if ($mode === 'edit' && $editId !== null): ?>
                        <input type="hidden" name="id" value="<?= (int) $editId ?>">
                    <?php endif; ?>
                    <label class="form-field">
                        <span>Nama Kategori</span>
                        <input type="text" name="name" required value="<?= htmlspecialchars($formData['name'], ENT_QUOTES) ?>" placeholder="Contoh: Teknologi Aspal">
                    </label>
                    <label class="form-field">
                        <span>Slug</span>
                        <input type="text" name="slug" value="<?= htmlspecialchars($formData['slug'], ENT_QUOTES) ?>" placeholder="teknologi-aspal">
                        <small>Slug otomatis dari nama jika dikosongkan.</small>
                    </label>
                    <label class="form-field full">
                        <span>Deskripsi</span>
                        <textarea name="description" rows="4" placeholder="Deskripsi singkat kategori"><?= htmlspecialchars($formData['description'], ENT_QUOTES) ?></textarea>
                    </label>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Kategori' ?>
                        </button>
                    </div>
                </form>
            </section>

            <section class="card table-card">
                <header>
                    <h2>Daftar Kategori</h2>
                </header>
                <div class="table-responsive table-responsive--flush">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Slug</th>
                                <th>Deskripsi</th>
                                <th>Artikel</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($categories === []): ?>
                                <tr>
                                    <td colspan="6">Belum ada kategori.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $categoryRow): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($categoryRow['name'], ENT_QUOTES) ?></td>
                                        <td><code><?= htmlspecialchars($categoryRow['slug'], ENT_QUOTES) ?></code></td>
                                        <td><?= htmlspecialchars($categoryRow['description'] ?? '—', ENT_QUOTES) ?></td>
                                        <td><?= number_format((int) $categoryRow['posts_count']) ?></td>
                                        <td>
                                            <?php
                                            $createdAt = '—';
                                            if (!empty($categoryRow['created_at'])) {
                                                try {
                                                    $createdAt = (new DateTime($categoryRow['created_at']))->format('d M Y');
                                                } catch (Throwable $ignored) {
                                                    $createdAt = (string) $categoryRow['created_at'];
                                                }
                                            }
                                            ?>
                                            <?= htmlspecialchars($createdAt, ENT_QUOTES) ?>
                                        </td>
                                        <td class="table-actions">
                                            <a class="btn btn-outline" href="<?= admin_url('categories') ?>?edit=<?= (int) $categoryRow['id'] ?>">Edit</a>
                                            <form method="post" onsubmit="return confirm('Hapus kategori ini?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $categoryRow['id'] ?>">
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


