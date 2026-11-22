<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adminLogout();
    admin_redirect('login');
}

admin_redirect('dashboard');


