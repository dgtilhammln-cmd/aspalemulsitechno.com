<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireAuth();

$pdo = getPDO();
$currentUser = adminCurrentUser();
$currentUserId = $currentUser['id'] ?? null;

/**
 * MUNG CMS V1
 * File: admin/settings.php
 * Author: Firmanda Himawan
 * Description: Configuration panel for site-wide identity, contact, and SEO metadata management.
 */

function fetchSettings(PDO $pdo): array
{
    $statement = $pdo->query('SELECT * FROM site_settings ORDER BY id ASC LIMIT 1');
    $settings = $statement->fetch();

    if ($settings === false) {
        $insert = $pdo->prepare('INSERT INTO site_settings (company_name) VALUES (:company_name)');
        $insert->execute(['company_name' => 'Aspal Emulsi Indonesia']);

        $statement = $pdo->query('SELECT * FROM site_settings ORDER BY id ASC LIMIT 1');
        $settings = $statement->fetch() ?: [];
    }

    return $settings ?: [];
}

$settings = fetchSettings($pdo);
$settingsId = (int) ($settings['id'] ?? 1);

$errors = [];
$notices = [];

$fields = [
    'company_name',
    'tagline',
    'meta_title',
    'meta_description',
    'meta_keywords',
    'meta_robots',
    'company_email',
    'company_phone',
    'whatsapp_number',
    'address',
    'map_embed_url',
    'working_hours',
    'shopee_url',
    'tokopedia_url',
    'facebook_url',
    'instagram_url',
    'linkedin_url',
    'youtube_url',
    'tiktok_url',
    'twitter_url',
    'logo_url',
    'favicon_url',
];

