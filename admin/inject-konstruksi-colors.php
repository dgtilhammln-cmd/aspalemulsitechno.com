<?php

/**
 * Inject orange and blue colors for konstruksi theme
 * 
 * This script will update or insert theme colors for konstruksi theme
 * Orange: Primary color (safety orange)
 * Blue: Secondary/Accent color (construction blue)
 */

// Load database connection
require __DIR__ . '/db.php';

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

try {
    $pdo = getPDO();
    
    // Light theme color scheme for konstruksi theme (modern web jasa konstruksi)
    $konstruksiColors = [
        // Primary colors (Orange - Safety Orange, modern construction)
        'primary' => '#ff6600',        // Main orange - vibrant construction orange
        'primaryLight' => '#ff8533',   // Lighter orange - light construction orange
        'primaryDark' => '#cc5200',    // Darker orange - dark construction orange
        
        // Secondary/Accent colors (Blue - Professional Construction Blue)
        'secondary' => '#0066cc',      // Main blue - professional construction blue
        'accent' => '#4da6ff',         // Light blue accent - modern accent
        'accentLight' => '#b3d9ff',    // Very light blue - subtle accent
        
        // Background colors (Light theme - clean white/gray)
        'background' => '#ffffff',     // Pure white background
        'backgroundDark' => '#f5f7fa', // Light gray - subtle dark
        'backgroundLight' => '#fafbfc', // Very light gray - light background
        'backgroundMedium' => '#e8ecf1', // Medium gray - section backgrounds
        
        // Text colors (Dark text on light background)
        'textPrimary' => '#1a1f2e',    // Dark blue-gray - main text
        'textSecondary' => '#4a5568',  // Medium gray - secondary text
        'textTertiary' => '#718096',   // Light gray - tertiary text
        'textMuted' => '#a0aec0',      // Muted gray - muted text
        
        // Border colors (Light gray borders)
        'border' => '#e2e8f0',         // Light gray border
        'borderLight' => '#f1f5f9',    // Very light gray border
        
        // Overlay colors (Light overlay for modals/popups)
        'overlay' => 'rgba(26, 31, 46, 0.6)',      // Dark overlay (semi-transparent)
        'overlayHover' => 'rgba(26, 31, 46, 0.7)', // Darker overlay (hover)
    ];
    
    // Check if theme_settings exists
    $stmt = $pdo->query('SELECT id, active_theme, theme_colors FROM theme_settings ORDER BY id ASC LIMIT 1');
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Default colors for default theme (dark theme)
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
    
    if ($existing) {
        // Update existing theme_settings with nested structure (per-theme colors)
        $allThemesColors = json_decode($existing['theme_colors'] ?? '{}', true) ?: [];
        
        // Check if structure is nested (per-theme) or flat (legacy)
        $isNested = isset($allThemesColors['default']) || isset($allThemesColors['konstruksi']);
        
        if (!$isNested) {
            // Convert legacy flat structure to nested
            // Use existing colors for default theme, konstruksi colors for konstruksi theme
            $allThemesColors = [
                'default' => !empty($allThemesColors) ? $allThemesColors : $defaultThemeColors,
                'konstruksi' => $konstruksiColors,
            ];
        } else {
            // Update konstruksi theme colors, preserve default theme colors
            $allThemesColors['konstruksi'] = $konstruksiColors;
            
            // Ensure default theme exists
            if (!isset($allThemesColors['default'])) {
                $allThemesColors['default'] = $defaultThemeColors;
            }
        }
        
        $updateStmt = $pdo->prepare(
            'UPDATE theme_settings 
             SET theme_colors = :colors,
                 updated_at = NOW()
             WHERE id = :id'
        );
        
        $updateStmt->execute([
            'colors' => json_encode($allThemesColors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'id' => $existing['id'],
        ]);
        
        echo "✓ Updated theme_settings with konstruksi colors (Orange & Blue)\n";
        echo "  Theme ID: " . $existing['id'] . "\n";
        echo "  Active Theme: " . ($existing['active_theme'] ?? 'default') . "\n";
        echo "  Colors injected for konstruksi theme: " . count($konstruksiColors) . " colors\n";
        echo "  Default theme colors preserved: " . count($allThemesColors['default']) . " colors\n\n";
        
        // If active theme is not konstruksi, also show message
        if (($existing['active_theme'] ?? 'default') !== 'konstruksi') {
            echo "⚠ Active theme is '" . ($existing['active_theme'] ?? 'default') . "', not 'konstruksi'.\n";
            echo "  Konstruksi colors are saved separately and won't affect default theme.\n";
            echo "  To activate: Go to admin → Pengaturan Tema → Select 'konstruksi' → Save\n\n";
        }
    } else {
        // Insert new theme_settings with nested structure
        $nestedColors = [
            'default' => $defaultThemeColors,
            'konstruksi' => $konstruksiColors,
        ];
        
        $insertStmt = $pdo->prepare(
            'INSERT INTO theme_settings (active_theme, theme_colors) 
             VALUES (:theme, :colors)'
        );
        
        $insertStmt->execute([
            'theme' => 'default',
            'colors' => json_encode($nestedColors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        
        echo "✓ Created theme_settings with per-theme color structure\n";
        echo "  Active Theme: default\n";
        echo "  Default theme colors: " . count($defaultThemeColors) . " colors\n";
        echo "  Konstruksi theme colors: " . count($konstruksiColors) . " colors\n\n";
    }
    
    // Display color scheme
    echo "Color Scheme:\n";
    echo "=============\n";
    echo "Primary (Orange):\n";
    echo "  - Primary:      " . $konstruksiColors['primary'] . "\n";
    echo "  - Primary Light: " . $konstruksiColors['primaryLight'] . "\n";
    echo "  - Primary Dark:  " . $konstruksiColors['primaryDark'] . "\n";
    echo "\n";
    echo "Secondary (Blue):\n";
    echo "  - Secondary:     " . $konstruksiColors['secondary'] . "\n";
    echo "  - Accent:        " . $konstruksiColors['accent'] . "\n";
    echo "  - Accent Light:  " . $konstruksiColors['accentLight'] . "\n";
    echo "\n";
    echo "Background:\n";
    echo "  - Background:        " . $konstruksiColors['background'] . "\n";
    echo "  - Background Dark:   " . $konstruksiColors['backgroundDark'] . "\n";
    echo "  - Background Light:  " . $konstruksiColors['backgroundLight'] . "\n";
    echo "  - Background Medium: " . $konstruksiColors['backgroundMedium'] . "\n";
    echo "\n";
    
    echo "✓ Done! Colors are ready for konstruksi theme.\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Go to admin → Pengaturan Tema\n";
    echo "2. Select 'konstruksi' theme\n";
    echo "3. Click 'Simpan Tema'\n";
    echo "4. Colors will be applied automatically\n";
    
} catch (Throwable $throwable) {
    echo "✗ Error: " . $throwable->getMessage() . "\n";
    echo "  File: " . $throwable->getFile() . "\n";
    echo "  Line: " . $throwable->getLine() . "\n";
    exit(1);
}

