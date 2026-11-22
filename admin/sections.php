<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireAuth();

$pdo = getPDO();

/**
 * MUNG CMS V1
 * File: admin/sections.php
 * Author: Firmanda Himawan
 * Description: Content management for sections across different pages.
 */

$errors = [];
$notices = [];

// Define available pages and sections
$availablePages = [
    'blog-index' => [
        'label' => 'Blog Index',
        'sections' => [
            'hero-kicker' => [
                'label' => 'Kicker: Hero Blog',
                'description' => 'Text untuk kicker/badge hero blog (tanpa tag HTML)',
                'default' => 'Wawasan Lapangan',
                'html_tag' => 'span',
                'html_class' => 'section-kicker',
            ],
            'hero-heading' => [
                'label' => 'Heading: Hero Blog',
                'description' => 'Text heading utama hero blog (tanpa tag HTML)',
                'default' => 'Artikel & Insight Terbaru',
                'html_tag' => 'h1',
                'html_class' => 'blog-index__title',
            ],
            'hero-description' => [
                'label' => 'Deskripsi: Hero Blog',
                'description' => 'Text deskripsi hero blog (tanpa tag HTML)',
                'default' => 'Kompilasi insight teknis, studi kasus, dan best practice lapangan langsung dari tim engineer Aspal Emulsi.',
                'html_tag' => 'p',
                'html_class' => 'blog-index__subtitle',
            ],
            'hero-cta-label' => [
                'label' => 'Label Tombol Hero',
                'description' => 'Text untuk label tombol CTA hero blog (tanpa tag HTML)',
                'default' => 'Ngobrol dengan Engineer',
            ],
            'topics-heading' => [
                'label' => 'Heading: Filter Topik',
                'description' => 'Text untuk heading section filter topik (tanpa tag HTML)',
                'default' => 'Pilih topik artikel',
                'html_tag' => 'span',
                'html_class' => 'section-kicker section-kicker--subtle',
            ],
            'topics-description' => [
                'label' => 'Deskripsi: Filter Topik',
                'description' => 'Text deskripsi singkat filter topik (tanpa tag HTML)',
                'default' => 'Filter insight berdasarkan tag yang kamu butuhkan.',
                'html_tag' => 'p',
                'html_class' => 'blog-topics__description',
            ],
            'empty-heading' => [
                'label' => 'Heading: Empty State',
                'description' => 'Text heading saat belum ada artikel (tanpa tag HTML)',
                'default' => 'Konten segera hadir!',
                'html_tag' => 'h2',
            ],
            'empty-description' => [
                'label' => 'Deskripsi: Empty State',
                'description' => 'Text deskripsi saat belum ada artikel (tanpa tag HTML)',
                'default' => 'Kami sedang menyiapkan insight terbaru. Pantau terus blog Aspal Emulsi untuk update project dan studi kasus menarik.',
                'html_tag' => 'p',
            ],
        ],
    ],
    'blog-detail' => [
        'label' => 'Blog Detail',
        'sections' => [
            'toc-heading' => [
                'label' => 'Heading: Isi Artikel',
                'description' => 'Text untuk heading table of contents (tanpa tag HTML)',
                'default' => 'Isi Artikel',
                'html_tag' => 'h2',
            ],
            'workflow-heading' => [
                'label' => 'Heading: Workflow Eksekusi',
                'description' => 'Text untuk heading section timeline/workflow (tanpa tag HTML)',
                'default' => 'Workflow Eksekusi',
                'html_tag' => 'h2',
            ],
            'highlights-heading' => [
                'label' => 'Heading: Highlights Project',
                'description' => 'Text untuk heading section statistik/highlights (tanpa tag HTML)',
                'default' => 'Highlights Project',
                'html_tag' => 'h2',
            ],
            'checklist-heading' => [
                'label' => 'Heading: Checklist Maintenance',
                'description' => 'Text untuk heading section checklist (tanpa tag HTML)',
                'default' => 'Checklist Maintenance',
                'html_tag' => 'h2',
            ],
            'related-kicker' => [
                'label' => 'Kicker: Related Articles',
                'description' => 'Text untuk kicker/badge section artikel terkait (tanpa tag HTML)',
                'default' => 'Artikel Lainnya',
                'html_tag' => 'span',
                'html_class' => 'section-kicker',
            ],
            'related-heading' => [
                'label' => 'Heading: Related Articles',
                'description' => 'Text untuk heading section artikel terkait (tanpa tag HTML)',
                'default' => 'Upgrade Insight Lapangan Kamu',
                'html_tag' => 'h2',
            ],
            'related-description' => [
                'label' => 'Deskripsi: Related Articles',
                'description' => 'Text untuk deskripsi section artikel terkait (tanpa tag HTML)',
                'default' => 'Referensi tambahan buat optimasi proyek infrastruktur dan waterproofing.',
                'html_tag' => 'p',
            ],
            'cta-badge' => [
                'label' => 'Badge: CTA Section',
                'description' => 'Text untuk badge CTA section di blog detail (tanpa tag HTML)',
                'default' => 'Siap bersinergi',
                'html_tag' => 'span',
                'html_class' => 'cta-badge',
            ],
            'cta-heading' => [
                'label' => 'Heading: CTA Section',
                'description' => 'Text untuk heading CTA section di blog detail (tanpa tag HTML)',
                'default' => 'Yuk Planning Project Bareng!',
                'html_tag' => 'h2',
                'html_class' => 'cta-title',
            ],
            'cta-description' => [
                'label' => 'Deskripsi: CTA Section',
                'description' => 'Text untuk deskripsi CTA section di blog detail (tanpa tag HTML)',
                'default' => 'Dari survei, rekomendasi formulasi, sampai eksekusi lapangan, kita siap bantu kamu deliver hasil yang konsisten dan tahan lama.',
                'html_tag' => 'p',
                'html_class' => 'cta-text',
            ],
            'coverage-text' => [
                'label' => 'Coverage Text',
                'description' => 'Text untuk coverage/pengiriman di blog detail (tanpa tag HTML)',
                'default' => 'Pengiriman & Pengerjaan Seluruh Indonesia',
                'html_tag' => 'p',
                'html_class' => 'coverage-text',
                'html_style' => 'margin-top: -136px; text-align: center;',
            ],
        ],
    ],
    'product-detail' => [
        'label' => 'Product Detail',
        'sections' => [
            'description-heading' => [
                'label' => 'Heading: Deskripsi Produk',
                'description' => 'Text untuk heading deskripsi produk (tanpa tag HTML). Gunakan {product_name} sebagai placeholder untuk nama produk.',
                'default' => 'Kenapa {product_name}?',
                'html_tag' => 'h2',
            ],
            'related-kicker' => [
                'label' => 'Kicker: Related Products',
                'description' => 'Text untuk kicker/badge section produk terkait (tanpa tag HTML)',
                'default' => 'Produk Terkait',
                'html_tag' => 'span',
                'html_class' => 'section-kicker',
            ],
            'related-heading' => [
                'label' => 'Heading: Related Products',
                'description' => 'Text untuk heading section produk terkait (tanpa tag HTML)',
                'default' => 'Lengkapi Workflow Waterproofing Kamu',
                'html_tag' => 'h2',
            ],
            'related-description' => [
                'label' => 'Deskripsi: Related Products',
                'description' => 'Text untuk deskripsi section produk terkait (tanpa tag HTML)',
                'default' => 'Mix and match material pendukung supaya hasil lapisan makin rapat dan tahan lama.',
                'html_tag' => 'p',
            ],
            'cta-badge' => [
                'label' => 'Badge: CTA Section',
                'description' => 'Text untuk badge CTA section di product detail (tanpa tag HTML)',
                'default' => 'Siap bersinergi',
                'html_tag' => 'span',
                'html_class' => 'cta-badge',
            ],
            'cta-heading' => [
                'label' => 'Heading: CTA Section',
                'description' => 'Text untuk heading CTA section di product detail (tanpa tag HTML)',
                'default' => 'Butuh Volume Project Lebih Besar?',
                'html_tag' => 'h2',
                'html_class' => 'cta-title',
            ],
            'cta-description' => [
                'label' => 'Deskripsi: CTA Section',
                'description' => 'Text untuk deskripsi CTA section di product detail (tanpa tag HTML)',
                'default' => 'Tim engineer kami siap bantu survei, rekomendasi formulasi, hingga eksekusi lapangan. Konsultasikan kebutuhan project kamu sekarang juga.',
                'html_tag' => 'p',
                'html_class' => 'cta-text',
            ],
            'coverage-text' => [
                'label' => 'Coverage Text',
                'description' => 'Text untuk coverage/pengiriman di product detail (tanpa tag HTML)',
                'default' => 'Pengiriman & Pengerjaan Seluruh Indonesia',
                'html_tag' => 'p',
                'html_class' => 'coverage-text',
                'html_style' => 'margin-top: -136px; text-align: center;',
            ],
        ],
    ],
    'home' => [
        'label' => 'Homepage',
        'sections' => [
            'services-heading' => [
                'label' => 'Heading: Layanan Unggulan',
                'description' => 'Text untuk heading section layanan (tanpa tag HTML)',
                'default' => 'Solusi Terintegrasi Aspal Emulsi & Waterproofing',
                'html_tag' => 'h2',
            ],
            'services-description' => [
                'label' => 'Deskripsi: Layanan',
                'description' => 'Text untuk deskripsi section layanan (tanpa tag HTML)',
                'default' => 'Kami tangani dari preparasi permukaan hingga finishing akhir dengan formulasi emulsi pilihan, memastikan daya lekat maksimum plus ketahanan cuaca ekstrem.',
                'html_tag' => 'p',
            ],
            'products-heading' => [
                'label' => 'Heading: Produk',
                'description' => 'Text untuk heading section produk (tanpa tag HTML)',
                'default' => 'Solusi Produk Aspal Untuk Workflow Pengerjaan Lebih Mulus',
                'html_tag' => 'h2',
            ],
            'products-description' => [
                'label' => 'Deskripsi: Produk',
                'description' => 'Text untuk deskripsi section produk (tanpa tag HTML)',
                'default' => 'Formulasi emulsi kami siap pakai untuk berbagai skenario konstruksi jalan, jembatan, dan bangunan. Tingkatkan kecepatan pengerjaan tanpa mengorbankan mutu lapisan.',
                'html_tag' => 'p',
                'html_class' => 'products-subtitle',
            ],
            'gallery-heading' => [
                'label' => 'Heading: Gallery',
                'description' => 'Text untuk heading section gallery (tanpa tag HTML)',
                'default' => 'Gallery Pengerjaan',
                'html_tag' => 'h2',
                'html_class' => 'gallery-title',
            ],
            'gallery-description' => [
                'label' => 'Deskripsi: Gallery',
                'description' => 'Text untuk deskripsi section gallery (tanpa tag HTML)',
                'default' => 'Pengerjaan dilakukan dengan standar keamanan, menggunakan bahan baku berkualitas dan tenaga kerja berpengalaman, sehingga menghasilkan lapisan yang kuat, teruji, dan tahan lama.',
                'html_tag' => 'p',
                'html_class' => 'gallery-intro',
            ],
            'blog-heading' => [
                'label' => 'Heading: Blog',
                'description' => 'Text untuk heading section blog (tanpa tag HTML)',
                'default' => 'Insight Teknis & Tips Perawatan Permukaan',
                'html_tag' => 'h2',
            ],
            'blog-description' => [
                'label' => 'Deskripsi: Blog',
                'description' => 'Text untuk deskripsi section blog (tanpa tag HTML)',
                'default' => 'Update terbaru dari tim engineer kami seputar teknologi emulsi, manajemen pemeliharaan jalan, serta strategi efisiensi supaya proyek kamu lebih presisi dan mudah di-maintain.',
                'html_tag' => 'p',
            ],
            'testimonials-heading' => [
                'label' => 'Heading: Testimonials',
                'description' => 'Text untuk heading section testimonials (tanpa tag HTML)',
                'default' => 'Partner Kami Bicara Soal Hasil Akhir',
                'html_tag' => 'h2',
            ],
            'testimonials-description' => [
                'label' => 'Deskripsi: Testimonials',
                'description' => 'Text untuk deskripsi section testimonials (tanpa tag HTML)',
                'default' => 'Dari akses kampus, kawasan industri sampai jalur logistik, mereka percaya sama kualitas layer dan servis aftersales kami.',
                'html_tag' => 'p',
            ],
            'services-kicker' => [
                'label' => 'Kicker: Layanan',
                'description' => 'Text untuk kicker/badge section layanan (tanpa tag HTML)',
                'default' => 'Layanan Unggulan',
                'html_tag' => 'span',
                'html_class' => 'services2-kicker',
            ],
            'blog-kicker' => [
                'label' => 'Kicker: Blog',
                'description' => 'Text untuk kicker/badge section blog (tanpa tag HTML)',
                'default' => 'Wawasan Lapangan',
                'html_tag' => 'span',
                'html_class' => 'blog-kicker',
            ],
            'testimonials-kicker' => [
                'label' => 'Kicker: Testimonials',
                'description' => 'Text untuk kicker/badge section testimonials (tanpa tag HTML)',
                'default' => 'Suara Klien',
                'html_tag' => 'span',
                'html_class' => 'testimonials-kicker',
            ],
            'cta-badge' => [
                'label' => 'Badge: CTA Section',
                'description' => 'Text untuk badge CTA section di homepage (tanpa tag HTML)',
                'default' => 'Siap bersinergi',
                'html_tag' => 'span',
                'html_class' => 'cta-badge',
            ],
            'cta-heading' => [
                'label' => 'Heading: CTA Section',
                'description' => 'Text untuk heading CTA section di homepage (tanpa tag HTML)',
                'default' => 'Percayakan Ke-Ahlinya!',
                'html_tag' => 'h2',
                'html_class' => 'cta-title',
            ],
            'cta-description' => [
                'label' => 'Deskripsi: CTA Section',
                'description' => 'Text untuk deskripsi CTA section di homepage (tanpa tag HTML)',
                'default' => 'Telah menyelesaikan puluhan proyek dan ribuan pesanan dengan hasil kuat, tahan lama, dan terpercaya. Hubungi kami untuk konsultasi pengerjaan, atau checkout produk langsung melalui e-commerce kami.',
                'html_tag' => 'p',
                'html_class' => 'cta-text',
            ],
            'coverage-text' => [
                'label' => 'Coverage Text',
                'description' => 'Text untuk coverage/pengiriman (tanpa tag HTML)',
                'default' => 'Pengiriman & Pengerjaan Seluruh Indonesia',
                'html_tag' => 'p',
                'html_class' => 'coverage-text',
                'html_style' => 'margin-top: -136px; text-align: center;',
            ],
        ],
    ],
];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pageIdentifier = trim($_POST['page_identifier'] ?? '');
    $sectionKey = trim($_POST['section_key'] ?? '');
    $contentHtml = trim($_POST['content_html'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($pageIdentifier === '') {
        $errors[] = 'Page identifier wajib diisi.';
    }

    if ($sectionKey === '') {
        $errors[] = 'Section key wajib diisi.';
    }

    if (!isset($availablePages[$pageIdentifier])) {
        $errors[] = 'Page identifier tidak valid.';
    }

    if (isset($availablePages[$pageIdentifier]) && !isset($availablePages[$pageIdentifier]['sections'][$sectionKey])) {
        $errors[] = 'Section key tidak valid untuk page ini.';
    }

    if ($errors === []) {
        $stmt = $pdo->prepare(
            'INSERT INTO content_sections (page_identifier, section_key, content_html, description)
             VALUES (:page_identifier, :section_key, :content_html, :description)
             ON DUPLICATE KEY UPDATE
                 content_html = :content_html_update,
                 description = :description_update,
                 updated_at = CURRENT_TIMESTAMP'
        );

        $stmt->execute([
            'page_identifier' => $pageIdentifier,
            'section_key' => $sectionKey,
            'content_html' => $contentHtml !== '' ? $contentHtml : null,
            'description' => $description !== '' ? $description : null,
            'content_html_update' => $contentHtml !== '' ? $contentHtml : null,
            'description_update' => $description !== '' ? $description : null,
        ]);

        admin_redirect('sections', ['notice' => 'saved', 'page' => $pageIdentifier]);
    }
}

// Handle DELETE requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
    $pageIdentifier = trim($_POST['page_identifier'] ?? '');
    $sectionKey = trim($_POST['section_key'] ?? '');

    if ($pageIdentifier !== '' && $sectionKey !== '') {
        $stmt = $pdo->prepare('DELETE FROM content_sections WHERE page_identifier = :page_identifier AND section_key = :section_key');
        $stmt->execute([
            'page_identifier' => $pageIdentifier,
            'section_key' => $sectionKey,
        ]);

        admin_redirect('sections', ['notice' => 'deleted', 'page' => $pageIdentifier]);
    }
}