$formData = [];
foreach ($fields as $field) {
    $formData[$field] = isset($settings[$field]) ? (string) $settings[$field] : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = trim($_POST['company_name'] ?? '');
    $tagline = trim($_POST['tagline'] ?? '');
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $metaKeywords = trim($_POST['meta_keywords'] ?? '');
    $metaRobots = trim($_POST['meta_robots'] ?? '');
    $companyEmail = trim($_POST['company_email'] ?? '');
    $companyPhone = trim($_POST['company_phone'] ?? '');
    $whatsappNumber = trim($_POST['whatsapp_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $mapEmbedUrl = trim($_POST['map_embed_url'] ?? '');
    $workingHours = trim($_POST['working_hours'] ?? '');
    $shopeeUrl = trim($_POST['shopee_url'] ?? '');
    $tokopediaUrl = trim($_POST['tokopedia_url'] ?? '');
    $facebookUrl = trim($_POST['facebook_url'] ?? '');
    $instagramUrl = trim($_POST['instagram_url'] ?? '');
    $linkedinUrl = trim($_POST['linkedin_url'] ?? '');
    $youtubeUrl = trim($_POST['youtube_url'] ?? '');
    $tiktokUrl = trim($_POST['tiktok_url'] ?? '');
    $twitterUrl = trim($_POST['twitter_url'] ?? '');
    $existingLogo = trim($_POST['existing_logo'] ?? '');
    $existingFavicon = trim($_POST['existing_favicon'] ?? '');

    if ($companyName === '') {
        $errors[] = 'Nama perusahaan wajib diisi.';
    }

    if ($companyEmail !== '' && !filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email perusahaan tidak valid.';
    }

    $urlFields = [
        ['label' => 'Link Shopee', 'value' => $shopeeUrl],
        ['label' => 'Link Tokopedia', 'value' => $tokopediaUrl],
        ['label' => 'Link Facebook', 'value' => $facebookUrl],
        ['label' => 'Link Instagram', 'value' => $instagramUrl],
        ['label' => 'Link LinkedIn', 'value' => $linkedinUrl],
        ['label' => 'Link YouTube', 'value' => $youtubeUrl],
        ['label' => 'Link TikTok', 'value' => $tiktokUrl],
        ['label' => 'Link Twitter/X', 'value' => $twitterUrl],
    ];

    foreach ($urlFields as $urlField) {
        if ($urlField['value'] !== '' && !filter_var($urlField['value'], FILTER_VALIDATE_URL)) {
            $errors[] = $urlField['label'] . ' tidak valid.';
        }
    }

    $logoUrl = $existingLogo;
    $faviconUrl = $existingFavicon;

    $uploadLogo = $_FILES['logo'] ?? null;
    $uploadFavicon = $_FILES['favicon'] ?? null;

    $uploadsDir = dirname(__DIR__) . '/uploads';
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0755, true);
    }

    // Logo: kompres dan konversi ke WebP
    $uploadedLogo = adminHandleImageUpload($uploadLogo, 'logo', $uploadsDir, $errors);
    if ($uploadedLogo !== null) {
        $logoUrl = $uploadedLogo;
    }

    // Favicon: tetap format asli (ICO/SVG) karena format khusus
    $faviconAllowedMime = ['image/svg+xml', 'image/x-icon', 'image/png'];
    if ($uploadFavicon && isset($uploadFavicon['error']) && $uploadFavicon['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($uploadFavicon['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload favicon gagal.';
        } else {
            $detectedType = mime_content_type($uploadFavicon['tmp_name']);
            if (!in_array($detectedType, $faviconAllowedMime, true)) {
                $errors[] = 'Favicon harus berupa ICO, PNG, atau SVG.';
            } else {
                $extension = match ($detectedType) {
                    'image/svg+xml' => 'svg',
                    'image/x-icon' => 'ico',
                    default => 'png',
                };
                $filename = sprintf('favicon-%s.%s', date('YmdHis') . '-' . bin2hex(random_bytes(4)), $extension);
                $destination = $uploadsDir . '/' . $filename;

                if (!move_uploaded_file($uploadFavicon['tmp_name'], $destination)) {
                    $errors[] = 'Gagal menyimpan file favicon.';
                } else {
                    $faviconUrl = '/uploads/' . $filename;
                }
            }
        }
    }

    $formData = [
        'company_name' => $companyName,
        'tagline' => $tagline,
        'meta_title' => $metaTitle,
        'meta_description' => $metaDescription,
        'meta_keywords' => $metaKeywords,
        'meta_robots' => $metaRobots,
        'company_email' => $companyEmail,
        'company_phone' => $companyPhone,
        'whatsapp_number' => $whatsappNumber,
        'address' => $address,
        'map_embed_url' => $mapEmbedUrl,
        'working_hours' => $workingHours,
        'shopee_url' => $shopeeUrl,
        'tokopedia_url' => $tokopediaUrl,
        'facebook_url' => $facebookUrl,
        'instagram_url' => $instagramUrl,
        'linkedin_url' => $linkedinUrl,
        'youtube_url' => $youtubeUrl,
        'tiktok_url' => $tiktokUrl,
        'twitter_url' => $twitterUrl,
        'logo_url' => $logoUrl,
        'favicon_url' => $faviconUrl,
    ];

    if ($errors === []) {
        $updateStmt = $pdo->prepare(
            'UPDATE site_settings
             SET company_name = :company_name,
                 tagline = :tagline,
                 meta_title = :meta_title,
                 meta_description = :meta_description,
                 meta_keywords = :meta_keywords,
                 meta_robots = :meta_robots,
                 company_email = :company_email,
                 company_phone = :company_phone,
                 whatsapp_number = :whatsapp_number,
                 address = :address,
                 map_embed_url = :map_embed_url,
                 working_hours = :working_hours,
                 shopee_url = :shopee_url,
                 tokopedia_url = :tokopedia_url,
                 facebook_url = :facebook_url,
                 instagram_url = :instagram_url,
                 linkedin_url = :linkedin_url,
                 youtube_url = :youtube_url,
                 tiktok_url = :tiktok_url,
                 twitter_url = :twitter_url,
                 logo_url = :logo_url,
                 favicon_url = :favicon_url,
                 updated_by = :updated_by
             WHERE id = :id'
        );

        $updateStmt->execute([
            'company_name' => $companyName,
            'tagline' => $tagline !== '' ? $tagline : null,
            'meta_title' => $metaTitle !== '' ? $metaTitle : null,
            'meta_description' => $metaDescription !== '' ? $metaDescription : null,
            'meta_keywords' => $metaKeywords !== '' ? $metaKeywords : null,
            'meta_robots' => $metaRobots !== '' ? $metaRobots : null,
            'company_email' => $companyEmail !== '' ? $companyEmail : null,
            'company_phone' => $companyPhone !== '' ? $companyPhone : null,
            'whatsapp_number' => $whatsappNumber !== '' ? $whatsappNumber : null,
            'address' => $address !== '' ? $address : null,
            'map_embed_url' => $mapEmbedUrl !== '' ? $mapEmbedUrl : null,
            'working_hours' => $workingHours !== '' ? $workingHours : null,
            'shopee_url' => $shopeeUrl !== '' ? $shopeeUrl : null,
            'tokopedia_url' => $tokopediaUrl !== '' ? $tokopediaUrl : null,
            'facebook_url' => $facebookUrl !== '' ? $facebookUrl : null,
            'instagram_url' => $instagramUrl !== '' ? $instagramUrl : null,
            'linkedin_url' => $linkedinUrl !== '' ? $linkedinUrl : null,
            'youtube_url' => $youtubeUrl !== '' ? $youtubeUrl : null,
            'tiktok_url' => $tiktokUrl !== '' ? $tiktokUrl : null,
            'twitter_url' => $twitterUrl !== '' ? $twitterUrl : null,
            'logo_url' => $logoUrl !== '' ? $logoUrl : null,
            'favicon_url' => $faviconUrl !== '' ? $faviconUrl : null,
            'updated_by' => $currentUserId,
            'id' => $settingsId,
        ]);

        admin_redirect('settings', ['notice' => 'updated']);
    }
}

