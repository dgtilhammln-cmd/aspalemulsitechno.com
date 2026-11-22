<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireAuth();

$currentUser = adminCurrentUser();
$dashboard = adminGetDashboardSummary();

/**
 * MUNG CMS V1
 * File: admin/dashboard.php
 * Author: Firmanda Himawan
 * Description: Executive summary dashboard for monitoring content and catalog performance.
 */

/**
 * Baseline target per metrik supaya progress bar terasa hidup.
 * Angka bisa disesuaikan kapan pun tanpa menyentuh query.
 */
$metricTargets = [
    'Total Admin' => 4,
    'Artikel Terbit' => 30,
    'Produk Aktif' => 12,
    'Layanan Aktif' => 8,
    'Draft Artikel' => 8,
    'Kategori Konten' => 10,
];

$metrics = array_map(
    static function (array $metric) use ($metricTargets): array {
        $label = (string) ($metric['label'] ?? '');
        $value = (int) ($metric['value'] ?? 0);
        $accent = (string) ($metric['accent'] ?? 'primary');
        $target = $metricTargets[$label] ?? null;
        $progress = null;
        $statusLabel = null;

        if ($target !== null && $target > 0) {
            $progress = max(0, min(100, (int) round(($value / $target) * 100)));
            if ($progress >= 100) {
                $statusLabel = 'Target tercapai';
            } else {
                $remaining = max(0, $target - $value);
                $statusLabel = sprintf('%d lagi menuju target', $remaining);
            }
        }

        return [
            'label' => $label,
            'value' => $value,
            'accent' => $accent,
            'target' => $target,
            'progress' => $progress,
            'status_label' => $statusLabel,
        ];
    },
    $dashboard['metrics']
);

$metricsByLabel = [];
foreach ($metrics as $metric) {
    $metricsByLabel[$metric['label']] = $metric;
}

$primaryNav = [
    [
        'label' => 'Overview',
        'href' => admin_url('dashboard'),
        'prefix' => 'OV',
        'active' => true,
    ],
    [
        'label' => 'Invoices',
        'href' => '#',
        'prefix' => 'IN',
        'disabled' => true,
    ],
    [
        'label' => 'Payments',
        'href' => '#',
        'prefix' => 'PM',
        'disabled' => true,
    ],
    [
        'label' => 'Recurring',
        'href' => '#',
        'prefix' => 'RC',
        'disabled' => true,
    ],
    [
        'label' => 'Checkout',
        'href' => '#',
        'prefix' => 'CK',
        'disabled' => true,
    ],
];

$quickActions = [
    [
        'label' => 'Buat Artikel',
        'href' => admin_url('articles'),
        'variant' => 'primary',
    ],
    [
        'label' => 'Tambah Produk',
        'href' => admin_url('products'),
        'variant' => 'ghost',
    ],
];

$filters = [
    ['label' => 'Semua Channel', 'variant' => 'default'],
    ['label' => 'Konten Published', 'variant' => 'success'],
    ['label' => date('F Y'), 'variant' => 'neutral'],
];

$recentPosts = $dashboard['recent_posts'];
$seoAlerts = $dashboard['seo_alerts'];

$draftCount = (int) ($metricsByLabel['Draft Artikel']['value'] ?? 0);
$publishedCount = (int) ($metricsByLabel['Artikel Terbit']['value'] ?? 0);
$categoryCount = (int) ($metricsByLabel['Kategori Konten']['value'] ?? 0);
$seoAlertCount = count($seoAlerts);

