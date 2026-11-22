<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireAuth();

$pdo = getPDO();

/**
 * MUNG CMS V1
 * File: admin/leads.php
 * Description: Leads Tracker — daftar prospek dari form floating WA beserta UTM, device, timestamp.
 */

// ── Pastikan tabel leads sudah ada (single-quote untuk default values) ─────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS leads (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            needs TEXT NULL,
            source VARCHAR(100) DEFAULT 'floating_wa',
            device VARCHAR(50) DEFAULT 'Desktop',
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(500) NULL,
            utm_source VARCHAR(120) NULL,
            utm_medium VARCHAR(120) NULL,
            utm_campaign VARCHAR(150) NULL,
            utm_term VARCHAR(150) NULL,
            utm_content VARCHAR(150) NULL,
            referrer VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_leads_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (Throwable $e) {
    // tabel mungkin sudah ada atau izin tidak ada — lanjutkan saja
}

// ── Tambahkan kolom baru jika tabel versi lama (kolom bisa belum ada) ──────────
$migrateColumns = [
    'device'       => "ALTER TABLE leads ADD COLUMN device VARCHAR(50) DEFAULT 'Desktop' AFTER source",
    'utm_source'   => "ALTER TABLE leads ADD COLUMN utm_source VARCHAR(120) NULL AFTER user_agent",
    'utm_medium'   => "ALTER TABLE leads ADD COLUMN utm_medium VARCHAR(120) NULL AFTER utm_source",
    'utm_campaign' => "ALTER TABLE leads ADD COLUMN utm_campaign VARCHAR(150) NULL AFTER utm_medium",
    'utm_term'     => "ALTER TABLE leads ADD COLUMN utm_term VARCHAR(150) NULL AFTER utm_campaign",
    'utm_content'  => "ALTER TABLE leads ADD COLUMN utm_content VARCHAR(150) NULL AFTER utm_term",
    'referrer'     => "ALTER TABLE leads ADD COLUMN referrer VARCHAR(500) NULL AFTER utm_content",
];
foreach ($migrateColumns as $col => $ddl) {
    try {
        $check = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND COLUMN_NAME = :col");
        $check->execute([':col' => $col]);
        if (!$check->fetch()) {
            $pdo->exec($ddl);
        }
    } catch (Throwable $e) {
        // skip
    }
}

$notices = [];

// ── Handle Hapus Lead ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_lead') {
    $deleteId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($deleteId) {
        try {
            $delStmt = $pdo->prepare('DELETE FROM leads WHERE id = :id');
            $delStmt->execute([':id' => $deleteId]);
            $notices[] = ['type' => 'success', 'message' => 'Data lead berhasil dihapus.'];
        } catch (Throwable $e) {
            $notices[] = ['type' => 'error', 'message' => 'Gagal menghapus: ' . $e->getMessage()];
        }
    }
}

// ── Filter & Pencarian ──────────────────────────────────────────────────────────
$search       = trim((string) ($_GET['q'] ?? ''));
$filterDevice = trim((string) ($_GET['device'] ?? ''));

$where  = [];
$params = [];

if ($search !== '') {
    $where[]       = '(name LIKE :s OR phone LIKE :s2 OR needs LIKE :s3 OR utm_source LIKE :s4 OR utm_campaign LIKE :s5)';
    $params[':s']  = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
    $params[':s4'] = "%{$search}%";
    $params[':s5'] = "%{$search}%";
}

if ($filterDevice !== '' && in_array($filterDevice, ['Mobile', 'Desktop'], true)) {
    $where[]       = 'device = :dev';
    $params[':dev'] = $filterDevice;
}

$whereClause = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

// ── Statistik Ringkas ───────────────────────────────────────────────────────────
$stats = ['total' => 0, 'today_count' => 0, 'mobile_count' => 0, 'desktop_count' => 0];
try {
    $statsStmt = $pdo->query("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN device = 'Mobile'  THEN 1 ELSE 0 END) AS mobile_count,
            SUM(CASE WHEN device = 'Desktop' THEN 1 ELSE 0 END) AS desktop_count,
            SUM(CASE WHEN DATE(created_at) = CURRENT_DATE THEN 1 ELSE 0 END) AS today_count
        FROM leads
    ");
    if ($statsStmt) {
        $row = $statsStmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats = $row;
        }
    }
} catch (Throwable $e) {
    // tabel belum ada, biarkan default
}

