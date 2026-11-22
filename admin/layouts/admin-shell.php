<?php

declare(strict_types=1);

/**
 * Shared admin layout shell.
 *
 * Variables expected:
 *  - string $pageTitle
 *  - string|null $pageDescription
 *  - array $breadcrumbs
 *  - array $quickActions
 *  - callable $content (renders main content)
 *  - string $sidebarActive
 */

require_once __DIR__ . '/../bootstrap.php';

adminRequireAuth();

$pageTitle = $pageTitle ?? 'Dashboard';
$pageDescription = $pageDescription ?? null;
$breadcrumbs = $breadcrumbs ?? [];
$quickActions = $quickActions ?? [];
$sidebarActive = $sidebarActive ?? 'dashboard';
$content = $content ?? static function (): void {
};
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title><?= htmlspecialchars($pageTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> | Admin</title>
        <link rel="stylesheet" href="<?= admin_url('admin.css') ?>">
        <script src="<?= admin_url('app.js') ?>" defer></script>
    </head>
    <body class="dashboard">
        <?php
            require __DIR__ . '/../partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => $pageTitle,
                    'pageDescription' => $pageDescription,
                    'breadcrumbs' => $breadcrumbs,
                    'quickActions' => $quickActions,
                ]);
            ?>
            <div class="workspace-content">
                <?php $content(); ?>
            </div>
        </main>
    </body>
</html>