// Fetch all sections from database
$sectionsMap = [];
$stmt = $pdo->query('SELECT page_identifier, section_key, content_html, description FROM content_sections');
while ($row = $stmt->fetch()) {
    $pageId = $row['page_identifier'];
    $sectionKey = $row['section_key'];
    if (!isset($sectionsMap[$pageId])) {
        $sectionsMap[$pageId] = [];
    }
    $sectionsMap[$pageId][$sectionKey] = [
        'content_html' => $row['content_html'], // Store text only (no HTML tags)
        'description' => $row['description'],
    ];
}

// Get current page from query string
$currentPage = $_GET['page'] ?? 'blog-detail';
if (!isset($availablePages[$currentPage])) {
    $currentPage = 'blog-detail';
}

$currentPageConfig = $availablePages[$currentPage];
$currentPageSections = $currentPageConfig['sections'] ?? [];

// Check for notices
if (isset($_GET['notice'])) {
    if ($_GET['notice'] === 'saved') {
        $notices[] = ['type' => 'success', 'message' => 'Section berhasil disimpan.'];
    } elseif ($_GET['notice'] === 'deleted') {
        $notices[] = ['type' => 'success', 'message' => 'Section berhasil dihapus.'];
    }
}

$sidebarActive = 'sections';

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Konten Section | Admin</title>
        <link rel="stylesheet" href="admin.css">
        <script src="app.js" defer></script>
    </head>
    <body class="dashboard">
        <?php
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Konten Section',
                    'pageDescription' => 'Kelola konten section untuk berbagai halaman website.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Konten Section'],
                    ],
                    'quickActions' => [
                        [
                            'label' => 'Lihat Website',
                            'href' => '/',
                            'variant' => 'outline',
                        ],
                    ],
                ]);
            ?>
            <div class="workspace-content">
                <?php if (!empty($notices)): ?>
                    <div class="alert alert-success">
                        <?php foreach ($notices as $notice): ?>
                            <p><?= htmlspecialchars($notice['message'], ENT_QUOTES) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error, ENT_QUOTES) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <section class="card">
                    <header>
                        <h2>Manage Content Sections</h2>
                        <p>Kelola konten section untuk berbagai halaman website.</p>
                    </header>

        <div class="tabs">
            <?php foreach ($availablePages as $pageId => $pageConfig): ?>
                <a href="<?= admin_url('sections?page=' . urlencode($pageId)) ?>" class="tab <?= $currentPage === $pageId ? 'is-active' : '' ?>">
                    <?= htmlspecialchars($pageConfig['label'], ENT_QUOTES) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="sections-list">
            <?php foreach ($currentPageSections as $sectionKey => $sectionConfig): ?>
                <?php
                $sectionData = $sectionsMap[$currentPage][$sectionKey] ?? null;
                $contentText = $sectionData['content_html'] ?? $sectionConfig['default'] ?? '';
                $description = $sectionData['description'] ?? $sectionConfig['description'] ?? '';
                $isCustomized = $sectionData !== null && $sectionData['content_html'] !== null;
                
                // Get HTML tag info from config
                $htmlTag = $sectionConfig['html_tag'] ?? 'div';
                $htmlClass = $sectionConfig['html_class'] ?? null;
                $htmlStyle = $sectionConfig['html_style'] ?? null;
                ?>
                <div class="section-item">
                    <div class="section-header">
                        <div>
                            <h3><?= htmlspecialchars($sectionConfig['label'], ENT_QUOTES) ?></h3>
                            <p class="section-description"><?= htmlspecialchars($sectionConfig['description'], ENT_QUOTES) ?></p>
                            <?php if (isset($htmlTag)): ?>
                                <p class="section-meta">
                                    <small>HTML Tag: <code><?= htmlspecialchars($htmlTag, ENT_QUOTES) ?></code><?= $htmlClass ? ' | Class: <code>' . htmlspecialchars($htmlClass, ENT_QUOTES) . '</code>' : '' ?></small>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php if ($isCustomized): ?>
                            <span class="badge badge-success">✓ Customized</span>
                        <?php else: ?>
                            <span class="badge badge-default">Default</span>
                        <?php endif; ?>
                    </div>

                    <form method="post" class="section-form">
                        <input type="hidden" name="page_identifier" value="<?= htmlspecialchars($currentPage, ENT_QUOTES) ?>">
                        <input type="hidden" name="section_key" value="<?= htmlspecialchars($sectionKey, ENT_QUOTES) ?>">

                        <label class="form-field full">
                            <span>Content Text</span>
                            <input type="text" name="content_html" value="<?= htmlspecialchars($contentText, ENT_QUOTES) ?>" placeholder="<?= htmlspecialchars($sectionConfig['default'] ?? '', ENT_QUOTES) ?>">
                            <small>Masukkan text saja (tanpa tag HTML). Tag HTML akan ditambahkan otomatis di frontend.</small>
                        </label>

                        <label class="form-field full">
                            <span>Description (Optional)</span>
                            <input type="text" name="description" value="<?= htmlspecialchars($description, ENT_QUOTES) ?>" placeholder="<?= htmlspecialchars($sectionConfig['description'] ?? '', ENT_QUOTES) ?>">
                        </label>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <?php if ($isCustomized): ?>
                                <button type="button" class="btn btn-outline" onclick="if(confirm('Yakin ingin menghapus custom content dan kembali ke default?')) { document.getElementById('delete-form-<?= htmlspecialchars($sectionKey, ENT_QUOTES) ?>').submit(); }">
                                    Reset ke Default
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if ($isCustomized): ?>
                        <form id="delete-form-<?= htmlspecialchars($sectionKey, ENT_QUOTES) ?>" method="post" style="display: none;">
                            <input type="hidden" name="_method" value="DELETE">
                            <input type="hidden" name="page_identifier" value="<?= htmlspecialchars($currentPage, ENT_QUOTES) ?>">
                            <input type="hidden" name="section_key" value="<?= htmlspecialchars($sectionKey, ENT_QUOTES) ?>">
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
                </section>
            </div>
        </main>
        <style>
