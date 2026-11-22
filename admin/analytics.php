<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * MUNG CMS V1
 * File: admin/analytics.php
 * Author: Firmanda Himawan
 * Description: Admin dashboard page that visualises real-time CTA clicks and blog engagement metrics.
 */

adminRequireAuth();

$filters = [
    'range' => $_GET['range'] ?? '7d',
    'start' => $_GET['start'] ?? '',
    'end' => $_GET['end'] ?? '',
    'channel' => $_GET['channel'] ?? '',
    'product' => $_GET['product'] ?? '',
    'post' => $_GET['post'] ?? '',
];

$productAnalytics = adminAnalyticsFetchProductClickAnalytics($filters);
$marketAnalytics = adminAnalyticsFetchMarketplaceAnalytics($filters);
$blogAnalytics = adminAnalyticsFetchContentViewAnalytics($filters);
$channels = adminAnalyticsDistinctChannels();
$productOptions = adminAnalyticsListProducts();
$postOptions = adminAnalyticsListBlogPosts();

$rangeMode = $productAnalytics['range']['mode'] ?? '7d';
$rangeLabel = $productAnalytics['range']['label'] ?? '7 Hari Terakhir';

$jsonOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;

$formatNumber = static fn (int $value): string => number_format($value);
$formatDateTime = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '—';
    }

    try {
        $date = new DateTimeImmutable($value);

        return $date->format('d M Y H:i');
    } catch (Throwable) {
        return (string) $value;
    }
};

function adminAnalyticsLocationLabel(?string $country, ?string $region, ?string $city): string
{
    $parts = array_filter([$city, $region, $country], static fn ($part) => is_string($part) && trim($part) !== '');

    return $parts === [] ? 'Tidak diketahui' : implode(', ', $parts);
}

$topProductChannel = $productAnalytics['channels'][0]['channel'] ?? null;
$topProductChannelTotal = (int) ($productAnalytics['channels'][0]['total'] ?? 0);
$topMarketplaceChannel = $marketAnalytics['channels'][0]['channel'] ?? null;
$topMarketplaceChannelTotal = (int) ($marketAnalytics['channels'][0]['total'] ?? 0);
$topPost = $blogAnalytics['posts'][0]['content_title'] ?? null;
$topPostTotal = (int) ($blogAnalytics['posts'][0]['total'] ?? 0);

