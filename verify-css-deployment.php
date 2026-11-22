<?php
/**
 * Verify CSS Deployment
 * 
 * This script verifies if CSS files are correctly deployed and updated.
 * It checks:
 * 1. File existence
 * 2. File permissions
 * 3. File content hash
 * 4. Git status
 * 5. Latest commit info
 * 
 * Usage:
 * - Access via browser: https://yourdomain.com/verify-css-deployment.php
 * - Or run via CLI: php verify-css-deployment.php
 */

declare(strict_types=1);

// Load helpers
require __DIR__ . '/app/Support/helpers.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Deployment Verification</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #1a1a1a;
            color: #fff;
        }
        h1 {
            color: #ff6600;
            border-bottom: 2px solid #ff6600;
            padding-bottom: 10px;
        }
        .section {
            background: #2a2a2a;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #ff6600;
        }
        .section h2 {
            color: #ffaa00;
            margin-top: 0;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
        }
        .status.success {
            background: #28a745;
            color: #fff;
        }
        .status.warning {
            background: #ffc107;
            color: #000;
        }
        .status.error {
            background: #dc3545;
            color: #fff;
        }
        .info {
            background: #333;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
            word-break: break-all;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        th {
            background: #333;
            color: #ff6600;
        }
        .hash {
            font-family: monospace;
            background: #1a1a1a;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <h1>🔍 CSS Deployment Verification</h1>
    
    <?php
    $cssFiles = [
        'styles.css' => __DIR__ . '/styles.css',
        'animations.css' => __DIR__ . '/animations.css',
    ];
    
    // Check file existence and hash
    echo '<div class="section">';
    echo '<h2>📁 File Status</h2>';
    echo '<table>';
    echo '<tr><th>File</th><th>Status</th><th>Size</th><th>Hash (Content)</th><th>Modified</th></tr>';
    
    foreach ($cssFiles as $name => $path) {
        $exists = file_exists($path);
        $readable = $exists && is_readable($path);
        $size = $exists ? filesize($path) : 0;
        $mtime = $exists ? filemtime($path) : 0;
        $hash = $exists ? asset_version($path) : null;
        
        $statusClass = $exists && $readable ? 'success' : 'error';
        $statusText = $exists && $readable ? 'OK' : ($exists ? 'Not Readable' : 'Not Found');
        
        echo '<tr>';
        echo '<td><strong>' . htmlspecialchars($name) . '</strong></td>';
        echo '<td><span class="status ' . $statusClass . '">' . $statusText . '</span></td>';
        echo '<td>' . ($exists ? number_format($size) . ' bytes' : '-') . '</td>';
        echo '<td><span class="hash">' . ($hash ?? 'N/A') . '</span></td>';
        echo '<td>' . ($exists ? date('Y-m-d H:i:s', $mtime) : '-') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
    
    // Check git status
    echo '<div class="section">';
    echo '<h2>🔀 Git Status</h2>';
    
    $repoRoot = __DIR__;
    chdir($repoRoot);
    
    // Get current branch
    exec('git rev-parse --abbrev-ref HEAD 2>&1', $branchOutput, $branchReturn);
    $currentBranch = trim(implode("\n", $branchOutput)) ?: 'unknown';
    echo '<div class="info"><strong>Current Branch:</strong> ' . htmlspecialchars($currentBranch) . '</div>';
    
    // Get git status
    exec('git status --short 2>&1', $statusOutput, $statusReturn);
    if (empty($statusOutput)) {
        echo '<div class="info"><span class="status success">Clean</span> No uncommitted changes</div>';
    } else {
        echo '<div class="info"><span class="status warning">Modified</span><br>';
        foreach ($statusOutput as $line) {
            echo htmlspecialchars($line) . '<br>';
        }
        echo '</div>';
    }
    echo '</div>';
    
    // Get latest commit
    echo '<div class="section">';
    echo '<h2>📝 Latest Commit</h2>';
    
    exec('git log -1 --oneline --date=short --format="%h - %s (%cd)" 2>&1', $commitOutput, $commitReturn);
    if (!empty($commitOutput)) {
        echo '<div class="info">' . htmlspecialchars(implode("\n", $commitOutput)) . '</div>';
    } else {
        echo '<div class="info"><span class="status error">Error</span> Could not get commit info</div>';
    }
    
    // Get commit hash for CSS files
    echo '<h3>CSS Files in Latest Commit</h3>';
    exec('git log -1 --name-only --pretty=format:"" -- styles.css animations.css 2>&1', $filesOutput, $filesReturn);
    if (!empty($filesOutput)) {
        echo '<div class="info">';
        foreach ($filesOutput as $line) {
            $line = trim($line);
            if (!empty($line)) {
                echo htmlspecialchars($line) . '<br>';
            }
        }
        echo '</div>';
    } else {
        echo '<div class="info"><span class="status warning">No CSS changes</span> in latest commit</div>';
    }
    echo '</div>';
    
    // Check OPcache
    echo '<div class="section">';
    echo '<h2>⚡ OPcache Status</h2>';
    
    if (function_exists('opcache_get_status')) {
        $opcacheStatus = opcache_get_status();
        if ($opcacheStatus && isset($opcacheStatus['opcache_enabled'])) {
            $enabled = $opcacheStatus['opcache_enabled'];
            echo '<div class="info">';
            echo '<span class="status ' . ($enabled ? 'success' : 'warning') . '">';
            echo $enabled ? 'Enabled' : 'Disabled';
            echo '</span>';
            if ($enabled) {
                echo '<br><strong>Cache Full:</strong> ' . ($opcacheStatus['cache_full'] ? 'Yes' : 'No');
                echo '<br><strong>Cached Scripts:</strong> ' . ($opcacheStatus['opcache_statistics']['num_cached_scripts'] ?? 0);
                echo '<br><strong>Memory Used:</strong> ' . number_format(($opcacheStatus['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 2) . ' MB';
            }
            echo '</div>';
        } else {
            echo '<div class="info"><span class="status warning">Status Unavailable</span></div>';
        }
    } else {
        echo '<div class="info"><span class="status warning">OPcache Not Available</span></div>';
    }
    echo '</div>';
    
    // Recommendations
    echo '<div class="section">';
    echo '<h2>💡 Recommendations</h2>';
    echo '<ul>';
    
    $hasIssues = false;
    foreach ($cssFiles as $name => $path) {
        if (!file_exists($path) || !is_readable($path)) {
            echo '<li><span class="status error">ERROR</span> File <strong>' . htmlspecialchars($name) . '</strong> is missing or not readable</li>';
            $hasIssues = true;
        }
    }
    
    if (!$hasIssues) {
        echo '<li><span class="status success">OK</span> All CSS files exist and are readable</li>';
    }
    
    if (function_exists('opcache_reset')) {
        echo '<li><span class="status warning">INFO</span> OPcache is available. Clear cache after deployment if needed.</li>';
    }
    
    echo '<li><span class="status warning">INFO</span> Check browser DevTools > Network to verify CSS hash in URL matches file hash above</li>';
    echo '</ul>';
    echo '</div>';
    ?>
    
    <div class="section">
        <h2>🔄 Next Steps</h2>
        <ol>
            <li>Verify CSS hash in URL matches file hash above</li>
            <li>If hash doesn't match, clear OPcache and reload</li>
            <li>Check browser cache (hard refresh: Ctrl+Shift+R)</li>
            <li>Verify webhook is calling the correct deployment script</li>
        </ol>
    </div>
</body>
</html>