?><!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Dashboard Admin</title>
        <link rel="stylesheet" href="admin.css?v=<?= file_exists(__DIR__ . '/admin.css') ? filemtime(__DIR__ . '/admin.css') : time() ?>">
        <script src="app.js" defer></script>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'dashboard';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Dashboard',
                    'pageDescription' => 'Control center untuk memantau performa konten, produk, dan SEO dalam satu layar.',
                    'primaryNav' => $primaryNav,
                    'quickActions' => $quickActions,
                ]);
            ?>

            <div class="workspace-content">
                <section class="kpi-grid">
                    <?php foreach (array_slice($metrics, 0, 4) as $metric): ?>
                        <article class="kpi-card kpi-card--<?= htmlspecialchars($metric['accent'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                            <div class="kpi-card__meta">
                                <span class="kpi-card__label"><?= htmlspecialchars($metric['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                <?php if ($metric['target'] !== null): ?>
                                    <span class="kpi-card__target">Target <?= number_format((int) $metric['target']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="kpi-card__value"><?= number_format((int) $metric['value']) ?></div>
                            <?php if ($metric['progress'] !== null): ?>
                                <div class="kpi-card__progress" role="progressbar" aria-valuenow="<?= (int) $metric['progress'] ?>" aria-valuemin="0" aria-valuemax="100">
                                    <span style="width: <?= (int) $metric['progress'] ?>%"></span>
                                </div>
                                <?php if ($metric['status_label'] !== null): ?>
                                    <p class="kpi-card__hint"><?= htmlspecialchars($metric['status_label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </section>

                <section class="insight-grid">
                    <article class="panel">
                        <header class="panel__header">
                            <span class="chip chip--neutral">Pipeline</span>
                            <h3><?= $draftCount ?> draft aktif</h3>
                        </header>
                        <p>Gunakan backlog draft sebagai buffer sebelum jadwal publish. Review cepat memastikan kualitas konten tetap konsisten.</p>
                        <div class="panel__stats">
                            <div>
                                <strong><?= $draftCount ?></strong>
                                <small>Draft</small>
                            </div>
                            <div>
                                <strong><?= $seoAlertCount ?></strong>
                                <small>Butuh SEO</small>
                            </div>
                            <div>
                                <strong><?= $categoryCount ?></strong>
                                <small>Kategori</small>
                            </div>
                        </div>
                    </article>
                    <article class="panel panel--accent">
                        <header class="panel__header">
                            <span class="chip chip--success">Publikasi</span>
                            <h3><?= $publishedCount ?> konten live</h3>
                        </header>
                        <p>Ritme publikasi stabil. Pertimbangkan untuk menyiapkan kampanye baru agar channel tetap aktif.</p>
                        <a class="btn btn-ghost" href="<?= admin_url('analytics') ?>">Lihat detail analytics</a>
                    </article>
                </section>

                <section class="filter-bar">
                    <div class="filter-bar__label">Active filters</div>
                    <div class="filter-bar__chips">
                        <?php foreach ($filters as $filter): ?>
                            <span class="chip chip--<?= htmlspecialchars($filter['variant'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                                <?= htmlspecialchars($filter['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="board">
                    <div class="board__primary">
                        <header class="board__header">
                            <div>
                                <h2>Konten Terbaru</h2>
                                <p>5 update terakhir dari tim editorial.</p>
                            </div>
                            <a class="btn btn-outline" href="<?= admin_url('articles') ?>">Kelola blog</a>
                        </header>
                        <div class="board__tabs" role="tablist" aria-label="Segmentasi konten">
                            <span class="board-tab is-active" role="tab" aria-selected="true">
                                Semua
                                <span class="board-tab__badge"><?= count($recentPosts) ?></span>
                            </span>
                            <span class="board-tab" role="tab" aria-selected="false">
                                Draft
                                <span class="board-tab__badge"><?= $draftCount ?></span>
                            </span>
                            <span class="board-tab" role="tab" aria-selected="false">
                                Published
                                <span class="board-tab__badge"><?= $publishedCount ?></span>
                            </span>
                        </div>
                        <div class="table-responsive table-responsive--flush">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Judul</th>
                                        <th scope="col">Penulis</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Terbit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentPosts === []): ?>
                                        <tr>
                                            <td colspan="4" class="table-empty">Belum ada artikel.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentPosts as $post): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($post['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($post['author'] ?? '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                                <td>
                                                    <?php if (($post['status'] ?? '') === 'published'): ?>
                                                        <span class="badge badge-success">Published</span>
                                                    <?php elseif (($post['status'] ?? '') === 'draft'): ?>
                                                        <span class="badge badge-warning">Draft</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-info"><?= htmlspecialchars((string) ($post['status'] ?? 'unknown'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                        $publishedLabel = '—';
                                                        if (!empty($post['published_at'])) {
                                                            try {
                                                                $publishedLabel = (new DateTime($post['published_at']))->format('d M Y H:i');
                                                            } catch (Throwable $ignored) {
                                                                $publishedLabel = (string) $post['published_at'];
                                                            }
                                                        }
                                                    ?>
                                                    <?= htmlspecialchars($publishedLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <aside class="board__secondary">
                        <article class="panel panel--stack">
                            <header class="panel__header">
                                <span class="chip chip--warning">SEO Alerts</span>
                                <h3><?= $seoAlertCount ?> konten perlu optimasi</h3>
                            </header>
                            <?php if ($seoAlerts === []): ?>
                                <p class="empty-state">Semua konten utama sudah punya meta tag dasar.</p>
                            <?php else: ?>
                                <ul class="seo-alert-list">
                                    <?php foreach ($seoAlerts as $alert): ?>
                                        <li class="seo-alert">
                                            <div class="seo-alert__heading">
                                                <strong><?= htmlspecialchars(strtoupper((string) $alert['owner_type']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> #<?= (int) $alert['owner_id'] ?></strong>
                                                <span class="seo-alert__status <?= $alert['meta_title'] !== null && $alert['meta_title'] !== '' ? 'is-ok' : 'is-missing' ?>">
                                                    <?= $alert['meta_title'] !== null && $alert['meta_title'] !== '' ? 'Title OK' : 'Title kosong' ?>
                                                </span>
                                            </div>
                                            <div class="seo-alert__meta">
                                                <span>Desc: <?= $alert['meta_description'] !== null && strlen((string) $alert['meta_description']) >= 110 ? 'OK' : 'Pendek' ?></span>
                                                <span>Keyword: <?= $alert['meta_keywords'] !== null && $alert['meta_keywords'] !== '' ? 'OK' : 'Kosong' ?></span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <a class="btn btn-primary" href="<?= admin_url('settings') ?>">Update SEO</a>
                        </article>
                    </aside>
                </section>
            </div>
        </main>
    </body>
</html>