?><!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Realtime Analytics | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
        <script src="app.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'analytics';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Realtime Analytics',
                    'pageDescription' => 'Monitor klik CTA produk & performa artikel dalam satu dashboard.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Analytics'],
                    ],
                ]);
            ?>
            <div class="workspace-content">
                <section class="workspace-meta">
                    <span class="status-pill">Per <?= htmlspecialchars($rangeLabel, ENT_QUOTES) ?></span>
                </section>

            <section class="card" data-animate="fade-up">
                <header>
                    <h2>Filter Laporan</h2>
                    <p class="card-subtitle">Tentukan rentang waktu, kanal CTA, produk, atau artikel spesifik.</p>
                </header>
                <form method="get" class="form-grid analytics-filter" autocomplete="off">
                    <div class="form-field">
                        <label for="filter-range">Rentang Waktu</label>
                        <select id="filter-range" name="range">
                            <?php
                            $ranges = [
                                'today' => 'Hari Ini',
                                '7d' => '7 Hari',
                                '30d' => '30 Hari',
                                '90d' => '90 Hari',
                                '12m' => '12 Bulan',
                                'custom' => 'Custom',
                            ];
                            foreach ($ranges as $value => $label):
                                $selected = $rangeMode === $value ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES) ?>" <?= $selected ?>><?= htmlspecialchars($label, ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field custom-range-field"<?= $rangeMode === 'custom' ? '' : ' hidden' ?>>
                        <label for="filter-start">Dari</label>
                        <input type="datetime-local" id="filter-start" name="start" value="<?= htmlspecialchars($filters['start'], ENT_QUOTES) ?>">
                    </div>
                    <div class="form-field custom-range-field"<?= $rangeMode === 'custom' ? '' : ' hidden' ?>>
                        <label for="filter-end">Sampai</label>
                        <input type="datetime-local" id="filter-end" name="end" value="<?= htmlspecialchars($filters['end'], ENT_QUOTES) ?>">
                    </div>
                    <div class="form-field">
                        <label for="filter-channel">Kanal CTA</label>
                        <select id="filter-channel" name="channel">
                            <option value="">Semua Kanal</option>
                            <?php foreach ($channels as $channel): ?>
                                <?php $selected = strtolower($filters['channel']) === strtolower($channel) ? 'selected' : ''; ?>
                                <option value="<?= htmlspecialchars($channel, ENT_QUOTES) ?>" <?= $selected ?>><?= htmlspecialchars(ucfirst($channel), ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="filter-product">Produk</label>
                        <input list="product-options" id="filter-product" name="product" placeholder="Slug / nama produk" value="<?= htmlspecialchars($filters['product'], ENT_QUOTES) ?>">
                        <datalist id="product-options">
                            <?php foreach ($productOptions as $product): ?>
                                <option value="<?= htmlspecialchars($product['slug'], ENT_QUOTES) ?>"><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-field">
                        <label for="filter-post">Artikel</label>
                        <input list="post-options" id="filter-post" name="post" placeholder="Slug / judul artikel" value="<?= htmlspecialchars($filters['post'], ENT_QUOTES) ?>">
                        <datalist id="post-options">
                            <?php foreach ($postOptions as $post): ?>
                                <option value="<?= htmlspecialchars($post['slug'], ENT_QUOTES) ?>"><?= htmlspecialchars($post['title'], ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Terapkan</button>
                        <a class="btn btn-outline" href="<?= admin_url('analytics') ?>">Reset</a>
                    </div>
                </form>
            </section>

            <section class="grid metrics">
                <article class="card metric-card metric-primary">
                    <span class="metric-label">Total Klik CTA</span>
                    <span class="metric-value"><?= $formatNumber((int) ($productAnalytics['summary']['total'] ?? 0)) ?></span>
                    <span class="metric-meta">Periode <?= htmlspecialchars($rangeLabel, ENT_QUOTES) ?></span>
                </article>
                <article class="card metric-card metric-success">
                    <span class="metric-label">Unique Visitor CTA</span>
                    <span class="metric-value"><?= $formatNumber((int) ($productAnalytics['summary']['unique_ips'] ?? 0)) ?></span>
                    <span class="metric-meta">Distinct IP</span>
                </article>
                <article class="card metric-card metric-highlight">
                    <span class="metric-label">Kanal CTA Produk Teratas</span>
                    <span class="metric-value"><?= htmlspecialchars($topProductChannel ? ucfirst($topProductChannel) : 'Belum ada', ENT_QUOTES) ?></span>
                    <span class="metric-meta"><?= $topProductChannel ? $formatNumber($topProductChannelTotal) . ' klik' : '—' ?></span>
                </article>
                <article class="card metric-card metric-info">
                    <span class="metric-label">Klik Marketplace</span>
                    <span class="metric-value"><?= $formatNumber((int) ($marketAnalytics['summary']['total'] ?? 0)) ?></span>
                    <span class="metric-meta"><?= $topMarketplaceChannel ? 'Top: ' . htmlspecialchars(ucfirst($topMarketplaceChannel), ENT_QUOTES) . ' (' . $formatNumber($topMarketplaceChannelTotal) . ')' : 'Shopee, Tokopedia, WhatsApp' ?></span>
                </article>
                <article class="card metric-card metric-service">
                    <span class="metric-label">Artikel Populer</span>
                    <span class="metric-value" style="font-size: 14px;">
                        <?= htmlspecialchars($topPost ?? 'Belum ada data', ENT_QUOTES) ?>
                    </span>
                    <span class="metric-meta"><?= $topPost ? $formatNumber($topPostTotal) . ' view' : '—' ?></span>
                </article>
            </section>

            <section class="analytics-block">
                <header class="analytics-block__header">
                    <h2>CTA Produk</h2>
                    <p>Insight klik tombol CTA terhadap halaman produk yang dipublikasikan.</p>
                </header>
                <div class="analytics-chart-grid">
                    <article class="card chart-card">
                        <header>
                            <h3>Tren Klik Harian</h3>
                        </header>
                        <div class="chart-card__canvas">
                            <canvas
                                id="productClicksChart"
                                data-series='<?= json_encode($productAnalytics['daily'] ?? [], $jsonOptions) ?>'
                            ></canvas>
                        </div>
                    </article>
                    <article class="card chart-card">
                        <header>
                            <h3>Distribusi Kanal CTA</h3>
                        </header>
                        <div class="chart-card__canvas">
                            <canvas
                                id="productChannelsChart"
                                data-channels='<?= json_encode($productAnalytics['channels'] ?? [], $jsonOptions) ?>'
                            ></canvas>
                        </div>
                    </article>
                </div>

                <section class="layout-split">
                    <div class="card table-card">
                        <header>
                            <h2>Kanal CTA Teraktif</h2>
                            <p class="card-subtitle">Channel dengan klik terbanyak.</p>
                        </header>
                        <div class="table-responsive table-responsive--flush">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kanal</th>
                                        <th>Klik</th>
                                        <th>Share</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($productAnalytics['channels'] === []): ?>
                                        <tr><td colspan="3">Belum ada data.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($productAnalytics['channels'] as $channel): ?>
                                            <?php
                                            $channelName = $channel['channel'] ?? 'unknown';
                                            $total = (int) ($channel['total'] ?? 0);
                                            $share = ($productAnalytics['summary']['total'] ?? 0) > 0
                                                ? round($total / $productAnalytics['summary']['total'] * 100, 1)
                                                : 0;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars(ucfirst($channelName), ENT_QUOTES) ?></td>
                                                <td><?= $formatNumber($total) ?></td>
                                                <td><?= $share ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card table-card">
                        <header>
                            <h2>Produk Terbanyak Klik</h2>
                            <p class="card-subtitle">Top 10 produk berdasarkan klik CTA.</p>
                        </header>
                        <div class="table-responsive table-responsive--flush">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Slug</th>
                                        <th>Klik</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($productAnalytics['products'] === []): ?>
                                        <tr><td colspan="3">Belum ada data.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($productAnalytics['products'] as $product): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['product_name'] ?? 'Tanpa nama', ENT_QUOTES) ?></td>
                                                <td><?= htmlspecialchars($product['product_slug'] ?? '—', ENT_QUOTES) ?></td>
                                                <td><?= $formatNumber((int) ($product['total'] ?? 0)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="layout-split">
                    <div class="card table-card">
                        <header>
                            <h2>Lokasi Klik CTA</h2>
                            <p class="card-subtitle">Top wilayah asal pengunjung.</p>
                        </header>
                        <div class="table-responsive table-responsive--flush">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Lokasi</th>
                                        <th>Klik</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($productAnalytics['locations'] === []): ?>
                                        <tr><td colspan="2">Belum ada data lokasi.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($productAnalytics['locations'] as $location): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(adminAnalyticsLocationLabel($location['country'] ?? null, $location['region'] ?? null, $location['city'] ?? null), ENT_QUOTES) ?></td>
                                                <td><?= $formatNumber((int) ($location['total'] ?? 0)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card table-card">
                        <header>
                            <h2>Perangkat Terpopuler</h2>
                            <p class="card-subtitle">Device yang sering digunakan klik CTA.</p>
                        </header>
                        <div class="table-responsive table-responsive--flush">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Perangkat</th>
                                        <th>Klik</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($productAnalytics['devices'] === []): ?>
                                        <tr><td colspan="2">Belum ada data perangkat.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($productAnalytics['devices'] as $device): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(ucfirst($device['device'] ?? 'unknown'), ENT_QUOTES) ?></td>
                                                <td><?= $formatNumber((int) ($device['total'] ?? 0)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="card table-card">
                    <header>
                        <h2>Timeline Klik Harian</h2>
                        <p class="card-subtitle">Distribusi klik / hari (maks. 30 hari terakhir).</p>
                    </header>
                    <div class="table-responsive table-responsive--flush">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Total Klik</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($productAnalytics['daily'] === []): ?>
                                    <tr><td colspan="2">Belum ada data.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($productAnalytics['daily'] as $day): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($day['period'] ?? '', ENT_QUOTES) ?></td>
                                            <td><?= $formatNumber((int) ($day['total'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card table-card">
                    <header>
                        <h2>Log Klik Terbaru</h2>
                        <p class="card-subtitle">100 aktivitas terbaru lengkap dengan IP &amp; referer.</p>
                    </header>
                    <div class="table-responsive table-responsive--flush">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Produk</th>
                                    <th>Kanal</th>
                                    <th>Lokasi</th>
                                    <th>IP</th>
                                    <th>Device</th>
                                    <th>Referrer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($productAnalytics['recent'] === []): ?>
                                    <tr><td colspan="7">Belum ada data.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($productAnalytics['recent'] as $event): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($formatDateTime($event['created_at'] ?? null), ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($event['product_name'] ?? '—', ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($event['cta_channel'] ?? '—'), ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars(adminAnalyticsLocationLabel($event['country'] ?? null, $event['region'] ?? null, $event['city'] ?? null), ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($event['ip_address'] ?? '—', ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars(($event['device_type'] ?? '—') . ' / ' . ($event['browser'] ?? '—'), ENT_QUOTES) ?></td>
                                            <td class="truncate"><?= htmlspecialchars($event['referrer_url'] ?? '—', ENT_QUOTES) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>

            <section class="analytics-block">
                <header class="analytics-block__header">
                    <h2>Klik Marketplace</h2>
                    <p>Monitoring seluruh link toko eksternal (Shopee, Tokopedia, WhatsApp, dll).</p>
                </header>
                <div class="analytics-chart-grid">
                    <article class="card chart-card">
                        <header>
                            <h3>Tren Klik Marketplace</h3>
                        </header>
                        <div class="chart-card__canvas">
                            <canvas
                                id="marketplaceClicksChart"
                                data-series='<?= json_encode($marketAnalytics['daily'] ?? [], $jsonOptions) ?>'
                            ></canvas>
                        </div>
                    </article>
                    <article class="card chart-card">
                        <header>
                            <h3>Distribusi Kanal Marketplace</h3>
                        </header>
                        <div class="chart-card__canvas">
                            <canvas
                                id="marketplaceChannelsChart"
                                data-channels='<?= json_encode($marketAnalytics['channels'] ?? [], $jsonOptions) ?>'
                            ></canvas>
                        </div>
                    </article>
                </div>

                <section class="layout-split">
                    <div class="card table-card">
                        <header>
                            <h2>Kanal Marketplace Teraktif</h2>
                            <p class="card-subtitle">Shopee, Tokopedia, WhatsApp, dan kanal toko lainnya.</p>
                        </header>
                        <div class="table-responsive table-responsive--flush">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kanal</th>
                                        <th>Klik</th>
                                        <th>Share</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($marketAnalytics['channels'] === []): ?>
                                        <tr><td colspan="3">Belum ada data.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($marketAnalytics['channels'] as $channel): ?>
                                            <?php
                                            $channelName = $channel['channel'] ?? 'unknown';
                                            $total = (int) ($channel['total'] ?? 0);
                                            $share = ($marketAnalytics['summary']['total'] ?? 0) > 0
                                                ? round($total / $marketAnalytics['summary']['total'] * 100, 1)
                                                : 0;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars(ucfirst($channelName), ENT_QUOTES) ?></td>
                                                <td><?= $formatNumber($total) ?></td>
                                                <td><?= $share ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card table-card">
                        <header>
                            <h2>Halaman Sumber Marketplace</h2>
                            <p class="card-subtitle">Landing page yang menghasilkan klik marketplace.</p>
                        </header>
                        <div class="table-responsive table-responsive--flush">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Halaman</th>
                                        <th>Klik</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($marketAnalytics['pages'] === []): ?>
                                        <tr><td colspan="2">Belum ada data.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($marketAnalytics['pages'] as $page): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(ucfirst($page['source_page'] ?? 'unknown'), ENT_QUOTES) ?></td>
                                                <td><?= $formatNumber((int) ($page['total'] ?? 0)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="layout-split">
                    <div class="card table-card">
                        <header>
                            <h2>Posisi CTA Marketplace</h2>
                            <p class="card-subtitle">Context / section tempat link marketplace berada.</p>
                        </header>
                        <div class="table-responsive table-responsive--flush">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Posisi</th>
                                        <th>Klik</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($marketAnalytics['contexts'] === []): ?>
                                        <tr><td colspan="2">Belum ada data.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($marketAnalytics['contexts'] as $context): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($context['source_context'] ?? 'unknown', ENT_QUOTES) ?></td>
                                                <td><?= $formatNumber((int) ($context['total'] ?? 0)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card table-card">
                        <header>
                            <h2>Lokasi Klik Marketplace</h2>
                            <p class="card-subtitle">Wilayah asal visitor marketplace.</p>
                        </header>
                        <div class="table-responsive table-responsive--flush">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Lokasi</th>
                                        <th>Klik</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($marketAnalytics['locations'] === []): ?>
                                        <tr><td colspan="2">Belum ada data.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($marketAnalytics['locations'] as $location): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(adminAnalyticsLocationLabel($location['country'] ?? null, $location['region'] ?? null, $location['city'] ?? null), ENT_QUOTES) ?></td>
                                                <td><?= $formatNumber((int) ($location['total'] ?? 0)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="card table-card">
                    <header>
                        <h2>Timeline Marketplace Harian</h2>
                        <p class="card-subtitle">Distribusi klik marketplace / hari (maks. 30 hari terakhir).</p>
                    </header>
                    <div class="table-responsive table-responsive--flush">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Total Klik</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($marketAnalytics['daily'] === []): ?>
                                    <tr><td colspan="2">Belum ada data.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($marketAnalytics['daily'] as $day): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($day['period'] ?? '', ENT_QUOTES) ?></td>
                                            <td><?= $formatNumber((int) ($day['total'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card table-card">
                    <header>
                        <h2>Log Marketplace Terbaru</h2>
                        <p class="card-subtitle">100 klik toko terbaru lengkap dengan IP &amp; referrer.</p>
                    </header>
                    <div class="table-responsive table-responsive--flush">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Kanal</th>
                                    <th>Halaman</th>
                                    <th>Posisi</th>
                                    <th>Lokasi</th>
                                    <th>IP</th>
                                    <th>Device</th>
                                    <th>Referrer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($marketAnalytics['recent'] === []): ?>
                                    <tr><td colspan="8">Belum ada data.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($marketAnalytics['recent'] as $event): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($formatDateTime($event['created_at'] ?? null), ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($event['channel'] ?? '—'), ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($event['source_page'] ?? '—', ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($event['source_context'] ?? '—', ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars(adminAnalyticsLocationLabel($event['country'] ?? null, $event['region'] ?? null, $event['city'] ?? null), ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($event['ip_address'] ?? '—', ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars(($event['device_type'] ?? '—') . ' / ' . ($event['browser'] ?? '—'), ENT_QUOTES) ?></td>
                                            <td class="truncate"><?= htmlspecialchars($event['referrer_url'] ?? '—', ENT_QUOTES) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>

            <section class="analytics-block">
                <header class="analytics-block__header">
                    <h2>Engagement Artikel</h2>
                    <p>Pelajari performa konten blog berdasarkan view dan distribusi pembaca.</p>
                </header>
                <div class="analytics-chart-grid">
                    <article class="card chart-card">
                        <header>
                            <h3>Tren View Harian</h3>
                        </header>
                        <div class="chart-card__canvas">
                            <canvas
                                id="blogViewsChart"
                                data-series='<?= json_encode($blogAnalytics['daily'] ?? [], $jsonOptions) ?>'
                            ></canvas>
                        </div>
                    </article>
                    <article class="card chart-card">
                        <header>
                            <h3>Top Artikel Terpopuler</h3>
                        </header>
                        <div class="chart-card__canvas">
                            <canvas
                                id="blogTopPostsChart"
                                data-posts='<?= json_encode($blogAnalytics['posts'] ?? [], $jsonOptions) ?>'
                            ></canvas>
                        </div>
                    </article>
                </div>

                <section class="card table-card">
                    <header>
                        <h2>Daftar Artikel Terpopuler</h2>
                        <p class="card-subtitle">Top 10 artikel berdasarkan view.</p>
                    </header>
                    <div class="table-responsive table-responsive--flush">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Slug</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($blogAnalytics['posts'] === []): ?>
                                    <tr><td colspan="3">Belum ada data.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($blogAnalytics['posts'] as $post): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($post['content_title'] ?? '—', ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($post['content_slug'] ?? '—', ENT_QUOTES) ?></td>
                                            <td><?= $formatNumber((int) ($post['total'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="layout-split">
                    <div class="card table-card">
                        <header>
                            <h2>Lokasi Pembaca Artikel</h2>
                            <p class="card-subtitle">Wilayah dominan pembaca blog.</p>
                        </header>
                        <div class="table-responsive table-responsive--flush">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Lokasi</th>
                                        <th>View</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($blogAnalytics['locations'] === []): ?>
                                        <tr><td colspan="2">Belum ada data.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($blogAnalytics['locations'] as $location): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(adminAnalyticsLocationLabel($location['country'] ?? null, $location['region'] ?? null, $location['city'] ?? null), ENT_QUOTES) ?></td>
                                                <td><?= $formatNumber((int) ($location['total'] ?? 0)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card table-card">
                        <header>
                            <h2>Timeline View Artikel</h2>
                            <p class="card-subtitle">Distribusi view per hari.</p>
                        </header>
                        <div class="table-responsive table-responsive--flush">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>View</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($blogAnalytics['daily'] === []): ?>
                                        <tr><td colspan="2">Belum ada data.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($blogAnalytics['daily'] as $day): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($day['period'] ?? '', ENT_QUOTES) ?></td>
                                                <td><?= $formatNumber((int) ($day['total'] ?? 0)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="card table-card">
                    <header>
                        <h2>Log View Artikel Terbaru</h2>
                        <p class="card-subtitle">100 view terakhir beserta lokasi &amp; device.</p>
                    </header>
                    <div class="table-responsive table-responsive--flush">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Artikel</th>
                                    <th>Lokasi</th>
                                    <th>IP</th>
                                    <th>Device</th>
                                    <th>Referrer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($blogAnalytics['recent'] === []): ?>
                                    <tr><td colspan="6">Belum ada data.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($blogAnalytics['recent'] as $event): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($formatDateTime($event['created_at'] ?? null), ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($event['content_title'] ?? '—', ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars(adminAnalyticsLocationLabel($event['country'] ?? null, $event['region'] ?? null, $event['city'] ?? null), ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars($event['ip_address'] ?? '—', ENT_QUOTES) ?></td>
                                            <td><?= htmlspecialchars(($event['device_type'] ?? '—') . ' / ' . ($event['browser'] ?? '—'), ENT_QUOTES) ?></td>
                                            <td class="truncate"><?= htmlspecialchars($event['referrer_url'] ?? '—', ENT_QUOTES) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </div>
        </main>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var rangeSelect = document.getElementById('filter-range');
                var customFields = document.querySelectorAll('.custom-range-field');

                if (!rangeSelect || !customFields.length) {
                    return;
                }

                var toggleCustom = function () {
                    var isCustom = rangeSelect.value === 'custom';
                    customFields.forEach(function (field) {
                        field.hidden = !isCustom;
                    });
                };

                rangeSelect.addEventListener('change', toggleCustom);
                toggleCustom();
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof Chart === 'undefined') {
                    return;
                }

                const chartPalette = ['#60a5fa', '#34d399', '#fbbf24', '#f472b6', '#c084fc', '#38bdf8', '#f97316', '#22d3ee'];

                const hexToRgba = function (hex, alpha) {
                    let sanitized = hex.replace('#', '');
                    if (sanitized.length === 3) {
                        sanitized = sanitized.split('').map(function (char) {
                            return char + char;
                        }).join('');
                    }
                    const bigint = parseInt(sanitized, 16);
                    const r = (bigint >> 16) & 255;
                    const g = (bigint >> 8) & 255;
                    const b = bigint & 255;
                    return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
                };

                const createEmptyState = function (canvas, message) {
                    if (!canvas) {
                        return;
                    }

                    const container = canvas.closest('.chart-card__canvas');
                    const placeholder = document.createElement('div');
                    placeholder.className = 'chart-empty';
                    placeholder.textContent = message;

                    if (container) {
                        container.innerHTML = '';
                        container.appendChild(placeholder);
                    } else {
                        canvas.replaceWith(placeholder);
                    }
                };

                const sortSeriesByPeriod = function (series) {
                    return [].concat(series || []).sort(function (a, b) {
                        const left = String(a && a.period ? a.period : '');
                        const right = String(b && b.period ? b.period : '');
                        return left.localeCompare(right);
                    });
                };

                const readDataset = function (canvas, key) {
                    if (!canvas || !canvas.dataset || typeof canvas.dataset[key] === 'undefined') {
                        return '[]';
                    }

                    const value = canvas.dataset[key];
                    return value && value.length ? value : '[]';
                };

                const renderLineChart = function (canvas, series, label, colorHex) {
                    if (!canvas) {
                        return;
                    }

                    const sortedSeries = sortSeriesByPeriod(series);
                    if (!sortedSeries.length) {
                        createEmptyState(canvas, 'Belum ada data harian untuk periode ini.');
                        return;
                    }

                    const labels = sortedSeries.map(function (item) {
                        return item && item.period ? item.period : '';
                    });
                    const data = sortedSeries.map(function (item) {
                        return Number(item && item.total ? item.total : 0);
                    });

                    const context = canvas.getContext('2d');
                    if (!context) {
                        return;
                    }

                    new Chart(context, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label,
                                    data,
                                    borderColor: colorHex,
                                    backgroundColor: hexToRgba(colorHex, 0.18),
                                    tension: 0.36,
                                    fill: true,
                                    pointRadius: 3,
                                    pointHoverRadius: 6,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                        color: '#cbd5f5',
                                    },
                                    grid: {
                                        color: 'rgba(148, 163, 184, 0.14)',
                                    },
                                },
                                x: {
                                    ticks: {
                                        color: '#cbd5f5',
                                    },
                                    grid: {
                                        color: 'rgba(148, 163, 184, 0.06)',
                                    },
                                },
                            },
                            plugins: {
                                legend: {
                                    labels: {
                                        color: '#e2e8f0',
                                    },
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.94)',
                                    borderColor: hexToRgba(colorHex, 0.46),
                                    borderWidth: 1,
                                    callbacks: {
                                        label: function (context) {
                                            const value = context.parsed.y || 0;
                                            return context.dataset.label + ': ' + value.toLocaleString('id-ID');
                                        },
                                    },
                                },
                            },
                        },
                    });
                };

                const renderDoughnutChart = function (canvas, entries) {
                    if (!canvas) {
                        return;
                    }

                    const filtered = (entries || []).filter(function (item) {
                        return Number(item && item.total ? item.total : 0) > 0;
                    });

                    if (!filtered.length) {
                        createEmptyState(canvas, 'Belum ada data kanal untuk ditampilkan.');
                        return;
                    }

                    const labels = filtered.map(function (item) {
                        return item && item.channel ? item.channel.toString().toUpperCase() : 'UNKNOWN';
                    });
                    const data = filtered.map(function (item) {
                        return Number(item && item.total ? item.total : 0);
                    });

                    const colors = chartPalette.slice(0, data.length).map(function (hex, index) {
                        if (data.length > chartPalette.length) {
                            const fallbackHue = (index * 47) % 360;
                            return 'hsl(' + fallbackHue + ', 78%, 60%)';
                        }
                        return hex;
                    });

                    const context = canvas.getContext('2d');
                    if (!context) {
                        return;
                    }

                    new Chart(context, {
                        type: 'doughnut',
                        data: {
                            labels,
                            datasets: [
                                {
                                    data,
                                    backgroundColor: colors,
                                    borderWidth: 0,
                                    hoverOffset: 6,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: '#e2e8f0',
                                        usePointStyle: true,
                                    },
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.94)',
                                    borderColor: 'rgba(148, 163, 184, 0.4)',
                                    borderWidth: 1,
                                    callbacks: {
                                        label: function (context) {
                                            const value = context.parsed || 0;
                                            return context.label + ': ' + value.toLocaleString('id-ID');
                                        },
                                    },
                                },
                            },
                        },
                    });
                };

                const renderBarChart = function (canvas, entries, datasetLabel, colorHex) {
                    if (!canvas) {
                        return;
                    }

                    const filtered = (entries || [])
                        .filter(function (item) {
                            return Number(item && item.total ? item.total : 0) > 0;
                        })
                        .sort(function (a, b) {
                            const left = Number(a && a.total ? a.total : 0);
                            const right = Number(b && b.total ? b.total : 0);
                            return right - left;
                        })
                        .slice(0, 8);

                    if (!filtered.length) {
                        createEmptyState(canvas, 'Belum ada data artikel yang bisa divisualisasikan.');
                        return;
                    }

                    const labels = filtered.map(function (item) {
                        return item && item.content_title ? item.content_title.toString() : 'Tanpa Judul';
                    });
                    const data = filtered.map(function (item) {
                        return Number(item && item.total ? item.total : 0);
                    });

                    const context = canvas.getContext('2d');
                    if (!context) {
                        return;
                    }

                    new Chart(context, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label: datasetLabel,
                                    data,
                                    backgroundColor: hexToRgba(colorHex, 0.85),
                                    borderColor: colorHex,
                                    borderWidth: 1,
                                    borderRadius: 6,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',
                            animation: false,
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                        color: '#cbd5f5',
                                    },
                                    grid: {
                                        color: 'rgba(148, 163, 184, 0.14)',
                                    },
                                },
                                y: {
                                    ticks: {
                                        color: '#cbd5f5',
                                        callback: function (value, index) {
                                            const label = labels[index] || '';
                                            return label.length > 24 ? label.slice(0, 24) + '…' : label;
                                        },
                                    },
                                    grid: {
                                        display: false,
                                    },
                                },
                            },
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.94)',
                                    borderColor: hexToRgba(colorHex, 0.45),
                                    borderWidth: 1,
                                    callbacks: {
                                        title: function (tooltipItems) {
                                            return tooltipItems[0] && tooltipItems[0].label ? tooltipItems[0].label : datasetLabel;
                                        },
                                        label: function (context) {
                                            const value = context.parsed.x || 0;
                                            return datasetLabel + ': ' + value.toLocaleString('id-ID');
                                        },
                                    },
                                },
                            },
                        },
                    });
                };

                const productDailyCanvas = document.getElementById('productClicksChart');
                renderLineChart(
                    productDailyCanvas,
                    JSON.parse(readDataset(productDailyCanvas, 'series')),
                    'CTA Produk',
                    '#3b82f6'
                );

                const productChannelsCanvas = document.getElementById('productChannelsChart');
                renderDoughnutChart(
                    productChannelsCanvas,
                    JSON.parse(readDataset(productChannelsCanvas, 'channels'))
                );

                const marketplaceDailyCanvas = document.getElementById('marketplaceClicksChart');
                renderLineChart(
                    marketplaceDailyCanvas,
                    JSON.parse(readDataset(marketplaceDailyCanvas, 'series')),
                    'Klik Marketplace',
                    '#10b981'
                );

                const marketplaceChannelsCanvas = document.getElementById('marketplaceChannelsChart');
                renderDoughnutChart(
                    marketplaceChannelsCanvas,
                    JSON.parse(readDataset(marketplaceChannelsCanvas, 'channels'))
                );

                const blogDailyCanvas = document.getElementById('blogViewsChart');
                renderLineChart(
                    blogDailyCanvas,
                    JSON.parse(readDataset(blogDailyCanvas, 'series')),
                    'View Artikel',
                    '#f97316'
                );

                const blogPostsCanvas = document.getElementById('blogTopPostsChart');
                renderBarChart(
                    blogPostsCanvas,
                    JSON.parse(readDataset(blogPostsCanvas, 'posts')),
                    'View',
                    '#f472b6'
                );
            });
        </script>
        <style>
/* Analytics page styling - consistent with admin panel */
.analytics-block {
    margin-top: 32px;
    padding-top: 32px;
    border-top: 1px solid rgba(148, 163, 184, 0.16);
}

.analytics-block:first-of-type {
    margin-top: 0;
    padding-top: 0;
    border-top: none;
}

.analytics-block__header {
    margin-bottom: 28px;
}

.analytics-block__header h2 {
    margin: 0 0 8px 0;
    font-size: 1.5rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--text-primary);
}

.analytics-block__header p {
    margin: 0;
    color: var(--text-muted);
    font-size: 0.9rem;
    line-height: 1.6;
}

.analytics-chart-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

@media (max-width: 768px) {
    .analytics-chart-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

.chart-card {
    padding: 24px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: var(--radius-lg);
    background: linear-gradient(145deg, rgba(20, 32, 56, 0.96) 0%, rgba(9, 15, 30, 0.94) 100%);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.chart-card::before {
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

.chart-card:hover {
    border-color: rgba(126, 252, 43, 0.35);
    box-shadow: 0 20px 40px -20px rgba(126, 252, 43, 0.3);
    transform: translateY(-2px);
}

.chart-card:hover::before {
    opacity: 1;
}

.chart-card header {
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.12);
}

.chart-card header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--text-primary);
}

.chart-card__canvas {
    position: relative;
    min-height: 300px;
    height: 300px;
}

.chart-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    height: 300px;
    color: var(--text-muted);
    font-size: 0.9rem;
    text-align: center;
    padding: 40px 20px;
}

.layout-split {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

@media (max-width: 768px) {
    .layout-split {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

.table-card {
    padding: 24px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: var(--radius-lg);
    background: linear-gradient(145deg, rgba(20, 32, 56, 0.96) 0%, rgba(9, 15, 30, 0.94) 100%);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.table-card::before {
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

.table-card:hover {
    border-color: rgba(126, 252, 43, 0.35);
    box-shadow: 0 20px 40px -20px rgba(126, 252, 43, 0.3);
    transform: translateY(-2px);
}

.table-card:hover::before {
    opacity: 1;
}

.table-card header {
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.12);
}

.table-card header h2 {
    margin: 0 0 8px 0;
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--text-primary);
}

.table-card .card-subtitle {
    margin: 0;
    color: var(--text-muted);
    font-size: 0.85rem;
    line-height: 1.6;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table-responsive--flush {
    margin: 0 -24px -24px -24px;
    padding: 0 24px 24px 24px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.table thead {
    background: rgba(148, 163, 184, 0.08);
}

.table thead th {
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 0.8rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--text-muted);
    border-bottom: 1px solid rgba(148, 163, 184, 0.16);
}

.table tbody tr {
    border-bottom: 1px solid rgba(148, 163, 184, 0.08);
    transition: background 0.2s ease;
}

.table tbody tr:hover {
    background: rgba(148, 163, 184, 0.06);
}

.table tbody tr:last-child {
    border-bottom: none;
}

.table tbody td {
    padding: 14px 16px;
    color: var(--text-primary);
}

.table tbody td.truncate {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Workspace meta styling */
.workspace-meta {
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    border-radius: 999px;
    background: linear-gradient(135deg, rgba(56, 245, 199, 0.22), rgba(56, 245, 199, 0.16));
    color: rgba(56, 245, 199, 0.98);
    border: 1px solid rgba(56, 245, 199, 0.45);
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    box-shadow: 0 4px 16px -6px rgba(56, 245, 199, 0.5);
}

/* Form styling improvements */
.analytics-filter {
    margin-top: 20px;
}

.analytics-filter .form-field {
    margin-bottom: 0;
}

/* Metric card improvements */
.metric-card {
    padding: 24px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: var(--radius-lg);
    background: linear-gradient(145deg, rgba(20, 32, 56, 0.96) 0%, rgba(9, 15, 30, 0.94) 100%);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.metric-card::before {
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

.metric-card:hover {
    border-color: rgba(126, 252, 43, 0.35);
    box-shadow: 0 20px 40px -20px rgba(126, 252, 43, 0.3);
    transform: translateY(-2px);
}

.metric-card:hover::before {
    opacity: 1;
}

.metric-card.metric-primary::before {
    background: linear-gradient(90deg, rgba(96, 165, 250, 0.6), rgba(59, 130, 246, 0.6));
}

.metric-card.metric-success::before {
    background: linear-gradient(90deg, rgba(52, 211, 153, 0.6), rgba(34, 197, 94, 0.6));
}

.metric-card.metric-highlight::before {
    background: linear-gradient(90deg, rgba(251, 191, 36, 0.6), rgba(245, 158, 11, 0.6));
}

.metric-card.metric-info::before {
    background: linear-gradient(90deg, rgba(96, 165, 250, 0.6), rgba(59, 130, 246, 0.6));
}

.metric-card.metric-service::before {
    background: linear-gradient(90deg, rgba(244, 114, 182, 0.6), rgba(236, 72, 153, 0.6));
}

.metric-value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.2;
    background: linear-gradient(135deg, var(--text-primary) 0%, var(--text-muted) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.metric-meta {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-top: 4px;
}

/* Card header improvements */
.card header h2 {
    margin: 0 0 8px 0;
    font-size: 1.5rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--text-primary);
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .analytics-chart-grid,
    .layout-split {
        grid-template-columns: 1fr;
    }
    
    .chart-card__canvas {
        min-height: 250px;
        height: 250px;
    }
}

@media (max-width: 768px) {
    .analytics-block__header h2 {
        font-size: 1.25rem;
    }
    
    .chart-card,
    .table-card,
    .metric-card {
        padding: 20px;
    }
    
    .metric-value {
        font-size: 1.5rem;
    }
    
    .table-responsive--flush {
        margin: 0 -20px -20px -20px;
        padding: 0 20px 20px 20px;
    }
    
    .table thead th,
    .table tbody td {
        padding: 12px;
        font-size: 0.85rem;
    }
}
        </style>
    </body>
</html>