if (isset($_GET['notice']) && $_GET['notice'] === 'updated') {
    $notices[] = ['type' => 'success', 'message' => 'Pengaturan perusahaan berhasil disimpan.'];
}

$updatedAtLabel = 'Belum pernah';
try {
    if (!empty($settings['updated_at'])) {
        $updatedAtLabel = (new DateTime((string) $settings['updated_at']))->format('d M Y H:i');
    }
} catch (Throwable $throwable) {
    $updatedAtLabel = (string) ($settings['updated_at'] ?? 'Belum pernah');
}

$contactFieldsKeys = ['company_email', 'company_phone', 'whatsapp_number', 'address'];
$contactCompleteness = 0;
foreach ($contactFieldsKeys as $key) {
    if (!empty($formData[$key])) {
        $contactCompleteness++;
    }
}

$marketplaceKeys = ['shopee_url', 'tokopedia_url'];
$marketplaceActive = 0;
foreach ($marketplaceKeys as $key) {
    if (!empty($formData[$key])) {
        $marketplaceActive++;
    }
}

$socialKeys = ['facebook_url', 'instagram_url', 'linkedin_url', 'youtube_url', 'tiktok_url', 'twitter_url'];
$socialActive = 0;
foreach ($socialKeys as $key) {
    if (!empty($formData[$key])) {
        $socialActive++;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Pengaturan Perusahaan | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
        <script src="app.js" defer></script>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'settings';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Pengaturan Perusahaan',
                    'pageDescription' => 'Kelola identitas brand, informasi kontak, dan SEO halaman utama.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Pengaturan'],
                    ],
                    'quickActions' => [
                        [
                            'label' => 'Kelola User',
                            'href' => admin_url('users'),
                            'variant' => 'ghost',
                        ],
                        [
                            'label' => 'Lihat Website',
                            'href' => '/',
                            'variant' => 'outline',
                        ],
                    ],
                ]);
            ?>
            <div class="workspace-content">
                <section class="workspace-meta">
                    <span class="status-pill">Terakhir update: <?= htmlspecialchars($updatedAtLabel, ENT_QUOTES) ?></span>
                    <span class="chip chip--success">Kontak <?= $contactCompleteness ?>/<?= count($contactFieldsKeys) ?></span>
                    <span class="chip chip--neutral">Marketplace aktif <?= $marketplaceActive ?></span>
                    <span class="chip chip--default">Sosial aktif <?= $socialActive ?></span>
                </section>

                <section class="card">
                <header>
                    <h2>Detail Perusahaan</h2>
                </header>
                <form method="post" class="form-grid form-settings" enctype="multipart/form-data">
                    <h3 class="form-section-title">Identitas Brand</h3>
                    <span class="form-section-description">Nama perusahaan dan tagline akan tampil di navigasi dan hero landing page.</span>
                    <label class="form-field">
                        <span>Nama Perusahaan *</span>
                        <input type="text" name="company_name" required value="<?= htmlspecialchars($formData['company_name'], ENT_QUOTES) ?>" placeholder="PT Aspal Emulsi Indonesia">
                    </label>
                    <label class="form-field">
                        <span>Tagline</span>
                        <input type="text" name="tagline" value="<?= htmlspecialchars($formData['tagline'], ENT_QUOTES) ?>" placeholder="Solusi Infrastruktur Jalan Berkelanjutan">
                    </label>
                    <label class="form-field">
                        <span>Logo</span>
                        <input type="file" name="logo" accept="image/jpeg,image/png,image/webp">
                        <small style="display: block; margin-top: 4px; color: #666;">
                            <strong>Note:</strong> Ukuran maksimal 10MB. Gambar akan otomatis dikompres dan dikonversi ke format WebP untuk optimasi kualitas dan ukuran file yang lebih kecil.
                        </small>
                        <?php if ($formData['logo_url'] !== ''): ?>
                            <input type="hidden" name="existing_logo" value="<?= htmlspecialchars($formData['logo_url'], ENT_QUOTES) ?>">
                            <div class="preview-thumb">
                                <img src="<?= htmlspecialchars($formData['logo_url'], ENT_QUOTES) ?>" alt="Logo">
                                <span>Upload file baru untuk mengganti logo.</span>
                            </div>
                        <?php endif; ?>
                    </label>
                    <label class="form-field">
                        <span>Favicon</span>
                        <input type="file" name="favicon" accept="image/x-icon,image/svg+xml,image/png">
                        <small style="display: block; margin-top: 4px; color: #666;">
                            <strong>Note:</strong> Format ICO, PNG, atau SVG. Ukuran maksimal 2MB.
                        </small>
                        <?php if ($formData['favicon_url'] !== ''): ?>
                            <input type="hidden" name="existing_favicon" value="<?= htmlspecialchars($formData['favicon_url'], ENT_QUOTES) ?>">
                            <div class="preview-thumb">
                                <img src="<?= htmlspecialchars($formData['favicon_url'], ENT_QUOTES) ?>" alt="Favicon" style="max-width:64px;">
                                <span>Upload file baru untuk mengganti favicon.</span>
                            </div>
                        <?php endif; ?>
                    </label>

                    <h3 class="form-section-title">SEO Homepage</h3>
                    <span class="form-section-description">Optimalkan meta tag untuk meningkatkan visibilitas mesin pencari.</span>
                    <label class="form-field full">
                        <span>Meta Title</span>
                        <input type="text" name="meta_title" value="<?= htmlspecialchars($formData['meta_title'], ENT_QUOTES) ?>" placeholder="Aspal Emulsi Indonesia | Solusi Infrastruktur Jalan">
                    </label>
                    <label class="form-field full">
                        <span>Meta Description</span>
                        <textarea name="meta_description" rows="3" placeholder="Deskripsi singkat 150-160 karakter"><?= htmlspecialchars($formData['meta_description'], ENT_QUOTES) ?></textarea>
                    </label>
                    <label class="form-field full">
                        <span>Meta Keywords</span>
                        <input type="text" name="meta_keywords" value="<?= htmlspecialchars($formData['meta_keywords'], ENT_QUOTES) ?>" placeholder="aspal emulsi, overlay jalan, waterproofing">
                    </label>
                    <label class="form-field full">
                        <span>Meta Robots</span>
                        <select name="meta_robots">
                            <option value="">Gunakan default (index, follow)</option>
                            <option value="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" <?= ($formData['meta_robots'] ?? '') === 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' ? 'selected' : '' ?>>Index, Follow (Recommended)</option>
                            <option value="index, follow" <?= ($formData['meta_robots'] ?? '') === 'index, follow' ? 'selected' : '' ?>>Index, Follow (Simple)</option>
                            <option value="index, nofollow" <?= ($formData['meta_robots'] ?? '') === 'index, nofollow' ? 'selected' : '' ?>>Index, No Follow</option>
                            <option value="noindex, follow" <?= ($formData['meta_robots'] ?? '') === 'noindex, follow' ? 'selected' : '' ?>>No Index, Follow</option>
                            <option value="noindex, nofollow" <?= ($formData['meta_robots'] ?? '') === 'noindex, nofollow' ? 'selected' : '' ?>>No Index, No Follow</option>
                        </select>
                        <small style="display:block;margin-top:4px;color:#666;font-size:13px;">
                            <strong>Penting:</strong> "No Index, No Follow" akan mencegah Google mengindeks website. Gunakan hanya untuk development/staging.
                        </small>
                    </label>

                    <h3 class="form-section-title">Kontak & Operasional</h3>
                    <label class="form-field">
                        <span>Email Perusahaan</span>
                        <input type="email" name="company_email" value="<?= htmlspecialchars($formData['company_email'], ENT_QUOTES) ?>" placeholder="info@aspalemulsi.local">
                    </label>
                    <label class="form-field">
                        <span>Telepon Kantor</span>
                        <input type="text" name="company_phone" value="<?= htmlspecialchars($formData['company_phone'], ENT_QUOTES) ?>" placeholder="+62 21 5089 4400">
                    </label>
                    <label class="form-field">
                        <span>WhatsApp</span>
                        <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($formData['whatsapp_number'], ENT_QUOTES) ?>" placeholder="+62 812 3456 7890">
                    </label>
                    <label class="form-field full">
                        <span>Alamat Lengkap</span>
                        <textarea name="address" rows="3" placeholder="Alamat kantor pusat"><?= htmlspecialchars($formData['address'], ENT_QUOTES) ?></textarea>
                    </label>
                    <label class="form-field full">
                        <span>Link/Embed Maps</span>
                        <textarea name="map_embed_url" rows="3" placeholder="https://maps.google.com/..."><?= htmlspecialchars($formData['map_embed_url'], ENT_QUOTES) ?></textarea>
                    </label>
                    <label class="form-field full">
                        <span>Jam Operasional</span>
                        <textarea name="working_hours" rows="3" placeholder="Contoh: Senin - Jumat 08:00 - 17:00"><?= htmlspecialchars($formData['working_hours'], ENT_QUOTES) ?></textarea>
                    </label>

                    <h3 class="form-section-title">Marketplace & Sosial Media</h3>
                    <label class="form-field">
                        <span>Link Shopee</span>
                        <input type="url" name="shopee_url" value="<?= htmlspecialchars($formData['shopee_url'], ENT_QUOTES) ?>" placeholder="https://shopee.co.id/...">
                    </label>
                    <label class="form-field">
                        <span>Link Tokopedia</span>
                        <input type="url" name="tokopedia_url" value="<?= htmlspecialchars($formData['tokopedia_url'], ENT_QUOTES) ?>" placeholder="https://www.tokopedia.com/...">
                    </label>
                    <label class="form-field">
                        <span>Facebook</span>
                        <input type="url" name="facebook_url" value="<?= htmlspecialchars($formData['facebook_url'], ENT_QUOTES) ?>" placeholder="https://facebook.com/...">
                    </label>
                    <label class="form-field">
                        <span>Instagram</span>
                        <input type="url" name="instagram_url" value="<?= htmlspecialchars($formData['instagram_url'], ENT_QUOTES) ?>" placeholder="https://instagram.com/...">
                    </label>
                    <label class="form-field">
                        <span>LinkedIn</span>
                        <input type="url" name="linkedin_url" value="<?= htmlspecialchars($formData['linkedin_url'], ENT_QUOTES) ?>" placeholder="https://linkedin.com/company/...">
                    </label>
                    <label class="form-field">
                        <span>YouTube</span>
                        <input type="url" name="youtube_url" value="<?= htmlspecialchars($formData['youtube_url'], ENT_QUOTES) ?>" placeholder="https://youtube.com/...">
                    </label>
                    <label class="form-field">
                        <span>TikTok</span>
                        <input type="url" name="tiktok_url" value="<?= htmlspecialchars($formData['tiktok_url'], ENT_QUOTES) ?>" placeholder="https://www.tiktok.com/@...">
                    </label>
                    <label class="form-field">
                        <span>Twitter / X</span>
                        <input type="url" name="twitter_url" value="<?= htmlspecialchars($formData['twitter_url'], ENT_QUOTES) ?>" placeholder="https://twitter.com/...">
                    </label>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                    </div>
                </form>
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