/* Tabs styling - consistent with admin panel */
.tabs {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 32px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.16);
}

.tab {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    border-radius: 16px;
    background: rgba(148, 163, 184, 0.08);
    color: var(--text-muted);
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.25s ease;
    font-size: 0.9rem;
    font-weight: 500;
    letter-spacing: 0.02em;
}

.tab:hover {
    color: var(--text-primary);
    background: rgba(148, 163, 184, 0.14);
    transform: translateY(-1px);
}

.tab.is-active {
    background: linear-gradient(135deg, rgba(126, 252, 43, 0.2), rgba(56, 245, 199, 0.2));
    border-color: rgba(126, 252, 43, 0.45);
    color: var(--text-primary);
    box-shadow: 0 8px 24px -12px rgba(126, 252, 43, 0.6);
    font-weight: 600;
}

/* Sections list */
.sections-list {
    display: flex;
    flex-direction: column;
    gap: 24px;
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.section-item {
    padding: 28px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: var(--radius-lg);
    background: linear-gradient(145deg, rgba(20, 32, 56, 0.96) 0%, rgba(9, 15, 30, 0.94) 100%);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    animation: slideIn 0.5s ease-out backwards;
}

.section-item:nth-child(1) { animation-delay: 0.05s; }
.section-item:nth-child(2) { animation-delay: 0.1s; }
.section-item:nth-child(3) { animation-delay: 0.15s; }
.section-item:nth-child(4) { animation-delay: 0.2s; }
.section-item:nth-child(5) { animation-delay: 0.25s; }
.section-item:nth-child(6) { animation-delay: 0.3s; }
.section-item:nth-child(n+7) { animation-delay: 0.35s; }

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.section-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, rgba(126, 252, 43, 0.6), rgba(56, 245, 199, 0.6));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.section-item:hover {
    border-color: rgba(126, 252, 43, 0.35);
    box-shadow: 0 20px 40px -20px rgba(126, 252, 43, 0.3);
    transform: translateY(-2px);
}

