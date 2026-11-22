<?php
/**
 * Webhook Deploy Script
 * 
 * This script is called by webhook to automatically deploy updates from git.
 * It performs:
 * 1. Git pull from repository
 * 2. Clear OPcache (if enabled)
 * 3. Touch CSS files to update timestamps
 * 4. Log deployment activity
 * 
 * Usage:
 * - Set this as webhook URL in your git repository
 * - Make sure this file is accessible from web (or run via CLI)
 * - Configure secret token for security
 */

declare(strict_types=1);

// Security: Uncomment and set your secret token
// define('WEBHOOK_SECRET', 'your-secret-token-here');

// Check if secret token is provided (if enabled)
// if (defined('WEBHOOK_SECRET')) {
//     $token = $_GET['token'] ?? $_POST['token'] ?? '';
//     if ($token !== WEBHOOK_SECRET) {
//         http_response_code(403);
//         die('Forbidden: Invalid token');
//     }
// }

// Set execution time limit
set_time_limit(300); // 5 minutes

// Log file
$logFile = __DIR__ . '/deploy.log';
$log = function(string $message) use ($logFile): void {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
};

// Get repository root
$repoRoot = __DIR__;
chdir($repoRoot);

$log("=== Deployment Started ===");

// Step 1: Git Pull
$log("Step 1: Pulling from git repository...");

// Get current branch
exec('git rev-parse --abbrev-ref HEAD 2>&1', $branchOutput, $branchReturn);
$currentBranch = trim(implode("\n", $branchOutput)) ?: 'master';
$log("Current branch: $currentBranch");

// Set merge strategy untuk menghindari divergent branches error
exec('git config pull.rebase false 2>&1', $configOutput, $configReturn);
$log("Git config: pull.rebase = false");

// Fetch latest changes first
exec('git fetch origin 2>&1', $fetchOutput, $fetchReturn);
$log("Git fetch output: " . implode("\n", $fetchOutput));

// Check if branches are divergent
exec("git rev-list --left-right --count HEAD...origin/$currentBranch 2>&1", $divergentOutput, $divergentReturn);
$divergentCount = trim(implode("\n", $divergentOutput));
$log("Divergent branches check: $divergentCount");

// If branches are divergent (not "0	0"), reset hard to remote
if ($divergentCount !== '0	0' && strpos($divergentCount, '0') === false) {
    $log("WARNING: Branches are divergent. Resetting hard to remote (production mode)...");
    
    // Reset hard to remote (WARNING: This will discard local changes)
    exec("git reset --hard origin/$currentBranch 2>&1", $resetOutput, $resetReturn);
    $log("Git reset output: " . implode("\n", $resetOutput));
    
    if ($resetReturn !== 0) {
        $log("ERROR: Git reset failed");
        http_response_code(500);
        die("Git reset failed");
    }
    $log("Successfully reset to origin/$currentBranch");
} else {
    // Normal pull (no divergence)
    $output = [];
    $returnVar = 0;
    exec("git pull origin $currentBranch --no-rebase 2>&1", $output, $returnVar);
    $log("Git pull output: " . implode("\n", $output));
    
    if ($returnVar !== 0) {
        $log("WARNING: Git pull failed with return code: $returnVar");
        $log("Attempting force reset as fallback...");
        
        // Try force reset as fallback
        exec("git reset --hard origin/$currentBranch 2>&1", $resetOutput, $resetReturn);
        $log("Git reset output: " . implode("\n", $resetOutput));
        
        if ($resetReturn !== 0) {
            $log("ERROR: Git reset also failed");
            http_response_code(500);
            die("Git pull and reset failed");
        }
        $log("Successfully reset to origin/$currentBranch");
    }
}

// Step 2: Touch CSS files to update timestamps (if filemtime is still used)
$cssFiles = [
    __DIR__ . '/styles.css',
    __DIR__ . '/animations.css',
];

foreach ($cssFiles as $cssFile) {
    if (file_exists($cssFile)) {
        touch($cssFile);
        $log("Touched: $cssFile");
    }
}

// Step 3: Clear OPcache (if enabled)
if (function_exists('opcache_reset')) {
    opcache_reset();
    $log("OPcache cleared");
} else {
    $log("OPcache not available");
}

// Step 4: Verify CSS files exist and are readable
// Load asset_version function if not already loaded
if (!function_exists('asset_version')) {
    require __DIR__ . '/app/Support/helpers.php';
}

foreach ($cssFiles as $cssFile) {
    if (file_exists($cssFile)) {
        $size = filesize($cssFile);
        $mtime = filemtime($cssFile);
        $log("CSS file: $cssFile (size: $size bytes, mtime: $mtime)");
        
        // Calculate hash for verification using asset_version function
        $hash = asset_version($cssFile);
        $log("CSS hash: $hash");
        
        // Verify file is readable by web server
        if (!is_readable($cssFile)) {
            $log("WARNING: CSS file is not readable: $cssFile");
            chmod($cssFile, 0644);
            $log("Fixed permissions for: $cssFile");
        }
    } else {
        $log("WARNING: CSS file not found: $cssFile");
    }
}

// Step 5: Check git status
$log("Step 5: Checking git status...");
exec('git status --short 2>&1', $statusOutput, $statusReturn);
$log("Git status: " . implode("\n", $statusOutput));

// Step 6: Get latest commit info
exec('git log -1 --oneline 2>&1', $commitOutput, $commitReturn);
$log("Latest commit: " . implode("\n", $commitOutput));

$log("=== Deployment Completed ===");

// Return success response
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Deployment completed',
    'timestamp' => date('Y-m-d H:i:s'),
    'commit' => trim(implode("\n", $commitOutput)),
], JSON_PRETTY_PRINT);

