<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireAuth();

// Only superadmin can access
if (!adminCurrentUserHasRole('superadmin')) {
    abort_admin(403, 'Akses ditolak. Hanya Super Admin yang dapat mengakses halaman ini.');
}

$pdo = getPDO();
$currentUser = adminCurrentUser();
$currentUserId = $currentUser['id'] ?? null;

/**
 * MUNG CMS V1
 * File: admin/theme-settings.php
 * Description: Theme management panel for superadmin - manage active theme and custom colors
 */

function fetchThemeSettings(PDO $pdo): array
{
    $statement = $pdo->query('SELECT * FROM theme_settings ORDER BY id ASC LIMIT 1');
    $settings = $statement->fetch(PDO::FETCH_ASSOC);
    
    if ($settings === false) {
        // Create default if not exists with nested structure (per-theme colors)
        $defaultThemeColors = [
            'primary' => '#ff6600',
            'primaryLight' => '#ff7a00',
            'primaryDark' => '#ff6d00',
            'secondary' => '#ffaa00',
            'accent' => '#ffd873',
            'accentLight' => '#ffe8b3',
            'background' => '#000000',
            'backgroundDark' => '#0a0a0a',
            'backgroundLight' => '#1a1a1a',
            'backgroundMedium' => '#333333',
            'textPrimary' => '#ffffff',
            'textSecondary' => '#e0e0e0',
            'textTertiary' => '#b0b0b0',
            'textMuted' => '#888888',
            'border' => '#333333',
            'borderLight' => '#555555',
            'overlay' => 'rgba(0, 0, 0, 0.8)',
            'overlayHover' => 'rgba(0, 0, 0, 0.9)',
        ];
        
        $konstruksiThemeColors = [
            'primary' => '#ff6600',
            'primaryLight' => '#ff8533',
            'primaryDark' => '#cc5200',
            'secondary' => '#0066cc',
            'accent' => '#4da6ff',
            'accentLight' => '#b3d9ff',
            'background' => '#ffffff',
            'backgroundDark' => '#f5f7fa',
            'backgroundLight' => '#fafbfc',
            'backgroundMedium' => '#e8ecf1',
            'textPrimary' => '#1a1f2e',
            'textSecondary' => '#4a5568',
            'textTertiary' => '#718096',
            'textMuted' => '#a0aec0',
            'border' => '#e2e8f0',
            'borderLight' => '#f1f5f9',
            'overlay' => 'rgba(26, 31, 46, 0.6)',
            'overlayHover' => 'rgba(26, 31, 46, 0.7)',
        ];
        
        // Nested structure: each theme has its own colors
        $nestedColors = [
            'default' => $defaultThemeColors,
            'konstruksi' => $konstruksiThemeColors,
        ];
        
        $insert = $pdo->prepare('INSERT INTO theme_settings (active_theme, theme_colors) VALUES (:theme, :colors)');
        $insert->execute([
            'theme' => 'default',
            'colors' => json_encode($nestedColors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        
        $statement = $pdo->query('SELECT * FROM theme_settings ORDER BY id ASC LIMIT 1');
        $settings = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    return $settings ?: [];
}

// Helper function to convert hex/rgba to hex for color picker
function colorToHex(string $color): string
{
    // If already hex, return as is
    if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
        // Convert 3-digit hex to 6-digit
        if (strlen($color) === 4) {
            return '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
        }
        return $color;
    }
    
    // If rgba, convert to hex
    if (preg_match('/^rgba?\(([\d\s,\.]+)\)$/', $color, $matches)) {
        $values = array_map('trim', explode(',', $matches[1]));
        $r = (int) $values[0];
        $g = (int) $values[1];
        $b = (int) $values[2];
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    return '#000000';
}

$settings = fetchThemeSettings($pdo);
$settingsId = (int) ($settings['id'] ?? 1);
$activeTheme = $settings['active_theme'] ?? 'default';
$allThemesColors = json_decode($settings['theme_colors'] ?? '{}', true) ?: [];

// Check if structure is nested (per-theme) or flat (legacy)
$isNested = isset($allThemesColors['default']) || isset($allThemesColors['konstruksi']);

if ($isNested) {
    // New nested structure: {default: {...}, konstruksi: {...}}
    $themeColors = $allThemesColors[$activeTheme] ?? $allThemesColors['default'] ?? [];
} else {
    // Legacy flat structure: {...}
    $themeColors = $allThemesColors;
}

$errors = [];
$notices = [];

// Available themes (based on existing theme folders)
$availableThemes = [
    'default' => [
        'name' => 'Default',
        'description' => 'Tema default dengan desain modern dan profesional',
        'preview' => '/themes/default/theme-preview.jpg',
    ],
    'konstruksi' => [
        'name' => 'Konstruksi',
        'description' => 'Tema konstruksi dengan grid pattern, garis geometris, dan elemen blueprint',
        'preview' => '/themes/konstruksi/theme-preview.jpg',
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check which form was submitted
    $formType = $_POST['form_type'] ?? 'theme'; // 'theme' or 'colors'
    $newTheme = trim($_POST['active_theme'] ?? '');
    $colorUpdates = [];
    
    // Validate theme
    if ($newTheme === '' || !array_key_exists($newTheme, $availableThemes)) {
        $errors[] = 'Tema yang dipilih tidak valid.';
    }
    
    // Process color updates (only if colors form was submitted)
    if ($formType === 'colors') {
        $colorFields = [
            'primary', 'primaryLight', 'primaryDark', 'secondary', 'accent', 'accentLight',
            'background', 'backgroundDark', 'backgroundLight', 'backgroundMedium',
            'textPrimary', 'textSecondary', 'textTertiary', 'textMuted',
            'border', 'borderLight', 'overlay', 'overlayHover',
        ];
        
        foreach ($colorFields as $field) {
            $value = trim($_POST['color_' . $field] ?? '');
            if ($value !== '') {
                // Validate hex color or rgba
                if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value) || 
                    preg_match('/^rgba?\([\d\s,\.]+\)$/', $value)) {
                    $colorUpdates[$field] = $value;
                } else {
                    $errors[] = "Warna untuk {$field} tidak valid. Gunakan format hex (#ffffff) atau rgba(r, g, b, a).";
                }
            }
        }
    }
    
    if ($errors === []) {
        // Ensure nested structure exists
        if (!$isNested) {
            // Convert legacy flat structure to nested
            // Use existing colors for default theme, copy to konstruksi as fallback
            $allThemesColors = [
                'default' => $themeColors,
                'konstruksi' => $themeColors, // Copy existing colors to konstruksi as fallback
            ];
        }
        
        // If theme form was submitted, update active theme (colors are preserved)
        // If colors form was submitted, update colors for the theme specified in hidden input
        if ($formType === 'theme') {
            // Only update active theme, preserve all theme colors
            // Don't update colors, just the active theme
            // $allThemesColors stays the same
        } elseif ($formType === 'colors') {
            // Update colors for the theme specified in hidden input (active_theme from colors form)
            // This is the theme that was active when colors form was displayed
            // The hidden input contains the active theme when the form was shown
            $themeToUpdateColors = $newTheme; // This comes from hidden input in colors form
            
            // Ensure this theme exists in the nested structure
            if (!isset($allThemesColors[$themeToUpdateColors])) {
                // If theme doesn't exist, initialize with empty array (will use defaults)
                $allThemesColors[$themeToUpdateColors] = [];
            }
            
            // Merge color updates with existing colors for this theme
            $currentThemeColors = $allThemesColors[$themeToUpdateColors] ?? [];
            $updatedThemeColors = array_merge($currentThemeColors, $colorUpdates);
            
            // Update nested structure: preserve other themes' colors, update only this theme
            $allThemesColors[$themeToUpdateColors] = $updatedThemeColors;
        }
        
        // Ensure all themes exist in structure
        if (!isset($allThemesColors['default'])) {
            $allThemesColors['default'] = [];
        }
        if (!isset($allThemesColors['konstruksi'])) {
            $allThemesColors['konstruksi'] = [];
        }
        
        $updateStmt = $pdo->prepare(
            'UPDATE theme_settings
             SET active_theme = :theme,
                 theme_colors = :colors,
                 updated_by = :updated_by
             WHERE id = :id'
        );
        
        // Determine which theme to set as active
        // If theme form was submitted, use $newTheme (from radio button)
        // If colors form was submitted, keep current active theme (don't change active theme when saving colors)
        $themeToSetAsActive = ($formType === 'theme') ? $newTheme : $activeTheme;
        
        $updateStmt->execute([
            'theme' => $themeToSetAsActive,
            'colors' => json_encode($allThemesColors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updated_by' => $currentUserId,
            'id' => $settingsId,
        ]);
        
        // After saving, redirect with appropriate message
        if ($formType === 'theme') {
            admin_redirect('theme-settings', ['notice' => 'theme_updated']);
        } else {
            admin_redirect('theme-settings', ['notice' => 'colors_updated']);
        }
    }
}

if (isset($_GET['notice'])) {
    $notice = $_GET['notice'];
    if ($notice === 'theme_updated') {
        $notices[] = ['type' => 'success', 'message' => 'Tema berhasil diubah. Warna tema yang ditampilkan di bawah adalah warna untuk tema yang baru dipilih.'];
    } elseif ($notice === 'colors_updated') {
        $notices[] = ['type' => 'success', 'message' => 'Warna tema berhasil diperbarui. Perubahan akan langsung diterapkan di frontend.'];
    } elseif ($notice === 'updated') {
        // Legacy support
        $notices[] = ['type' => 'success', 'message' => 'Pengaturan tema berhasil disimpan.'];
    }
}

// Reload settings after potential update
$settings = fetchThemeSettings($pdo);
$activeTheme = $settings['active_theme'] ?? 'default';
$allThemesColors = json_decode($settings['theme_colors'] ?? '{}', true) ?: [];

// Check if structure is nested (per-theme) or flat (legacy)
$isNested = isset($allThemesColors['default']) || isset($allThemesColors['konstruksi']);

if ($isNested) {
    // New nested structure: {default: {...}, konstruksi: {...}}
    $themeColors = $allThemesColors[$activeTheme] ?? $allThemesColors['default'] ?? [];
} else {
    // Legacy flat structure: {...}
    $themeColors = $allThemesColors;
}

$updatedAtLabel = 'Belum pernah';
try {
    if (!empty($settings['updated_at'])) {
        $updatedAtLabel = (new DateTime((string) $settings['updated_at']))->format('d M Y H:i');
    }
} catch (Throwable $throwable) {
    $updatedAtLabel = (string) ($settings['updated_at'] ?? 'Belum pernah');
}

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Pengaturan Tema | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
        <style>
            .theme-preview {
                border-radius: var(--radius-lg);
                border: 1px solid rgba(148, 163, 184, 0.14);
                background: var(--bg-card);
                padding: 24px;
                margin-top: 20px;
            }
            
            .theme-preview__header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 20px;
            }
            
            .theme-preview__title {
                margin: 0;
                font-size: 1.1rem;
                color: var(--text-primary);
            }
            
            .theme-preview__content {
                border-radius: 12px;
                padding: 24px;
                position: relative;
                overflow: hidden;
            }
            
            .theme-preview__demo {
                display: grid;
                gap: 16px;
            }
            
            .theme-preview__section {
                padding: 16px;
                border-radius: 8px;
                border: 1px solid;
            }
            
            .theme-preview__button {
                display: inline-block;
                padding: 10px 20px;
                border-radius: 8px;
                font-weight: 600;
                text-decoration: none;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            
            .theme-preview__button:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            }
            
            .color-picker-wrapper {
                display: flex;
                gap: 12px;
                align-items: flex-start;
            }
            
            .color-picker-wrapper input[type="color"] {
                width: 50px;
                height: 50px;
                border: 2px solid rgba(148, 163, 184, 0.22);
                border-radius: 12px;
                cursor: pointer;
                padding: 2px;
                background: transparent;
                flex-shrink: 0;
            }
            
            .color-picker-wrapper input[type="color"]::-webkit-color-swatch-wrapper {
                padding: 0;
            }
            
            .color-picker-wrapper input[type="color"]::-webkit-color-swatch {
                border: none;
                border-radius: 8px;
            }
            
            .color-picker-wrapper input[type="text"] {
                flex: 1;
            }
            
            .theme-selector-wrapper {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 24px;
                margin-top: 20px;
            }
            
            .theme-selector-panel {
                display: flex;
                flex-direction: column;
            }
            
            .theme-preview-panel {
                display: flex;
                flex-direction: column;
            }
            
            .theme-selector {
                display: flex;
                flex-direction: column;
                gap: 16px;
                margin-bottom: 24px;
            }
            
            .theme-preview-frame {
                border-radius: var(--radius-lg);
                border: 1px solid rgba(148, 163, 184, 0.14);
                background: var(--bg-card);
                overflow: hidden;
                display: flex;
                flex-direction: column;
                height: 100%;
                min-height: 600px;
            }
            
            .theme-preview-frame__header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px 20px;
                border-bottom: 1px solid rgba(148, 163, 184, 0.14);
                background: var(--bg-panel);
            }
            
            .theme-preview-frame__title {
                margin: 0;
                font-size: 0.95rem;
                font-weight: 600;
                color: var(--text-primary);
            }
            
            .theme-preview-frame__controls {
                display: flex;
                gap: 8px;
                align-items: center;
            }
            
            .theme-preview-frame__content {
                flex: 1;
                position: relative;
                background: #f5f5f5;
                overflow: hidden;
                min-height: 600px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .theme-preview-image {
                width: 100%;
                height: auto;
                max-height: 100%;
                object-fit: contain;
                display: block;
            }
            
            @media (max-width: 1024px) {
                .theme-selector-wrapper {
                    grid-template-columns: 1fr;
                }
                
                .theme-preview-frame {
                    min-height: 500px;
                }
                
                .theme-preview-frame__content {
                    min-height: 500px;
                }
            }
            
            .theme-option {
                border: 2px solid rgba(148, 163, 184, 0.22);
                border-radius: 12px;
                padding: 20px;
                cursor: pointer;
                transition: all 0.2s ease;
                background: var(--bg-card);
                position: relative;
            }
            
            .theme-option:hover {
                border-color: rgba(126, 252, 43, 0.4);
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            }
            
            .theme-option input[type="radio"] {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }
            
            .theme-option input[type="radio"]:checked + .theme-option__content {
                border-color: rgba(126, 252, 43, 0.6);
            }
            
            .theme-option__content {
                border: 2px solid transparent;
                border-radius: 8px;
                padding: 12px;
                transition: border-color 0.2s ease;
            }
            
            .theme-option input[type="radio"]:checked ~ .theme-option__content {
                border-color: rgba(126, 252, 43, 0.6);
            }
            
            .theme-option__name {
                font-weight: 600;
                margin: 0 0 8px;
                color: var(--text-primary);
            }
            
            .theme-option__description {
                font-size: 0.85rem;
                color: var(--text-muted);
                margin: 0;
            }
            
            .form-section-title {
                font-size: 1rem;
                font-weight: 600;
                color: var(--text-primary);
                margin: 24px 0 16px;
                padding-bottom: 12px;
                border-bottom: 1px solid rgba(148, 163, 184, 0.14);
            }
            
            .form-section-title:first-of-type {
                margin-top: 0;
            }
            
            .form-section-description {
                font-size: 0.85rem;
                color: var(--text-muted);
                margin: -8px 0 16px;
            }
        </style>
        <script src="app.js" defer></script>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'theme-settings';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Pengaturan Tema',
                    'pageDescription' => 'Kelola tema aktif dan kustomisasi warna untuk frontend website.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Pengaturan Tema'],
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
                <section class="workspace-meta">
                    <span class="status-pill">Terakhir update: <?= htmlspecialchars($updatedAtLabel, ENT_QUOTES) ?></span>
                    <span class="chip chip--success">Tema aktif: <?= htmlspecialchars($availableThemes[$activeTheme]['name'] ?? $activeTheme, ENT_QUOTES) ?></span>
                </section>
                
                <section class="card">
                    <header>
                        <h2>Pilih Tema</h2>
                        <p class="card-subtitle">Setiap tema memiliki layout dan struktur view yang berbeda, namun menggunakan data yang sama.</p>
                    </header>
                    <div class="theme-selector-wrapper">
                        <div class="theme-selector-panel">
                    <form method="post" class="form-grid form-settings" id="theme-form">
                        <input type="hidden" name="form_type" value="theme">
                        <div class="theme-selector">
                            <?php foreach ($availableThemes as $themeKey => $themeInfo): ?>
                                <label class="theme-option">
                                    <input type="radio" name="active_theme" value="<?= htmlspecialchars($themeKey, ENT_QUOTES) ?>" <?= $activeTheme === $themeKey ? 'checked' : '' ?> required data-theme-preview="<?= htmlspecialchars($themeKey, ENT_QUOTES) ?>">
                                            <div class="theme-option__content">
                                                <h3 class="theme-option__name"><?= htmlspecialchars($themeInfo['name'], ENT_QUOTES) ?></h3>
                                                <p class="theme-option__description"><?= htmlspecialchars($themeInfo['description'], ENT_QUOTES) ?></p>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">Simpan Tema</button>
                                </div>
                            </form>
                        </div>
                        <div class="theme-preview-panel">
                            <div class="theme-preview-frame">
                                <div class="theme-preview-frame__header">
                                    <h3 class="theme-preview-frame__title">Preview Homepage</h3>
                                    <div class="theme-preview-frame__controls">
                                        <a href="/?theme_preview=<?= htmlspecialchars($activeTheme, ENT_QUOTES) ?>" target="_blank" class="btn btn-sm btn-outline" title="Lihat di Tab Baru" id="preview-link">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M6 2H4C2.89543 2 2 2.89543 2 4V12C2 13.1046 2.89543 14 4 14H12C13.1046 14 14 13.1046 14 12V10M10 2H14M14 2V6M14 2L6 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                                <div class="theme-preview-frame__content">
                                    <img id="theme-preview-image" 
                                         src="/themes/<?= htmlspecialchars($activeTheme, ENT_QUOTES) ?>/theme-preview.jpg" 
                                         alt="Preview Tema <?= htmlspecialchars($availableThemes[$activeTheme]['name'] ?? $activeTheme, ENT_QUOTES) ?>"
                                         class="theme-preview-image">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                
                <section class="card">
                    <header>
                        <h2>Kustomisasi Warna</h2>
                        <p class="card-subtitle">Sesuaikan warna tema sesuai brand identity. Perubahan akan langsung diterapkan di frontend.</p>
                    </header>
                    <form method="post" class="form-grid form-settings" id="colors-form">
                        <input type="hidden" name="form_type" value="colors">
                        <input type="hidden" name="active_theme" id="colors-form-active-theme" value="<?= htmlspecialchars($activeTheme, ENT_QUOTES) ?>">
                        
                        <h3 class="form-section-title">Warna Primer</h3>
                        <span class="form-section-description">Warna utama yang digunakan untuk tombol, link, dan elemen interaktif utama.</span>
                        
                        <label class="form-field">
                            <span>Primary</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_primary" value="<?= htmlspecialchars(colorToHex($themeColors['primary'] ?? '#ff6600'), ENT_QUOTES) ?>">
                                <input type="text" name="color_primary" id="color_primary" value="<?= htmlspecialchars($themeColors['primary'] ?? '#ff6600', ENT_QUOTES) ?>" placeholder="#ff6600" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$|^rgba?\([\d\s,\.]+\)$">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Primary Light</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_primaryLight" value="<?= htmlspecialchars(colorToHex($themeColors['primaryLight'] ?? '#ff7a00'), ENT_QUOTES) ?>">
                                <input type="text" name="color_primaryLight" id="color_primaryLight" value="<?= htmlspecialchars($themeColors['primaryLight'] ?? '#ff7a00', ENT_QUOTES) ?>" placeholder="#ff7a00">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Primary Dark</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_primaryDark" value="<?= htmlspecialchars(colorToHex($themeColors['primaryDark'] ?? '#ff6d00'), ENT_QUOTES) ?>">
                                <input type="text" name="color_primaryDark" id="color_primaryDark" value="<?= htmlspecialchars($themeColors['primaryDark'] ?? '#ff6d00', ENT_QUOTES) ?>" placeholder="#ff6d00">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Secondary</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_secondary" value="<?= htmlspecialchars(colorToHex($themeColors['secondary'] ?? '#ffaa00'), ENT_QUOTES) ?>">
                                <input type="text" name="color_secondary" id="color_secondary" value="<?= htmlspecialchars($themeColors['secondary'] ?? '#ffaa00', ENT_QUOTES) ?>" placeholder="#ffaa00">
                            </div>
                        </label>
                        
                        <h3 class="form-section-title">Warna Accent</h3>
                        <span class="form-section-description">Warna tambahan untuk highlight dan elemen dekoratif.</span>
                        
                        <label class="form-field">
                            <span>Accent</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_accent" value="<?= htmlspecialchars(colorToHex($themeColors['accent'] ?? '#ffb300'), ENT_QUOTES) ?>">
                                <input type="text" name="color_accent" id="color_accent" value="<?= htmlspecialchars($themeColors['accent'] ?? '#ffb300', ENT_QUOTES) ?>" placeholder="#ffb300">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Accent Light</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_accentLight" value="<?= htmlspecialchars(colorToHex($themeColors['accentLight'] ?? '#ffd46b'), ENT_QUOTES) ?>">
                                <input type="text" name="color_accentLight" id="color_accentLight" value="<?= htmlspecialchars($themeColors['accentLight'] ?? '#ffd46b', ENT_QUOTES) ?>" placeholder="#ffd46b">
                            </div>
                        </label>
                        
                        <h3 class="form-section-title">Background</h3>
                        <span class="form-section-description">Warna latar belakang untuk berbagai elemen dan section.</span>
                        
                        <label class="form-field">
                            <span>Background</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_background" value="<?= htmlspecialchars(colorToHex($themeColors['background'] ?? '#000000'), ENT_QUOTES) ?>">
                                <input type="text" name="color_background" id="color_background" value="<?= htmlspecialchars($themeColors['background'] ?? '#000000', ENT_QUOTES) ?>" placeholder="#000000">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Background Dark</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_backgroundDark" value="<?= htmlspecialchars(colorToHex($themeColors['backgroundDark'] ?? '#050505'), ENT_QUOTES) ?>">
                                <input type="text" name="color_backgroundDark" id="color_backgroundDark" value="<?= htmlspecialchars($themeColors['backgroundDark'] ?? '#050505', ENT_QUOTES) ?>" placeholder="#050505">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Background Light</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_backgroundLight" value="<?= htmlspecialchars(colorToHex($themeColors['backgroundLight'] ?? '#222222'), ENT_QUOTES) ?>">
                                <input type="text" name="color_backgroundLight" id="color_backgroundLight" value="<?= htmlspecialchars($themeColors['backgroundLight'] ?? '#222222', ENT_QUOTES) ?>" placeholder="#222222">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Background Medium</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_backgroundMedium" value="<?= htmlspecialchars(colorToHex($themeColors['backgroundMedium'] ?? '#333333'), ENT_QUOTES) ?>">
                                <input type="text" name="color_backgroundMedium" id="color_backgroundMedium" value="<?= htmlspecialchars($themeColors['backgroundMedium'] ?? '#333333', ENT_QUOTES) ?>" placeholder="#333333">
                            </div>
                        </label>
                        
                        <h3 class="form-section-title">Text Colors</h3>
                        <span class="form-section-description">Warna teks untuk berbagai level hierarki konten.</span>
                        
                        <label class="form-field">
                            <span>Text Primary</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_textPrimary" value="<?= htmlspecialchars(colorToHex($themeColors['textPrimary'] ?? '#ffffff'), ENT_QUOTES) ?>">
                                <input type="text" name="color_textPrimary" id="color_textPrimary" value="<?= htmlspecialchars($themeColors['textPrimary'] ?? '#ffffff', ENT_QUOTES) ?>" placeholder="#ffffff">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Text Secondary</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_textSecondary" value="<?= htmlspecialchars(colorToHex($themeColors['textSecondary'] ?? '#e6e6e6'), ENT_QUOTES) ?>">
                                <input type="text" name="color_textSecondary" id="color_textSecondary" value="<?= htmlspecialchars($themeColors['textSecondary'] ?? '#e6e6e6', ENT_QUOTES) ?>" placeholder="#e6e6e6">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Text Tertiary</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_textTertiary" value="<?= htmlspecialchars(colorToHex($themeColors['textTertiary'] ?? '#dddddd'), ENT_QUOTES) ?>">
                                <input type="text" name="color_textTertiary" id="color_textTertiary" value="<?= htmlspecialchars($themeColors['textTertiary'] ?? '#dddddd', ENT_QUOTES) ?>" placeholder="#dddddd">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Text Muted</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_textMuted" value="<?= htmlspecialchars(colorToHex($themeColors['textMuted'] ?? '#999999'), ENT_QUOTES) ?>">
                                <input type="text" name="color_textMuted" id="color_textMuted" value="<?= htmlspecialchars($themeColors['textMuted'] ?? '#999999', ENT_QUOTES) ?>" placeholder="#999999">
                            </div>
                        </label>
                        
                        <h3 class="form-section-title">Border & Overlay</h3>
                        <span class="form-section-description">Warna border dan overlay untuk elemen UI.</span>
                        
                        <label class="form-field">
                            <span>Border</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_border" value="<?= htmlspecialchars(colorToHex($themeColors['border'] ?? 'rgba(255, 255, 255, 0.14)'), ENT_QUOTES) ?>">
                                <input type="text" name="color_border" id="color_border" value="<?= htmlspecialchars($themeColors['border'] ?? 'rgba(255, 255, 255, 0.14)', ENT_QUOTES) ?>" placeholder="rgba(255, 255, 255, 0.14)">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Border Light</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_borderLight" value="<?= htmlspecialchars(colorToHex($themeColors['borderLight'] ?? 'rgba(255, 255, 255, 0.08)'), ENT_QUOTES) ?>">
                                <input type="text" name="color_borderLight" id="color_borderLight" value="<?= htmlspecialchars($themeColors['borderLight'] ?? 'rgba(255, 255, 255, 0.08)', ENT_QUOTES) ?>" placeholder="rgba(255, 255, 255, 0.08)">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Overlay</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_overlay" value="<?= htmlspecialchars(colorToHex($themeColors['overlay'] ?? 'rgba(255, 255, 255, 0.06)'), ENT_QUOTES) ?>">
                                <input type="text" name="color_overlay" id="color_overlay" value="<?= htmlspecialchars($themeColors['overlay'] ?? 'rgba(255, 255, 255, 0.06)', ENT_QUOTES) ?>" placeholder="rgba(255, 255, 255, 0.06)">
                            </div>
                        </label>
                        <label class="form-field">
                            <span>Overlay Hover</span>
                            <div class="color-picker-wrapper">
                                <input type="color" id="color_picker_overlayHover" value="<?= htmlspecialchars(colorToHex($themeColors['overlayHover'] ?? 'rgba(255, 255, 255, 0.12)'), ENT_QUOTES) ?>">
                                <input type="text" name="color_overlayHover" id="color_overlayHover" value="<?= htmlspecialchars($themeColors['overlayHover'] ?? 'rgba(255, 255, 255, 0.12)', ENT_QUOTES) ?>" placeholder="rgba(255, 255, 255, 0.12)">
                            </div>
                        </label>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Simpan Warna</button>
                            <button type="button" class="btn btn-outline" onclick="resetColors()">Reset ke Default</button>
                        </div>
                    </form>
                </section>
                
                <section class="card">
                    <div class="theme-preview">
                        <div class="theme-preview__header">
                            <h3 class="theme-preview__title">Preview Tema</h3>
                            <span class="chip chip--success">Live Preview</span>
                        </div>
                        <div class="theme-preview__content" id="theme-preview" style="background: <?= htmlspecialchars($themeColors['background'] ?? '#000000', ENT_QUOTES) ?>;">
                            <div class="theme-preview__demo">
                                <div class="theme-preview__section" style="background: <?= htmlspecialchars($themeColors['backgroundLight'] ?? '#222222', ENT_QUOTES) ?>; border-color: <?= htmlspecialchars($themeColors['border'] ?? 'rgba(255, 255, 255, 0.14)', ENT_QUOTES) ?>;">
                                    <h4 style="color: <?= htmlspecialchars($themeColors['textPrimary'] ?? '#ffffff', ENT_QUOTES) ?>; margin: 0 0 8px;">Heading Example</h4>
                                    <p style="color: <?= htmlspecialchars($themeColors['textSecondary'] ?? '#e6e6e6', ENT_QUOTES) ?>; margin: 0 0 12px;">Ini adalah contoh teks secondary yang menunjukkan bagaimana warna teks terlihat pada tema ini.</p>
                                    <a href="#" class="theme-preview__button" style="background: <?= htmlspecialchars($themeColors['primary'] ?? '#ff6600', ENT_QUOTES) ?>; color: <?= htmlspecialchars($themeColors['textPrimary'] ?? '#ffffff', ENT_QUOTES) ?>;">Primary Button</a>
                                    <a href="#" class="theme-preview__button" style="background: <?= htmlspecialchars($themeColors['secondary'] ?? '#ffaa00', ENT_QUOTES) ?>; color: <?= htmlspecialchars($themeColors['textPrimary'] ?? '#ffffff', ENT_QUOTES) ?>; margin-left: 12px;">Secondary Button</a>
                                </div>
                                <div class="theme-preview__section" style="background: <?= htmlspecialchars($themeColors['backgroundMedium'] ?? '#333333', ENT_QUOTES) ?>; border-color: <?= htmlspecialchars($themeColors['borderLight'] ?? 'rgba(255, 255, 255, 0.08)', ENT_QUOTES) ?>;">
                                    <p style="color: <?= htmlspecialchars($themeColors['textTertiary'] ?? '#dddddd', ENT_QUOTES) ?>; margin: 0 0 8px;">Tertiary text example</p>
                                    <p style="color: <?= htmlspecialchars($themeColors['textMuted'] ?? '#999999', ENT_QUOTES) ?>; margin: 0; font-size: 0.9em;">Muted text untuk informasi tambahan atau metadata.</p>
                                </div>
                            </div>
                        </div>
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
            
            // Embed theme colors data from PHP to JavaScript (must be before DOMContentLoaded)
            const themeColorsData = <?= json_encode($allThemesColors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const currentActiveTheme = <?= json_encode($activeTheme, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            
            // Default colors per theme (used if colors are not set in database)
            const defaultColorsByTheme = {
                'default': {
                    'primary': '#ff6600',
                    'primaryLight': '#ff7a00',
                    'primaryDark': '#ff6d00',
                    'secondary': '#ffaa00',
                    'accent': '#ffd873',
                    'accentLight': '#ffe8b3',
                    'background': '#000000',
                    'backgroundDark': '#0a0a0a',
                    'backgroundLight': '#1a1a1a',
                    'backgroundMedium': '#333333',
                    'textPrimary': '#ffffff',
                    'textSecondary': '#e0e0e0',
                    'textTertiary': '#b0b0b0',
                    'textMuted': '#888888',
                    'border': '#333333',
                    'borderLight': '#555555',
                    'overlay': 'rgba(0, 0, 0, 0.8)',
                    'overlayHover': 'rgba(0, 0, 0, 0.9)',
                },
                'konstruksi': {
                    'primary': '#ff6600',
                    'primaryLight': '#ff8533',
                    'primaryDark': '#cc5200',
                    'secondary': '#0066cc',
                    'accent': '#4da6ff',
                    'accentLight': '#b3d9ff',
                    'background': '#ffffff',
                    'backgroundDark': '#f5f7fa',
                    'backgroundLight': '#fafbfc',
                    'backgroundMedium': '#e8ecf1',
                    'textPrimary': '#1a1f2e',
                    'textSecondary': '#4a5568',
                    'textTertiary': '#718096',
                    'textMuted': '#a0aec0',
                    'border': '#e2e8f0',
                    'borderLight': '#f1f5f9',
                    'overlay': 'rgba(26, 31, 46, 0.6)',
                    'overlayHover': 'rgba(26, 31, 46, 0.7)',
                },
            };
            
            // Function to get colors for a specific theme (merge database colors with defaults)
            function getThemeColors(theme) {
                // Check if structure is nested (per-theme) or flat (legacy)
                const isNested = themeColorsData && (themeColorsData['default'] !== undefined || themeColorsData['konstruksi'] !== undefined);
                
                let themeColors = {};
                if (isNested && themeColorsData) {
                    // New nested structure: {default: {...}, konstruksi: {...}}
                    themeColors = themeColorsData[theme] || themeColorsData['default'] || {};
                } else if (themeColorsData) {
                    // Legacy flat structure: {...}
                    themeColors = themeColorsData;
                }
                
                // Get default colors for this theme
                const defaults = defaultColorsByTheme[theme] || defaultColorsByTheme['default'];
                
                // Merge: database colors override defaults
                return Object.assign({}, defaults, themeColors);
            }
            
            // Function to update color form inputs with colors from a specific theme
            function updateColorFormWithTheme(theme) {
                const colors = getThemeColors(theme);
                
                // Color fields mapping
                const colorFields = [
                    'primary', 'primaryLight', 'primaryDark', 'secondary', 'accent', 'accentLight',
                    'background', 'backgroundDark', 'backgroundLight', 'backgroundMedium',
                    'textPrimary', 'textSecondary', 'textTertiary', 'textMuted',
                    'border', 'borderLight', 'overlay', 'overlayHover',
                ];
                
                colorFields.forEach(field => {
                    const input = document.getElementById('color_' + field);
                    const picker = document.getElementById('color_picker_' + field);
                    const colorValue = colors[field] || '';
                    
                    if (input && colorValue) {
                        input.value = colorValue;
                        
                        // Update color picker
                        if (picker) {
                            if (/^#/.test(colorValue)) {
                                // Hex color
                                let hexValue = colorValue;
                                if (hexValue.length === 4) {
                                    // Convert #RGB to #RRGGBB
                                    hexValue = '#' + hexValue[1] + hexValue[1] + hexValue[2] + hexValue[2] + hexValue[3] + hexValue[3];
                                }
                                picker.value = hexValue;
                            } else if (/^rgba?/.test(colorValue)) {
                                // RGBA color - convert to hex for color picker
                                const matches = colorValue.match(/rgba?\(([\d\s,\.]+)\)/);
                                if (matches) {
                                    const values = matches[1].split(',').map(v => parseInt(v.trim()));
                                    if (values.length >= 3) {
                                        const r = values[0];
                                        const g = values[1];
                                        const b = values[2];
                                        const hexValue = '#' + [r, g, b].map(x => {
                                            const h = x.toString(16);
                                            return h.length === 1 ? '0' + h : h;
                                        }).join('');
                                        picker.value = hexValue;
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Update preview
                if (typeof updatePreview === 'function') {
                    updatePreview();
                }
                
                // Update hidden input for colors form
                const colorsFormActiveTheme = document.getElementById('colors-form-active-theme');
                if (colorsFormActiveTheme) {
                    colorsFormActiveTheme.value = theme;
                }
            }
            
            // Color picker synchronization
            const colorFields = [
                'primary', 'primaryLight', 'primaryDark', 'secondary', 'accent', 'accentLight',
                'background', 'backgroundDark', 'backgroundLight', 'backgroundMedium',
                'textPrimary', 'textSecondary', 'textTertiary', 'textMuted',
                'border', 'borderLight', 'overlay', 'overlayHover',
            ];
            
            colorFields.forEach(function(field) {
                const picker = document.getElementById('color_picker_' + field);
                const input = document.getElementById('color_' + field);
                
                if (picker && input) {
                    // Update text input when color picker changes
                    picker.addEventListener('input', function() {
                        input.value = picker.value;
                        updatePreview();
                    });
                    
                    // Update color picker when text input changes (for hex)
                    input.addEventListener('input', function() {
                        const value = input.value.trim();
                        if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(value)) {
                            // Convert 3-digit hex to 6-digit
                            let hex = value;
                            if (hex.length === 4) {
                                hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
                            }
                            picker.value = hex;
                            updatePreview();
                        } else if (/^rgba?\([\d\s,\.]+\)$/.test(value)) {
                            // Convert rgba to hex for color picker
                            const matches = value.match(/rgba?\(([\d\s,\.]+)\)/);
                            if (matches) {
                                const values = matches[1].split(',').map(v => parseInt(v.trim()));
                                const r = values[0];
                                const g = values[1];
                                const b = values[2];
                                const hex = '#' + [r, g, b].map(x => {
                                    const hex = x.toString(16);
                                    return hex.length === 1 ? '0' + hex : hex;
                                }).join('');
                                picker.value = hex;
                                updatePreview();
                            }
                        }
                    });
                }
            });
            
            // Update preview in real-time
            function updatePreview() {
                const preview = document.getElementById('theme-preview');
                if (!preview) return;
                
                const colors = {
                    background: document.getElementById('color_background')?.value || '#000000',
                    backgroundLight: document.getElementById('color_backgroundLight')?.value || '#222222',
                    backgroundMedium: document.getElementById('color_backgroundMedium')?.value || '#333333',
                    textPrimary: document.getElementById('color_textPrimary')?.value || '#ffffff',
                    textSecondary: document.getElementById('color_textSecondary')?.value || '#e6e6e6',
                    textTertiary: document.getElementById('color_textTertiary')?.value || '#dddddd',
                    textMuted: document.getElementById('color_textMuted')?.value || '#999999',
                    primary: document.getElementById('color_primary')?.value || '#ff6600',
                    secondary: document.getElementById('color_secondary')?.value || '#ffaa00',
                    border: document.getElementById('color_border')?.value || 'rgba(255, 255, 255, 0.14)',
                    borderLight: document.getElementById('color_borderLight')?.value || 'rgba(255, 255, 255, 0.08)',
                };
                
                // Update preview background
                preview.style.background = colors.background;
                
                // Update sections
                const sections = preview.querySelectorAll('.theme-preview__section');
                if (sections.length >= 2) {
                    sections[0].style.background = colors.backgroundLight;
                    sections[0].style.borderColor = colors.border;
                    sections[0].querySelector('h4').style.color = colors.textPrimary;
                    sections[0].querySelector('p').style.color = colors.textSecondary;
                    sections[0].querySelectorAll('.theme-preview__button')[0].style.background = colors.primary;
                    sections[0].querySelectorAll('.theme-preview__button')[0].style.color = colors.textPrimary;
                    if (sections[0].querySelectorAll('.theme-preview__button')[1]) {
                        sections[0].querySelectorAll('.theme-preview__button')[1].style.background = colors.secondary;
                        sections[0].querySelectorAll('.theme-preview__button')[1].style.color = colors.textPrimary;
                    }
                    
                    sections[1].style.background = colors.backgroundMedium;
                    sections[1].style.borderColor = colors.borderLight;
                    sections[1].querySelectorAll('p')[0].style.color = colors.textTertiary;
                    sections[1].querySelectorAll('p')[1].style.color = colors.textMuted;
                }
            }
            
            // Initialize preview update on page load
            document.addEventListener('DOMContentLoaded', function() {
                updatePreview();
                initializeThemePreview();
            });
            
            // Theme preview image management and color form sync
            function initializeThemePreview() {
                const themeRadios = document.querySelectorAll('input[name="active_theme"][type="radio"]');
                const previewImage = document.getElementById('theme-preview-image');
                const previewLink = document.getElementById('preview-link');
                
                if (!previewImage) return;
                
                // Update image when theme selection changes
                themeRadios.forEach(function(radio) {
                    radio.addEventListener('change', function() {
                        if (this.checked) {
                            const theme = this.value;
                            updateThemePreview(theme);
                            // Show warning that colors form needs to be reloaded
                            showThemeChangeWarning(theme);
                        }
                    });
                });
            }
            
            function updateThemePreview(theme) {
                const previewImage = document.getElementById('theme-preview-image');
                const previewLink = document.getElementById('preview-link');
                
                if (!previewImage) return;
                
                // Update image source with cache busting
                const imageUrl = '/themes/' + theme + '/theme-preview.jpg?' + new Date().getTime();
                previewImage.src = imageUrl;
                previewImage.alt = 'Preview Tema ' + theme;
                
                // Update preview link
                if (previewLink) {
                    previewLink.href = '/?theme_preview=' + encodeURIComponent(theme);
                }
            }
            
            // Update color form when theme changes
            function showThemeChangeWarning(theme) {
                // Update color form with colors from the new theme
                updateColorFormWithTheme(theme);
            }
            
            function resetColors() {
                const activeThemeInput = document.querySelector('input[name="active_theme"]:checked');
                const activeTheme = activeThemeInput ? activeThemeInput.value : 'default';
                
                // Default colors per theme
                const defaultColorsByTheme = {
                    'default': {
                        'color_primary': '#ff6600',
                        'color_primaryLight': '#ff7a00',
                        'color_primaryDark': '#ff6d00',
                        'color_secondary': '#ffaa00',
                        'color_accent': '#ffd873',
                        'color_accentLight': '#ffe8b3',
                        'color_background': '#000000',
                        'color_backgroundDark': '#0a0a0a',
                        'color_backgroundLight': '#1a1a1a',
                        'color_backgroundMedium': '#333333',
                        'color_textPrimary': '#ffffff',
                        'color_textSecondary': '#e0e0e0',
                        'color_textTertiary': '#b0b0b0',
                        'color_textMuted': '#888888',
                        'color_border': '#333333',
                        'color_borderLight': '#555555',
                        'color_overlay': 'rgba(0, 0, 0, 0.8)',
                        'color_overlayHover': 'rgba(0, 0, 0, 0.9)',
                    },
                    'konstruksi': {
                        'color_primary': '#ff6600',
                        'color_primaryLight': '#ff8533',
                        'color_primaryDark': '#cc5200',
                        'color_secondary': '#0066cc',
                        'color_accent': '#4da6ff',
                        'color_accentLight': '#b3d9ff',
                        'color_background': '#ffffff',
                        'color_backgroundDark': '#f5f7fa',
                        'color_backgroundLight': '#fafbfc',
                        'color_backgroundMedium': '#e8ecf1',
                        'color_textPrimary': '#1a1f2e',
                        'color_textSecondary': '#4a5568',
                        'color_textTertiary': '#718096',
                        'color_textMuted': '#a0aec0',
                        'color_border': '#e2e8f0',
                        'color_borderLight': '#f1f5f9',
                        'color_overlay': 'rgba(26, 31, 46, 0.6)',
                        'color_overlayHover': 'rgba(26, 31, 46, 0.7)',
                    },
                };
                
                const defaultColors = defaultColorsByTheme[activeTheme] || defaultColorsByTheme['default'];
                
                if (confirm('Yakin ingin reset semua warna ke default untuk tema ' + activeTheme + '? Perubahan yang belum disimpan akan hilang.')) {
                    Object.keys(defaultColors).forEach(key => {
                        const fieldName = key.replace('color_', '');
                        const input = document.getElementById('color_' + fieldName);
                        const picker = document.getElementById('color_picker_' + fieldName);
                        if (input) {
                            input.value = defaultColors[key];
                            if (picker) {
                                const colorValue = defaultColors[key];
                                if (/^#/.test(colorValue)) {
                                    let hexValue = colorValue;
                                    if (hexValue.length === 4) {
                                        hexValue = '#' + hexValue[1] + hexValue[1] + hexValue[2] + hexValue[2] + hexValue[3] + hexValue[3];
                                    }
                                    picker.value = hexValue;
                                } else if (/^rgba?/.test(colorValue)) {
                                    const matches = colorValue.match(/rgba?\(([\d\s,\.]+)\)/);
                                    if (matches) {
                                        const values = matches[1].split(',').map(v => parseInt(v.trim()));
                                        const r = values[0];
                                        const g = values[1];
                                        const b = values[2];
                                        const hexValue = '#' + [r, g, b].map(x => {
                                            const h = x.toString(16);
                                            return h.length === 1 ? '0' + h : h;
                                        }).join('');
                                        picker.value = hexValue;
                                    }
                                }
                            }
                        }
                    });
                    
                    updatePreview();
                }
            }
        </script>
    </body>
</html>