.section-item:hover::before {
    opacity: 1;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 24px;
}

.section-header > div {
    flex: 1;
}

.section-header h3 {
    margin: 0 0 8px 0;
    font-size: 1.15rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--text-primary);
}

.section-description {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin: 0;
    line-height: 1.6;
}

.section-form {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(148, 163, 184, 0.12);
}

.section-meta {
    margin: 8px 0 0 0;
    color: var(--text-subtle);
    font-size: 0.8rem;
}

.section-meta code {
    background: rgba(148, 163, 184, 0.12);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.75rem;
    color: var(--accent-secondary);
    font-family: 'Consolas', 'Monaco', monospace;
}

/* Badge styling - improved */
.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    border: 1px solid transparent;
    transition: all 0.2s ease;
    white-space: nowrap;
    flex-shrink: 0;
}

.badge-success {
    background: linear-gradient(135deg, rgba(56, 245, 199, 0.22), rgba(56, 245, 199, 0.16));
    color: rgba(56, 245, 199, 0.98);
    border-color: rgba(56, 245, 199, 0.45);
    box-shadow: 0 4px 16px -6px rgba(56, 245, 199, 0.5);
    text-shadow: 0 1px 2px rgba(56, 245, 199, 0.3);
}

.badge-success:hover {
    box-shadow: 0 6px 20px -6px rgba(56, 245, 199, 0.6);
    transform: scale(1.05);
}

