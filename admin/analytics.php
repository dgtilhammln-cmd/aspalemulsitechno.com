<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * MUNG CMS V1
 * File: admin/analytics.php
 * Description: Simplified real-time analytics dashboard with 1 wave chart and 3 core indicators.
 */

adminRequireAuth();

$filters = [
    'range' => $_GET['range'] ?? '7d',
    'start' => $_GET['start'] ?? '',
    'end' => $_GET['end'] ?? '',
];

$overview = adminAnalyticsFetchWaveOverview($filters);
$rangeMode = $overview['range']['mode'] ?? '7d';
$rangeLabel = $overview['range']['label'] ?? '7 Hari Terakhir';

$formatNumber = static fn (int $value): string => number_format($value, 0, ',', '.');
$jsonOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;

?><!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Analytics Ringkasan | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css?v=<?= file_exists(__DIR__ . '/admin.css') ? filemtime(__DIR__ . '/admin.css') : time() ?>">
        <script src="app.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <style>
            .wave-analytics-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: 20px;
                margin-bottom: 24px;
            }
            .wave-metric-card {
                background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.8));
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 16px;
                padding: 22px 24px;
                display: flex;
                align-items: center;
                gap: 18px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                transition: transform 0.2s ease, border-color 0.2s ease;
            }
            .wave-metric-card:hover {
                transform: translateY(-2px);
                border-color: rgba(255, 255, 255, 0.18);
            }
            .wave-metric-icon {
                width: 52px;
                height: 52px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .wave-metric-icon--blue {
                background: rgba(59, 130, 246, 0.15);
                color: #60a5fa;
                border: 1px solid rgba(59, 130, 246, 0.3);
            }
            .wave-metric-icon--green {
                background: rgba(16, 185, 129, 0.15);
                color: #34d399;
                border: 1px solid rgba(16, 185, 129, 0.3);
            }
            .wave-metric-icon--gold {
                background: rgba(245, 158, 11, 0.15);
                color: #fbbf24;
                border: 1px solid rgba(245, 158, 11, 0.3);
            }
            .wave-metric-info {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            .wave-metric-label {
                font-size: 0.85rem;
                font-weight: 500;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            .wave-metric-value {
                font-size: 1.85rem;
                font-weight: 700;
                color: #ffffff;
                line-height: 1.1;
            }
            .wave-metric-meta {
                font-size: 0.78rem;
                color: #64748b;
            }
            .wave-chart-card {
                background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.8));
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 18px;
                padding: 24px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            }
            .wave-chart-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 12px;
                margin-bottom: 20px;
                padding-bottom: 14px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            }
            .wave-chart-title h2 {
                margin: 0 0 4px;
                font-size: 1.15rem;
                color: #f8fafc;
            }
            .wave-chart-title p {
                margin: 0;
                font-size: 0.85rem;
                color: #94a3b8;
            }
            .wave-chart-legend {
                display: flex;
                gap: 18px;
                font-size: 0.85rem;
            }
            .wave-legend-item {
                display: flex;
                align-items: center;
                gap: 8px;
                color: #cbd5e1;
            }
            .wave-legend-dot {
                width: 12px;
                height: 12px;
                border-radius: 50%;
            }
            .wave-chart-container {
                position: relative;
                width: 100%;
                height: 380px;
            }
        </style>
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
                    'pageTitle' => 'Analytics Ringkasan',
                    'pageDescription' => 'Monitoring performa visitor, klik WhatsApp, dan prospek leads masuk dalam satu grafik wave.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Analytics'],
                    ],
                ]);
            ?>
            <div class="workspace-content">
                <section class="workspace-meta">
                    <span class="status-pill">Periode: <?= htmlspecialchars($rangeLabel, ENT_QUOTES) ?></span>
                </section>

                <!-- Filter Laporan -->
                <section class="card" data-animate="fade-up" style="margin-bottom: 24px;">
                    <header>
                        <h2>Filter Laporan</h2>
                        <p class="card-subtitle">Pilih rentang waktu evaluasi laporan performa website.</p>
                    </header>
                    <form method="get" class="form-grid analytics-filter" autocomplete="off">
                        <div class="form-field">
                            <label for="filter-range">Rentang Waktu</label>
                            <select id="filter-range" name="range" onchange="toggleCustomRange(this.value)">
                                <?php
                                $ranges = [
                                    'today' => 'Hari Ini',
                                    '7d' => '7 Hari Terakhir',
                                    '30d' => '30 Hari Terakhir',
                                    '90d' => '90 Hari Terakhir',
                                    '12m' => '12 Bulan Terakhir',
                                    'custom' => 'Kustom Tanggal',
                                ];
                                foreach ($ranges as $value => $label):
                                    $selected = $rangeMode === $value ? 'selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($value, ENT_QUOTES) ?>" <?= $selected ?>><?= htmlspecialchars($label, ENT_QUOTES) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field custom-range-field" id="start-field" <?= $rangeMode === 'custom' ? '' : 'style="display:none;"' ?>>
                            <label for="filter-start">Tanggal Mulai</label>
                            <input type="date" id="filter-start" name="start" value="<?= htmlspecialchars($filters['start'], ENT_QUOTES) ?>">
                        </div>
                        <div class="form-field custom-range-field" id="end-field" <?= $rangeMode === 'custom' ? '' : 'style="display:none;"' ?>>
                            <label for="filter-end">Tanggal Selesai</label>
                            <input type="date" id="filter-end" name="end" value="<?= htmlspecialchars($filters['end'], ENT_QUOTES) ?>">
                        </div>
                        <div class="form-actions" style="align-self: flex-end;">
                            <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                            <a class="btn btn-outline" href="<?= admin_url('analytics') ?>">Reset</a>
                        </div>
                    </form>
                </section>

                <!-- 3 Indikator Utama -->
                <section class="wave-analytics-grid">
                    <!-- Indikator 1: Total Visitor -->
                    <article class="wave-metric-card">
                        <div class="wave-metric-icon wave-metric-icon--blue">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="wave-metric-info">
                            <span class="wave-metric-label">Total Visitor</span>
                            <span class="wave-metric-value"><?= $formatNumber($overview['totals']['visitors']) ?></span>
                            <span class="wave-metric-meta">Kunjungan ke situs</span>
                        </div>
                    </article>

                    <!-- Indikator 2: Total Klik WA -->
                    <article class="wave-metric-card">
                        <div class="wave-metric-icon wave-metric-icon--green">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.472-.148-.672.15-.198.297-.769.966-.941 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.447-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.672-1.612-.921-2.207-.242-.58-.487-.501-.672-.51-.173-.008-.371-.01-.57-.01-.199 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.199 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.226 1.36.194 1.872.118.571-.085 1.758-.718 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                <path d="M20.52 3.48A11.793 11.793 0 0 0 12 0C5.373 0 0 5.373 0 12c0 2.116.555 4.186 1.608 6.007L0 24l6.182-1.581A11.946 11.946 0 0 0 12 24c6.627 0 12-5.373 12-12 0-3.197-1.246-6.206-3.48-8.52zM12 22.125a10.07 10.07 0 0 1-5.143-1.409l-.368-.217-3.675.94.982-3.584-.24-.368A10.11 10.11 0 0 1 1.875 12c0-5.603 4.522-10.125 10.125-10.125S22.125 6.397 22.125 12 17.603 22.125 12 22.125z"/>
                            </svg>
                        </div>
                        <div class="wave-metric-info">
                            <span class="wave-metric-label">Total Klik WA</span>
                            <span class="wave-metric-value"><?= $formatNumber($overview['totals']['wa']) ?></span>
                            <span class="wave-metric-meta">Klik kontak WhatsApp</span>
                        </div>
                    </article>

                    <!-- Indikator 3: Total Leads -->
                    <article class="wave-metric-card">
                        <div class="wave-metric-icon wave-metric-icon--gold">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <polyline points="16 11 18 13 22 9"></polyline>
                            </svg>
                        </div>
                        <div class="wave-metric-info">
                            <span class="wave-metric-label">Total Leads</span>
                            <span class="wave-metric-value"><?= $formatNumber($overview['totals']['leads']) ?></span>
                            <span class="wave-metric-meta">Prospek form WhatsApp</span>
                        </div>
                    </article>
                </section>

                <!-- 1 Grafik Wave -->
                <section class="wave-chart-card">
                    <header class="wave-chart-header">
                        <div class="wave-chart-title">
                            <h2>Grafik Tren Interaksi & Leads</h2>
                            <p>Visualisasi kurva gelombang dinamis untuk Visitor, Klik WhatsApp, dan Leads Masuk.</p>
                        </div>
                        <div class="wave-chart-legend">
                            <div class="wave-legend-item">
                                <span class="wave-legend-dot" style="background:#3b82f6;"></span>
                                <span>Total Visitor</span>
                            </div>
                            <div class="wave-legend-item">
                                <span class="wave-legend-dot" style="background:#10b981;"></span>
                                <span>Klik WhatsApp</span>
                            </div>
                            <div class="wave-legend-item">
                                <span class="wave-legend-dot" style="background:#f59e0b;"></span>
                                <span>Total Leads</span>
                            </div>
                        </div>
                    </header>
                    <div class="wave-chart-container">
                        <canvas id="waveAnalyticsChart"></canvas>
                    </div>
                </section>
            </div>
        </main>

        <script>
            function toggleCustomRange(val) {
                const startField = document.getElementById('start-field');
                const endField = document.getElementById('end-field');
                if (val === 'custom') {
                    startField.style.display = 'block';
                    endField.style.display = 'block';
                } else {
                    startField.style.display = 'none';
                    endField.style.display = 'none';
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('waveAnalyticsChart');
                if (!ctx) return;

                const chartLabels = <?= json_encode($overview['chart']['labels'] ?? [], $jsonOptions) ?>;
                const visitorsData = <?= json_encode($overview['chart']['visitors'] ?? [], $jsonOptions) ?>;
                const waData = <?= json_encode($overview['chart']['wa'] ?? [], $jsonOptions) ?>;
                const leadsData = <?= json_encode($overview['chart']['leads'] ?? [], $jsonOptions) ?>;

                const canvas = ctx.getContext('2d');

                // Gradient fills untuk efek wave elegan
                const blueGradient = canvas.createLinearGradient(0, 0, 0, 350);
                blueGradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
                blueGradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

                const greenGradient = canvas.createLinearGradient(0, 0, 0, 350);
                greenGradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
                greenGradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

                const goldGradient = canvas.createLinearGradient(0, 0, 0, 350);
                goldGradient.addColorStop(0, 'rgba(245, 158, 11, 0.45)');
                goldGradient.addColorStop(1, 'rgba(245, 158, 11, 0.0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [
                            {
                                label: 'Total Visitor',
                                data: visitorsData,
                                borderColor: '#3b82f6',
                                backgroundColor: blueGradient,
                                fill: true,
                                tension: 0.42,
                                borderWidth: 3,
                                pointBackgroundColor: '#3b82f6',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 7,
                            },
                            {
                                label: 'Klik WhatsApp',
                                data: waData,
                                borderColor: '#10b981',
                                backgroundColor: greenGradient,
                                fill: true,
                                tension: 0.42,
                                borderWidth: 3,
                                pointBackgroundColor: '#10b981',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 7,
                            },
                            {
                                label: 'Total Leads',
                                data: leadsData,
                                borderColor: '#f59e0b',
                                backgroundColor: goldGradient,
                                fill: true,
                                tension: 0.42,
                                borderWidth: 3,
                                pointBackgroundColor: '#f59e0b',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 7,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleColor: '#f8fafc',
                                bodyColor: '#cbd5e1',
                                borderColor: 'rgba(255, 255, 255, 0.1)',
                                borderWidth: 1,
                                padding: 12,
                                boxPadding: 6,
                                usePointStyle: true,
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.05)',
                                    drawBorder: false,
                                },
                                ticks: {
                                    color: '#94a3b8',
                                    font: { size: 12 }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.05)',
                                    drawBorder: false,
                                },
                                ticks: {
                                    color: '#94a3b8',
                                    font: { size: 12 },
                                    precision: 0,
                                }
                            }
                        }
                    }
                });
            });
        </script>
    </body>
</html>
