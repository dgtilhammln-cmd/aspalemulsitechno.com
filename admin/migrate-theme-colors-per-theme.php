<?php

/**
 * Migration script to convert theme_colors from single object to nested object per theme
 * 
 * This script will:
 * 1. Convert existing theme_colors from {colors} to {default: {colors}, konstruksi: {colors}}
 * 2. Ensure each theme has its own color settings
 * 3. Preserve existing colors for the active theme
 */

declare(strict_types=1);

require __DIR__ . '/db.php';

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

try {
    $pdo = getPDO();
    
    echo "🔄 Migrating theme_colors to per-theme structure...\n\n";
    
    // Fetch existing theme_settings
    $stmt = $pdo->query('SELECT id, active_theme, theme_colors FROM theme_settings ORDER BY id ASC LIMIT 1');
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        echo "⚠ No theme_settings found. Creating default structure...\n";
        
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
        
        // Light theme colors for konstruksi theme
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
        
        // Create nested structure
        $nestedColors = [
            'default' => $defaultThemeColors,
            'konstruksi' => $konstruksiThemeColors,
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
        echo "  Konstruksi theme colors: " . count($konstruksiThemeColors) . " colors\n";
        
    } else {
        // Migrate existing data
        $existingColors = json_decode($existing['theme_colors'] ?? '{}', true) ?: [];
        $activeTheme = $existing['active_theme'] ?? 'default';
        
        echo "📦 Existing data found:\n";
        echo "  Active Theme: {$activeTheme}\n";
        echo "  Existing colors: " . count($existingColors) . " colors\n\n";
        
        // Check if already in nested structure
        $isNested = false;
        if (isset($existingColors['default']) || isset($existingColors['konstruksi'])) {
            $isNested = true;
            echo "✓ Already in nested structure. Skipping migration.\n";
        } else {
            echo "🔄 Converting to nested structure...\n";
            
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
            
            // Light theme colors for konstruksi theme
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
            
            // If existing colors exist, use them for the active theme
            if (!empty($existingColors)) {
                if ($activeTheme === 'konstruksi') {
                    // Merge existing colors with konstruksi defaults (existing takes priority)
                    $konstruksiThemeColors = array_merge($konstruksiThemeColors, $existingColors);
                    echo "  → Preserved existing colors for konstruksi theme\n";
                } else {
                    // Merge existing colors with default defaults (existing takes priority)
                    $defaultThemeColors = array_merge($defaultThemeColors, $existingColors);
                    echo "  → Preserved existing colors for default theme\n";
                }
            }
            
            // Create nested structure
            $nestedColors = [
                'default' => $defaultThemeColors,
                'konstruksi' => $konstruksiThemeColors,
            ];
            
            // Update database
            $updateStmt = $pdo->prepare(
                'UPDATE theme_settings 
                 SET theme_colors = :colors
                 WHERE id = :id'
            );
            
            $updateStmt->execute([
                'colors' => json_encode($nestedColors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'id' => $existing['id'],
            ]);
            
            echo "✓ Migration completed!\n";
            echo "  Default theme colors: " . count($defaultThemeColors) . " colors\n";
            echo "  Konstruksi theme colors: " . count($konstruksiThemeColors) . " colors\n";
        }
    }
    
    echo "\n✅ Migration complete! Each theme now has its own color settings.\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Test theme switching in admin → Pengaturan Tema\n";
    echo "2. Verify colors are different for each theme\n";
    echo "3. Colors should now be independent per theme\n";
    
} catch (Throwable $throwable) {
    echo "✗ Error: " . $throwable->getMessage() . "\n";
    echo "  File: " . $throwable->getFile() . "\n";
    echo "  Line: " . $throwable->getLine() . "\n";
    exit(1);
}