.badge-default {
    background: rgba(148, 163, 184, 0.14);
    color: var(--text-muted);
    border-color: rgba(148, 163, 184, 0.28);
}

/* Form actions */
.form-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(148, 163, 184, 0.12);
}

.form-actions .btn {
    min-width: 120px;
}

/* Responsive */
@media (max-width: 768px) {
    .tabs {
        flex-direction: column;
        gap: 8px;
    }
    
    .tab {
        width: 100%;
        justify-content: center;
    }
    
    .section-item {
        padding: 20px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .section-header > div {
        width: 100%;
    }
    
    .badge {
        align-self: flex-start;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
    
}

/* Enhanced form field styling for sections */
.section-form .form-field {
    margin-bottom: 24px;
}

.section-form .form-field:last-of-type {
    margin-bottom: 0;
}

.section-form .form-field input[type="text"] {
    transition: all 0.2s ease;
    font-size: 0.95rem;
    line-height: 1.5;
}

.section-form .form-field input[type="text"]:focus {
    border-color: rgba(126, 252, 43, 0.6);
    box-shadow: 0 0 0 4px rgba(126, 252, 43, 0.15), 0 4px 12px -4px rgba(126, 252, 43, 0.3);
}

.section-form .form-field small {
    margin-top: 10px;
    color: var(--text-subtle);
    font-size: 0.8rem;
    line-height: 1.6;
    padding-left: 4px;
}

        </style>
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

