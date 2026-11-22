<?php

/**
 * Migration: Add meta_robots column to site_settings table
 * Run this once to add the meta_robots field to existing installations
 */

require_once __DIR__ . '/../bootstrap.php';

$pdo = getPDO();

try {
    // Check if column already exists
    $check = $pdo->query("SHOW COLUMNS FROM site_settings LIKE 'meta_robots'");
    if ($check->rowCount() > 0) {
        echo "Column meta_robots already exists. Migration skipped.\n";
        exit(0);
    }

    // Add the column
    $pdo->exec("
        ALTER TABLE site_settings 
        ADD COLUMN meta_robots VARCHAR(255) NULL 
        AFTER meta_keywords
    ");

    echo "Migration successful: meta_robots column added to site_settings table.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

