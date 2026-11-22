<?php

declare(strict_types=1);

/**
 * MUNG CMS V1
 * File: app/bootstrap.php
 * Author: Firmanda Himawan
 * Description: Front-facing bootstrap that wires database access, autoloading, helpers, and global CMS identity.
 */

if (!defined('MUNG_CMS_NAME')) {
    define('MUNG_CMS_NAME', 'MUNG CMS V1');
}

if (!defined('MUNG_CMS_AUTHOR')) {
    define('MUNG_CMS_AUTHOR', 'Firmanda Himawan');
}

if (!defined('MUNG_CMS_GENERATOR')) {
    define('MUNG_CMS_GENERATOR', 'MUNG CMS V1 by Firmanda Himawan');
}

require_once __DIR__ . '/../admin/db.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;
    $prefixLength = strlen($prefix);

    if (strncmp($class, $prefix, $prefixLength) !== 0) {
        return;
    }

    $relativeClass = substr($class, $prefixLength);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

setlocale(LC_ALL, 'id_ID.UTF-8');

require_once __DIR__ . '/Support/helpers.php';

date_default_timezone_set('Asia/Jakarta');
