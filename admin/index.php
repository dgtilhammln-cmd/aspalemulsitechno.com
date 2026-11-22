<?php

declare(strict_types=1);

define('ADMIN_FRONT_CONTROLLER', true);

require __DIR__ . '/bootstrap.php';

$path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/');

if ($path === '' || $path === 'admin') {
    require __DIR__ . '/dashboard.php';
    exit;
}

$base = __DIR__ . DIRECTORY_SEPARATOR;
$segments = explode('/', $path);

if ($segments[0] === 'admin') {
    array_shift($segments);
}

$resource = implode('/', $segments);

if ($resource === '') {
    require $base . 'dashboard.php';
    exit;
}

if (str_ends_with($resource, '.php')) {
    abort_admin();
}

$target = $base . $resource . '.php';

if (is_file($target)) {
    require $target;
    exit;
}

abort_admin();