// ── Query Leads List ────────────────────────────────────────────────────────────
$leads = [];
try {
    $listSql  = "SELECT * FROM leads {$whereClause} ORDER BY created_at DESC LIMIT 100";
    $listStmt = $pdo->prepare($listSql);
    $listStmt->execute($params);
    $leads = $listStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $notices[] = ['type' => 'warning', 'message' => 'Belum ada data leads atau tabel belum siap.'];
}

// ── Helper: Waktu Relatif ───────────────────────────────────────────────────────
if (!function_exists('leadTimeAgo')) {
    function leadTimeAgo(string $datetime): string
    {
        $time = strtotime($datetime);
        if (!$time) {
            return '-';
        }
        $diff = time() - $time;
        if ($diff < 60)     { return 'Baru saja'; }
        if ($diff < 3600)   { return floor($diff / 60) . ' mnt lalu'; }
        if ($diff < 86400)  { return floor($diff / 3600) . ' jam lalu'; }
        if ($diff < 604800) { return floor($diff / 86400) . ' hari lalu'; }
        return date('d M Y', $time);
    }
}

// ── Helper: Format Link WA ──────────────────────────────────────────────────────
if (!function_exists('formatWaLink')) {
    function formatWaLink(string $phone, string $name = ''): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        }
        $msg = 'Halo ' . $name . ', terima kasih telah menghubungi PT Aspal Emulsi Indonesia.';
        return 'https://wa.me/' . $cleaned . '?text=' . rawurlencode($msg);
    }
}

$cssMtime = file_exists(__DIR__ . '/admin.css') ? filemtime(__DIR__ . '/admin.css') : time();
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Leads Tracker | Aspal Emulsi</title>
        <link rel="icon" type="image/png" href="/favicon.png">
        <link rel="stylesheet" href="admin.css?v=<?= $cssMtime ?>">
        <style>
            /* ═══════════════════════════════════════
               LEADS STATS CARDS
            ═══════════════════════════════════════ */
            .leads-stats-row {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 16px;
                margin-bottom: 20px;
            }
            .leads-stat-card {
                background: linear-gradient(135deg, rgba(30,41,59,0.7), rgba(15,23,42,0.85));
                border: 1px solid rgba(255,255,255,0.08);
                border-radius: 14px;
                padding: 16px 20px;
                display: flex;
                align-items: center;
                gap: 14px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            }
            .leads-stat-icon {
                width: 44px; height: 44px;
                border-radius: 12px;
                display: flex; align-items: center; justify-content: center;
                font-size: 1.3rem; flex-shrink: 0;
            }
            .stat-icon--total   { background: rgba(16,185,129,0.15); color: #10b981; }
            .stat-icon--today   { background: rgba(59,130,246,0.15);  color: #3b82f6; }
            .stat-icon--mobile  { background: rgba(245,158,11,0.15);  color: #f59e0b; }
            .stat-icon--desktop { background: rgba(168,85,247,0.15);  color: #a855f7; }
            .leads-stat-info    { display: flex; flex-direction: column; }
            .leads-stat-label   { font-size: 0.76rem; font-weight: 500; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
            .leads-stat-value   { font-size: 1.6rem; font-weight: 700; color: #fff; line-height: 1.1; margin-top: 2px; }

            /* ═══════════════════════════════════════
               TOOLBAR
            ═══════════════════════════════════════ */
            .leads-toolbar {
                display: flex; justify-content: space-between; align-items: center;
                gap: 12px; flex-wrap: wrap;
                background: rgba(30,41,59,0.6);
                border: 1px solid rgba(255,255,255,0.08);
                padding: 12px 18px; border-radius: 12px; margin-bottom: 20px;
            }
            .leads-search-form  { display: flex; align-items: center; gap: 10px; flex: 1; max-width: 500px; margin: 0; }
            .leads-search-input {
                flex: 1;
                background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.12);
                border-radius: 8px; padding: 7px 12px; font-size: 0.85rem; color: #f1f5f9; outline: none;
            }
            .leads-search-input:focus { border-color: #10b981; }
            .leads-filter-select {
                background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.12);
                border-radius: 8px; padding: 7px 12px; font-size: 0.85rem; color: #f1f5f9; outline: none;
            }
            .leads-btn-search { background: #10b981; color: #fff; border: none; border-radius: 8px; padding: 7px 14px; font-size: 0.85rem; font-weight: 600; cursor: pointer; }
            .leads-btn-reset  { background: rgba(255,255,255,0.08); color: #94a3b8; border: none; border-radius: 8px; padding: 7px 12px; font-size: 0.85rem; text-decoration: none; }
            .leads-btn-reset:hover { color: #fff; background: rgba(255,255,255,0.15); }

            /* ═══════════════════════════════════════
               TABLE
            ═══════════════════════════════════════ */
            .leads-table-container {
                background: linear-gradient(135deg, rgba(30,41,59,0.7), rgba(15,23,42,0.85));
                border: 1px solid rgba(255,255,255,0.08);
                border-radius: 16px; overflow: hidden;
                box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            }
            .leads-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; }
            .leads-table th {
                background: rgba(15,23,42,0.6); color: #94a3b8; font-weight: 600;
                text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.74rem;
                padding: 14px 18px; border-bottom: 1px solid rgba(255,255,255,0.08);
            }
            .leads-table td { padding: 14px 18px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cbd5e1; vertical-align: middle; }
            .leads-table tr:hover td { background: rgba(255,255,255,0.02); }
            .leads-table tr:last-child td { border-bottom: none; }

            /* ═══════════════════════════════════════
               BADGES
            ═══════════════════════════════════════ */
            .device-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
            .device-badge--mobile  { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
            .device-badge--desktop { background: rgba(99,102,241,0.15); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.3); }
            .utm-badge       { display: inline-block; padding: 3px 8px; border-radius: 6px; background: rgba(16,185,129,0.12); color: #34d399; font-size: 0.72rem; font-weight: 500; border: 1px solid rgba(16,185,129,0.25); max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .utm-badge--empty { background: rgba(255,255,255,0.05); color: #64748b; border: 1px solid rgba(255,255,255,0.08); }

            /* ═══════════════════════════════════════
               BUTTONS
            ═══════════════════════════════════════ */
            .btn-view-lead {
                display: inline-flex; align-items: center; gap: 6px;
                background: linear-gradient(135deg, #10b981, #059669); color: #fff;
                border: none; border-radius: 8px; padding: 6px 14px;
                font-size: 0.8rem; font-weight: 600; cursor: pointer;
                transition: transform 0.15s, box-shadow 0.15s;
            }
            .btn-view-lead:hover { transform: translateY(-1px); box-shadow: 0 3px 12px rgba(16,185,129,0.35); }
            .btn-wa-lead {
                display: inline-flex; align-items: center; gap: 5px;
                background: rgba(37,211,102,0.15); color: #25d366;
                border: 1px solid rgba(37,211,102,0.3); border-radius: 8px;
                padding: 5px 10px; font-size: 0.78rem; font-weight: 600;
                text-decoration: none; transition: background 0.15s;
            }
            .btn-wa-lead:hover { background: rgba(37,211,102,0.25); }

            /* ═══════════════════════════════════════
               MODAL
            ═══════════════════════════════════════ */
            .lead-modal-backdrop {
                position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
                background: rgba(5,8,15,0.82); backdrop-filter: blur(6px);
                display: none; align-items: center; justify-content: center;
                z-index: 99999; padding: 16px;
            }
            .lead-modal-backdrop.is-open { display: flex; animation: leadFade 0.2s cubic-bezier(0.16,1,0.3,1); }
            @keyframes leadFade { from { opacity:0; transform:scale(0.96); } to { opacity:1; transform:scale(1); } }
            .lead-modal-card {
                background: #111827; border: 1px solid rgba(255,255,255,0.12);
                border-radius: 20px; width: 100%; max-width: 680px; max-height: 90vh;
                overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6);
                display: flex; flex-direction: column;
            }
            .lead-modal-header {
                display: flex; justify-content: space-between; align-items: center;
                padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.08);
                background: rgba(17,24,39,0.98); position: sticky; top: 0; z-index: 10;
            }
            .lead-modal-header h3 { margin: 0; font-size: 1.1rem; color: #f8fafc; display: flex; align-items: center; gap: 10px; }
            .lead-modal-close {
                background: rgba(255,255,255,0.08); border: none; color: #94a3b8;
                width: 32px; height: 32px; border-radius: 8px; font-size: 1.3rem;
                cursor: pointer; display: flex; align-items: center; justify-content: center;
                transition: background 0.15s, color 0.15s; line-height: 1;
            }
            .lead-modal-close:hover { background: rgba(239,68,68,0.2); color: #ef4444; }
            .lead-modal-body { padding: 24px; display: flex; flex-direction: column; gap: 18px; }
            .detail-section { background: rgba(30,41,59,0.45); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 16px; }
            .detail-section-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #10b981; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
            .detail-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 12px 16px; }
            @media (max-width: 600px) { .detail-grid { grid-template-columns: 1fr; } }
            .detail-item { display: flex; flex-direction: column; gap: 3px; }
            .detail-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: 0.04em; }
            .detail-value { font-size: 0.9rem; color: #f1f5f9; font-weight: 500; word-break: break-word; }
            .detail-value--hl { color: #34d399; font-weight: 700; }
            .detail-value-box { background: rgba(15,23,42,0.7); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 10px 14px; font-size: 0.88rem; color: #e2e8f0; line-height: 1.5; white-space: pre-wrap; min-height: 60px; }
            .lead-modal-footer {
                display: flex; justify-content: flex-end; align-items: center; gap: 12px;
                padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.08);
                background: rgba(17,24,39,0.98);
            }
        </style>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'leads';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand'           => 'Aspal Emulsi',
                    'subtitle'        => 'Admin Intelligence',
                    'pageTitle'       => 'Leads Tracker',
                    'pageDescription' => 'Daftar prospek masuk via formulir WhatsApp beserta rincian tracking UTM, Device, dan Timestamp.',
                    'breadcrumbs'     => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Leads Tracker'],
                    ],
                ]);
            ?>

            <div class="workspace-content">

                <?php foreach ($notices as $notice): ?>
                    <?php $isOk = ($notice['type'] === 'success'); ?>
                    <div style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:<?= $isOk ? 'rgba(16,185,129,0.15)' : 'rgba(245,158,11,0.15)' ?>;color:<?= $isOk ? '#34d399' : '#fbbf24' ?>;border:1px solid <?= $isOk ? 'rgba(16,185,129,0.3)' : 'rgba(245,158,11,0.3)' ?>">
                        <?= htmlspecialchars($notice['message'], ENT_QUOTES) ?>
                    </div>
                <?php endforeach; ?>

                <!-- STATISTIK KARTU -->
                <section class="leads-stats-row" data-animate="fade-up">
                    <div class="leads-stat-card">
                        <div class="leads-stat-icon stat-icon--total">📊</div>
                        <div class="leads-stat-info">
                            <span class="leads-stat-label">Total Leads</span>
                            <span class="leads-stat-value"><?= number_format((int) ($stats['total'] ?? 0)) ?></span>
                        </div>
                    </div>
                    <div class="leads-stat-card">
                        <div class="leads-stat-icon stat-icon--today">⚡</div>
                        <div class="leads-stat-info">
                            <span class="leads-stat-label">Leads Hari Ini</span>
                            <span class="leads-stat-value"><?= number_format((int) ($stats['today_count'] ?? 0)) ?></span>
                        </div>
                    </div>
                    <div class="leads-stat-card">
                        <div class="leads-stat-icon stat-icon--mobile">📱</div>
                        <div class="leads-stat-info">
                            <span class="leads-stat-label">Mobile</span>
                            <span class="leads-stat-value"><?= number_format((int) ($stats['mobile_count'] ?? 0)) ?></span>
                        </div>
                    </div>
                    <div class="leads-stat-card">
                        <div class="leads-stat-icon stat-icon--desktop">💻</div>
                        <div class="leads-stat-info">
                            <span class="leads-stat-label">Desktop</span>
                            <span class="leads-stat-value"><?= number_format((int) ($stats['desktop_count'] ?? 0)) ?></span>
                        </div>
                    </div>
                </section>

                <!-- TOOLBAR FILTER -->
                <div class="leads-toolbar" data-animate="fade-up">
                    <form method="get" action="<?= admin_url('leads') ?>" class="leads-search-form">
                        <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>" placeholder="Cari nama, hp, kebutuhan, UTM..." class="leads-search-input">
                        <select name="device" class="leads-filter-select">
                            <option value="">Semua Perangkat</option>
                            <option value="Mobile"  <?= $filterDevice === 'Mobile'  ? 'selected' : '' ?>>📱 Mobile</option>
                            <option value="Desktop" <?= $filterDevice === 'Desktop' ? 'selected' : '' ?>>💻 Desktop</option>
                        </select>
                        <button type="submit" class="leads-btn-search">Cari</button>
                        <?php if ($search !== '' || $filterDevice !== ''): ?>
                            <a href="<?= admin_url('leads') ?>" class="leads-btn-reset">Reset</a>
                        <?php endif; ?>
                    </form>
                    <span style="font-size:0.8rem;color:#94a3b8;">
                        Menampilkan <strong style="color:#f1f5f9;"><?= count($leads) ?></strong> data
                    </span>
                </div>

                <!-- TABEL LEADS -->
                <div class="leads-table-container" data-animate="fade-up">
                    <table class="leads-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Waktu Masuk</th>
                                <th>Nama</th>
                                <th>WhatsApp</th>
                                <th>Kebutuhan</th>
                                <th>Perangkat</th>
                                <th>UTM Source</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($leads === []): ?>
                            <tr>
                                <td colspan="8" style="text-align:center;padding:48px;color:#64748b;">
                                    <div style="font-size:2rem;margin-bottom:8px;">📭</div>
                                    <div>Belum ada leads yang masuk.<br><small>Leads akan muncul setelah ada pengunjung yang mengisi form WA.</small></div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leads as $lead):
                                $leadCreated = (string) ($lead['created_at'] ?? '');
                                $waLink = formatWaLink((string) ($lead['phone'] ?? ''), (string) ($lead['name'] ?? ''));
                                $jsonPayload = htmlspecialchars(json_encode([
                                    'id'           => (int) $lead['id'],
                                    'name'         => (string) ($lead['name'] ?? ''),
                                    'phone'        => (string) ($lead['phone'] ?? ''),
                                    'needs'        => (string) ($lead['needs'] ?? ''),
                                    'source'       => (string) ($lead['source'] ?? 'floating_wa'),
                                    'device'       => (string) ($lead['device'] ?? 'Desktop'),
                                    'ip_address'   => (string) ($lead['ip_address'] ?? '-'),
                                    'user_agent'   => (string) ($lead['user_agent'] ?? '-'),
                                    'utm_source'   => (string) ($lead['utm_source'] ?? ''),
                                    'utm_medium'   => (string) ($lead['utm_medium'] ?? ''),
                                    'utm_campaign' => (string) ($lead['utm_campaign'] ?? ''),
                                    'utm_term'     => (string) ($lead['utm_term'] ?? ''),
                                    'utm_content'  => (string) ($lead['utm_content'] ?? ''),
                                    'referrer'     => (string) ($lead['referrer'] ?? ''),
                                    'created_wib'  => $leadCreated !== '' ? date('d M Y, H:i', strtotime($leadCreated)) . ' WIB' : '-',
                                    'created_utc'  => $leadCreated !== '' ? gmdate('Y-m-d H:i:s', strtotime($leadCreated)) . ' UTC' : '-',
                                    'time_ago'     => $leadCreated !== '' ? leadTimeAgo($leadCreated) : '-',
                                    'wa_link'      => $waLink,
                                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <td style="color:#64748b;font-size:0.8rem;"><?= (int) $lead['id'] ?></td>
                                <td>
                                    <div style="font-weight:600;color:#f1f5f9;"><?= $leadCreated !== '' ? date('d M Y, H:i', strtotime($leadCreated)) : '-' ?></div>
                                    <div style="font-size:0.72rem;color:#64748b;"><?= $leadCreated !== '' ? leadTimeAgo($leadCreated) : '' ?></div>
                                </td>
                                <td><strong style="color:#fff;"><?= htmlspecialchars((string) ($lead['name'] ?? '-'), ENT_QUOTES) ?></strong></td>
                                <td>
                                    <a href="<?= htmlspecialchars($waLink, ENT_QUOTES) ?>" target="_blank" rel="noopener" class="btn-wa-lead">
                                        💬 <?= htmlspecialchars((string) ($lead['phone'] ?? '-'), ENT_QUOTES) ?>
                                    </a>
                                </td>
                                <td>
                                    <div style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#94a3b8;" title="<?= htmlspecialchars((string) ($lead['needs'] ?? ''), ENT_QUOTES) ?>">
                                        <?= htmlspecialchars((string) ($lead['needs'] ?? '-'), ENT_QUOTES) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (($lead['device'] ?? '') === 'Mobile'): ?>
                                        <span class="device-badge device-badge--mobile">📱 Mobile</span>
                                    <?php else: ?>
                                        <span class="device-badge device-badge--desktop">💻 Desktop</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($lead['utm_source'])): ?>
                                        <span class="utm-badge" title="<?= htmlspecialchars((string) $lead['utm_source'], ENT_QUOTES) ?>"><?= htmlspecialchars((string) $lead['utm_source'], ENT_QUOTES) ?></span>
                                    <?php else: ?>
                                        <span class="utm-badge utm-badge--empty">Direct</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <button type="button" class="btn-view-lead" data-lead='<?= $jsonPayload ?>' onclick="openLeadModal(this)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div><!-- /workspace-content -->
        </main>

        <!-- ══════════════════════════════════════════
             MODAL DETAIL RINCIAN PROSPEK
        ══════════════════════════════════════════ -->
        <div id="leadDetailModal" class="lead-modal-backdrop" onclick="handleBackdropClick(event)">
            <div class="lead-modal-card">
                <div class="lead-modal-header">
                    <h3>
                        👤&nbsp;Rincian Prospek #<span id="modalLeadId">-</span>
                    </h3>
                    <button type="button" class="lead-modal-close" onclick="closeLeadModal()" title="Tutup">&times;</button>
                </div>
                <div class="lead-modal-body">

                    <!-- 1. TIMESTAMP & DEVICE -->
                    <div class="detail-section">
                        <div class="detail-section-title">⏱️ Timestamp &amp; Perangkat</div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Waktu (WIB)</span>
                                <span class="detail-value detail-value--hl" id="modalCreatedWib">-</span>
                                <small style="color:#64748b;" id="modalTimeAgo">-</small>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tracking UTC</span>
                                <span class="detail-value" id="modalCreatedUtc">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Perangkat</span>
                                <div id="modalDeviceBadge">-</div>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">IP Address</span>
                                <span class="detail-value" id="modalIp">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. KONTAK & KEBUTUHAN -->
                    <div class="detail-section">
                        <div class="detail-section-title">📋 Kontak &amp; Kebutuhan</div>
                        <div class="detail-grid" style="margin-bottom:12px;">
                            <div class="detail-item">
                                <span class="detail-label">Nama Prospek</span>
                                <span class="detail-value" style="font-size:1.05rem;font-weight:700;color:#fff;" id="modalName">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Nomor WhatsApp</span>
                                <span class="detail-value" id="modalPhone">-</span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label" style="margin-bottom:6px;">Kebutuhan / Pesan Prospek</span>
                            <div class="detail-value-box" id="modalNeeds">-</div>
                        </div>
                    </div>

                    <!-- 3. UTM TRACKING -->
                    <div class="detail-section">
                        <div class="detail-section-title">🎯 Tracking UTM &amp; Sumber Traffic</div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">UTM Source</span>
                                <span class="detail-value" id="modalUtmSource">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">UTM Medium</span>
                                <span class="detail-value" id="modalUtmMedium">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">UTM Campaign</span>
                                <span class="detail-value" id="modalUtmCampaign">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">UTM Term</span>
                                <span class="detail-value" id="modalUtmTerm">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">UTM Content</span>
                                <span class="detail-value" id="modalUtmContent">-</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Sumber Form</span>
                                <span class="detail-value" id="modalSource">-</span>
                            </div>
                        </div>
                        <div class="detail-item" style="margin-top:10px;">
                            <span class="detail-label">Referrer URL</span>
                            <span class="detail-value" style="font-size:0.8rem;color:#94a3b8;word-break:break-all;" id="modalReferrer">-</span>
                        </div>
                    </div>

                    <!-- 4. USER AGENT -->
                    <div class="detail-section">
                        <div class="detail-section-title">🌐 User Agent</div>
                        <div style="font-size:0.76rem;color:#64748b;line-height:1.5;word-break:break-all;" id="modalUserAgent">-</div>
                    </div>

                </div>
                <div class="lead-modal-footer">
                    <form method="post" action="<?= admin_url('leads') ?>" onsubmit="return confirm('Hapus data lead ini?');" style="margin:0;margin-right:auto;">
                        <input type="hidden" name="action" value="delete_lead">
                        <input type="hidden" name="id" id="modalDeleteId" value="">
                        <button type="submit" style="background:rgba(239,68,68,0.15);color:#ef4444;border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:7px 14px;font-size:0.82rem;font-weight:600;cursor:pointer;">
                            Hapus Lead
                        </button>
                    </form>
                    <a id="modalWaBtn" href="#" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#25d366,#128c7e);color:#fff;text-decoration:none;border-radius:8px;padding:7px 16px;font-size:0.85rem;font-weight:600;">
                        💬 Chat WhatsApp
                    </a>
                    <button type="button" onclick="closeLeadModal()" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);color:#94a3b8;border-radius:8px;padding:7px 14px;font-size:0.85rem;cursor:pointer;">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <script>
        function openLeadModal(btn) {
            try {
                var raw = btn.getAttribute('data-lead');
                var d = JSON.parse(raw);

                document.getElementById('modalLeadId').textContent    = d.id;
                document.getElementById('modalDeleteId').value         = d.id;
                document.getElementById('modalName').textContent       = d.name  || '-';
                document.getElementById('modalPhone').textContent      = d.phone || '-';
                document.getElementById('modalNeeds').textContent      = d.needs || '(Tidak ada catatan)';
                document.getElementById('modalCreatedWib').textContent = d.created_wib;
                document.getElementById('modalTimeAgo').textContent    = '(' + d.time_ago + ')';
                document.getElementById('modalCreatedUtc').textContent = d.created_utc;
                document.getElementById('modalIp').textContent         = d.ip_address || '-';
                document.getElementById('modalUserAgent').textContent  = d.user_agent || '-';
                document.getElementById('modalUtmSource').textContent   = d.utm_source   || '(Direct)';
                document.getElementById('modalUtmMedium').textContent   = d.utm_medium   || '(None)';
                document.getElementById('modalUtmCampaign').textContent = d.utm_campaign || '(None)';
                document.getElementById('modalUtmTerm').textContent     = d.utm_term     || '(None)';
                document.getElementById('modalUtmContent').textContent  = d.utm_content  || '(None)';
                document.getElementById('modalSource').textContent      = d.source       || 'floating_wa';
                document.getElementById('modalReferrer').textContent    = d.referrer     || '(Direct / Bookmark)';
                document.getElementById('modalWaBtn').href              = d.wa_link;

                var badgeEl = document.getElementById('modalDeviceBadge');
                if (d.device === 'Mobile') {
                    badgeEl.innerHTML = '<span class="device-badge device-badge--mobile">📱 Mobile</span>';
                } else {
                    badgeEl.innerHTML = '<span class="device-badge device-badge--desktop">💻 Desktop</span>';
                }

                document.getElementById('leadDetailModal').classList.add('is-open');
                document.body.style.overflow = 'hidden';
            } catch(e) {
                console.error('Lead modal error:', e);
            }
        }

        function closeLeadModal() {
            document.getElementById('leadDetailModal').classList.remove('is-open');
            document.body.style.overflow = '';
        }

        function handleBackdropClick(e) {
            if (e.target.id === 'leadDetailModal') closeLeadModal();
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLeadModal();
        });
        </script>
    </body>
</html>
