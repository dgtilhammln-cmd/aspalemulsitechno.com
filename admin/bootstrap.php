<?php

declare(strict_types=1);

/**
 * MUNG CMS V1
 * File: admin/bootstrap.php
 * Author: Firmanda Himawan
 * Description: Admin bootstrap, session wiring, helper utilities, and analytics/data access for the CMS dashboard.
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

if (!function_exists('adminIdentityMeta')) {
    function adminIdentityMeta(): string
    {
        $name = defined('MUNG_CMS_NAME') ? (string) MUNG_CMS_NAME : 'MUNG CMS V1';
        $author = defined('MUNG_CMS_AUTHOR') ? (string) MUNG_CMS_AUTHOR : 'Firmanda Himawan';
        $generator = defined('MUNG_CMS_GENERATOR') ? (string) MUNG_CMS_GENERATOR : ($name . ' by ' . $author);

        return sprintf(
            '<meta name="application-name" content="%s">' . PHP_EOL .
            '        <meta name="author" content="%s">' . PHP_EOL .
            '        <meta name="generator" content="%s">',
            htmlspecialchars($name, ENT_QUOTES),
            htmlspecialchars($author, ENT_QUOTES),
            htmlspecialchars($generator, ENT_QUOTES)
        );
    }
}

if (!function_exists('abort_admin')) {
    function abort_admin(int $status = 404, string $message = 'Halaman tidak ditemukan'): void
    {
        http_response_code($status);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>404</title><link rel="stylesheet" href="/styles.css"></head><body style="background:#0f0f0f;color:#fff;font-family:\'DM Sans\',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;"><div style="text-align:center;max-width:460px;padding:32px;"><h1 style="margin-bottom:16px;font-size:40px;">404</h1><p>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p><a href="/admin" style="display:inline-block;margin-top:18px;padding:10px 24px;border-radius:999px;background:#ff8a00;color:#0f0f0f;font-weight:600;text-decoration:none;">Kembali dashboard</a></div></body></html>';
        exit;
    }
}

if (defined('ADMIN_BOOTSTRAP_LOADED')) {
    return;
}

define('ADMIN_BOOTSTRAP_LOADED', true);

if (!isset($GLOBALS['_ADMIN_LAYOUT_STACK']) || !is_array($GLOBALS['_ADMIN_LAYOUT_STACK'])) {
    $GLOBALS['_ADMIN_LAYOUT_STACK'] = [];
}

require_once __DIR__ . '/db.php';

session_set_cookie_params([
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const ADMIN_SESSION_KEY = 'admin_user';

if (!function_exists('admin_url')) {
    function admin_url(string $path = '', array $query = []): string
    {
        $path = trim($path, '/');
        $url = '/admin' . ($path !== '' ? '/' . $path : '');

        if ($query !== []) {
            $queryString = http_build_query($query);
            if ($queryString !== '') {
                $url .= '?' . $queryString;
            }
        }

        return $url;
    }
}

if (!function_exists('admin_redirect')) {
    function admin_redirect(string $path = '', array $query = []): void
    {
        $url = admin_url($path, $query);
        header('Location: ' . $url);
        exit;
    }
}

if (!defined('ADMIN_FRONT_CONTROLLER')) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_ends_with(strtolower(parse_url((string) $requestUri, PHP_URL_PATH) ?? ''), '.php')) {
        abort_admin();
    }
}

function adminAttemptLogin(string $username, string $password): bool
{
    $username = trim($username);

    if ($username === '' || $password === '') {
        return false;
    }

    $user = adminFetchUserByUsername($username);

    if ($user === null) {
        return false;
    }

    if (!isset($user['password_hash']) || $user['password_hash'] === '') {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION[ADMIN_SESSION_KEY] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'name' => $user['full_name'] ?? $user['username'],
        'role' => $user['role_name'] ?? 'User',
        'role_id' => isset($user['role_id']) ? (int) $user['role_id'] : null,
        'role_slug' => $user['role_slug'] ?? null,
        'email' => $user['email'] ?? null,
        'login_at' => time(),
    ];

    session_regenerate_id(true);

    return true;
}

function adminIsAuthenticated(): bool
{
    return isset($_SESSION[ADMIN_SESSION_KEY]);
}

function adminRequireAuth(): void
{
    if (!adminIsAuthenticated()) {
        admin_redirect('login');
    }
}

function adminCurrentUser(): ?array
{
    return $_SESSION[ADMIN_SESSION_KEY] ?? null;
}

function adminRenderTopbar(?array $options = null): void
{
    $options ??= [];
    $brand = $options['brand'] ?? 'Aspal Emulsi';
    $subtitle = $options['subtitle'] ?? 'Control Center';
    $pageTitle = $options['pageTitle'] ?? null;
    $pageDescription = $options['pageDescription'] ?? null;
    $primaryNav = array_values(array_filter($options['primaryNav'] ?? [], static fn ($item): bool => ($item['visible'] ?? true)));
    $quickActions = array_values(array_filter($options['quickActions'] ?? [], static fn ($action): bool => ($action['visible'] ?? true)));
    $breadcrumbs = array_values(array_filter($options['breadcrumbs'] ?? [], static fn ($crumb): bool => ($crumb['label'] ?? '') !== ''));

    $substr = static function (string $value, int $start, int $length): string {
        if (function_exists('mb_substr')) {
            return mb_substr($value, $start, $length, 'UTF-8');
        }

        return substr($value, $start, $length);
    };

    foreach ($primaryNav as $index => $item) {
        $label = trim((string) ($item['label'] ?? ''));
        if (!isset($item['prefix']) || $item['prefix'] === '') {
            $primaryNav[$index]['prefix'] = $label === '' ? '' : strtoupper($substr($label, 0, 2));
        }
    }

    $currentUser = adminCurrentUser();
    $initials = '';

    if ($currentUser !== null) {
        $nameForInitial = $currentUser['name'] ?? $currentUser['username'] ?? '';
        $parts = preg_split('/\s+/', trim((string) $nameForInitial), -1, PREG_SPLIT_NO_EMPTY);
        if ($parts !== false && $parts !== []) {
            $initials = strtoupper($substr((string) $parts[0], 0, 1));
            if (count($parts) > 1) {
                $initials .= strtoupper($substr((string) end($parts), 0, 1));
            }
        } elseif (!empty($currentUser['username'])) {
            $initials = strtoupper($substr((string) $currentUser['username'], 0, 2));
        }
    }

    ?>
    <header class="workspace-header">
        <div class="workspace-header__top">
            <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-expanded="false" aria-label="Buka navigasi">
                <span></span>
            </button>
            <div class="workspace-branding">
                <span class="workspace-logo" aria-hidden="true">
                    <img src="/favicon.png" alt="Aspal Emulsi" class="workspace-logo-img">
                </span>
                <div class="workspace-branding__meta">
                    <span class="workspace-brand"><?= htmlspecialchars($brand, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                    <small class="workspace-subtitle"><?= htmlspecialchars($subtitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small>
                </div>
            </div>
            <?php if ($breadcrumbs !== []): ?>
                <nav class="workspace-breadcrumbs" aria-label="Breadcrumb">
                    <ol>
                        <?php foreach ($breadcrumbs as $crumb): ?>
                            <?php
                                $crumbLabel = htmlspecialchars((string) $crumb['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                $crumbHref = isset($crumb['href']) && $crumb['href'] !== '' ? (string) $crumb['href'] : null;
                            ?>
                            <li>
                                <?php if ($crumbHref !== null): ?>
                                    <a href="<?= htmlspecialchars($crumbHref, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= $crumbLabel ?></a>
                                <?php else: ?>
                                    <span><?= $crumbLabel ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>
            <div class="workspace-header__spacer"></div>
            <?php if ($quickActions !== []): ?>
                <div class="workspace-quick-actions">
                    <?php foreach ($quickActions as $action): ?>
                        <?php
                            $actionLabel = htmlspecialchars((string) ($action['label'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            if ($actionLabel === '') {
                                continue;
                            }
                            $actionHref = htmlspecialchars((string) ($action['href'] ?? '#'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                            $variant = (string) ($action['variant'] ?? 'outline');
                            $supportedVariants = ['primary', 'secondary', 'ghost', 'outline'];
                            if (!in_array($variant, $supportedVariants, true)) {
                                $variant = 'outline';
                            }
                            $classes = 'btn btn-' . $variant;
                        ?>
                        <a class="<?= $classes ?>" href="<?= $actionHref ?>">
                            <?= $actionLabel ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($currentUser !== null): ?>
                <div class="workspace-profile" data-user-menu>
                    <button class="workspace-profile__button" type="button" data-user-menu-toggle aria-haspopup="true" aria-expanded="false">
                        <span class="workspace-profile__avatar"><?= htmlspecialchars($initials, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                        <div class="workspace-profile__details">
                            <strong><?= htmlspecialchars($currentUser['name'] ?? $currentUser['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($currentUser['role'] ?? 'Admin', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small>
                        </div>
                        <svg class="workspace-profile__chevron" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" fill-rule="evenodd" d="M8 11.2a.8.8 0 0 1-.57-.24l-4-4a.8.8 0 1 1 1.14-1.12L8 9.44l3.43-3.6a.8.8 0 0 1 1.14 1.12l-4 4A.8.8 0 0 1 8 11.2Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div class="workspace-dropdown" data-user-menu-panel>
                        <div class="workspace-dropdown__profile">
                            <span class="workspace-profile__avatar workspace-profile__avatar--large"><?= htmlspecialchars($initials, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            <div class="workspace-dropdown__meta">
                                <strong><?= htmlspecialchars($currentUser['name'] ?? $currentUser['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($currentUser['role'] ?? 'Admin', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small>
                                <?php if (!empty($currentUser['email'])): ?>
                                    <span><?= htmlspecialchars($currentUser['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                <?php elseif (!empty($currentUser['username'])): ?>
                                    <span><?= htmlspecialchars('@' . $currentUser['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="workspace-dropdown__menu">
                            <a class="workspace-dropdown__link" href="<?= admin_url('settings') ?>">Profil Saya</a>
                            <a class="workspace-dropdown__link" href="<?= admin_url('settings?tab=password') ?>">Ubah Password</a>
                        </div>
                        <div class="workspace-dropdown__actions">
                            <form method="post" action="<?= admin_url('logout') ?>" class="workspace-dropdown__logout">
                                <button type="submit" class="btn btn-outline">Keluar</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($pageTitle !== null): ?>
            <div class="workspace-heading">
                <div class="workspace-heading__text">
                    <h1><?= htmlspecialchars((string) $pageTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
                    <?php if ($pageDescription !== null && $pageDescription !== ''): ?>
                        <p><?= htmlspecialchars((string) $pageDescription, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($primaryNav !== []): ?>
            <nav class="workspace-tabs" aria-label="Navigasi primer">
                <?php foreach ($primaryNav as $item): ?>
                    <?php
                        $label = htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        if ($label === '') {
                            continue;
                        }
                        $prefix = htmlspecialchars((string) ($item['prefix'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $badge = isset($item['badge']) ? htmlspecialchars((string) $item['badge'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : null;
                        $isActive = ($item['active'] ?? false) === true;
                        $isDisabled = ($item['disabled'] ?? false) === true;
                        $href = (string) ($item['href'] ?? '#');
                        $tagName = $isDisabled ? 'span' : 'a';
                        $attrs = $isDisabled
                            ? ' role="link" aria-disabled="true" tabindex="-1"'
                            : ' href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
                    ?>
                    <<?= $tagName ?> class="workspace-tab<?= $isActive ? ' is-active' : '' ?><?= $isDisabled ? ' is-disabled' : '' ?>"<?= $attrs ?>>
                        <?php if ($prefix !== ''): ?>
                            <span class="workspace-tab__prefix" aria-hidden="true"><?= $prefix ?></span>
                        <?php endif; ?>
                        <span><?= $label ?></span>
                        <?php if ($badge !== null && $badge !== ''): ?>
                            <span class="workspace-tab__badge"><?= $badge ?></span>
                        <?php endif; ?>
                    </<?= $tagName ?>>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </header>
    <?php
}

function adminDefaultPrimaryNav(?string $activeKey = null): array
{
    $items = [
        [
            'key' => 'dashboard',
            'label' => 'Overview',
            'href' => admin_url('dashboard'),
            'prefix' => 'OV',
        ],
        [
            'key' => 'analytics',
            'label' => 'Analytics',
            'href' => admin_url('analytics'),
            'prefix' => 'AN',
        ],
        [
            'key' => 'content',
            'label' => 'Konten',
            'href' => admin_url('articles'),
            'prefix' => 'CT',
        ],
        [
            'key' => 'catalog',
            'label' => 'Catalog',
            'href' => admin_url('products'),
            'prefix' => 'CG',
        ],
        [
            'key' => 'settings',
            'label' => 'Settings',
            'href' => admin_url('settings'),
            'prefix' => 'ST',
        ],
    ];

    foreach ($items as &$item) {
        $item['active'] = $activeKey !== null && $item['key'] === $activeKey;
    }
    unset($item);

    return $items;
}

function adminDefaultQuickActions(): array
{
    return [
        [
            'label' => 'Buat Artikel',
            'href' => admin_url('articles'),
            'variant' => 'primary',
        ],
        [
            'label' => 'Tambah Produk',
            'href' => admin_url('products'),
            'variant' => 'ghost',
        ],
    ];
}

function adminLayoutStart(array $options = []): void
{
    $defaults = [
        'title' => 'Dashboard Admin | Aspal Emulsi',
        'body_class' => 'dashboard',
        'sidebar_active' => null,
        'topbar_section' => 'dashboard',
        'page_title' => null,
        'page_description' => null,
        'topbar' => [],
        'head' => '',
        'content_wrapper' => true,
    ];

    $options = array_replace_recursive($defaults, $options);

    $headExtras = (string) ($options['head'] ?? '');
    $title = (string) $options['title'];
    $bodyClass = trim((string) $options['body_class']);
    $bodyClassAttr = $bodyClass !== '' ? htmlspecialchars($bodyClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'dashboard';

    $topbarOptions = is_array($options['topbar']) ? $options['topbar'] : [];
    $topbarOptions += [
        'brand' => 'Aspal Emulsi',
        'subtitle' => 'Admin Intelligence',
    ];
    $topbarOptions['pageTitle'] ??= $options['page_title'];
    $topbarOptions['pageDescription'] ??= $options['page_description'];
    $topbarOptions['primaryNav'] ??= adminDefaultPrimaryNav($options['topbar_section'] ?? null);
    $topbarOptions['quickActions'] ??= adminDefaultQuickActions();

    $layoutState = [
        'content_wrapper' => (bool) $options['content_wrapper'],
    ];

    $GLOBALS['_ADMIN_LAYOUT_STACK'][] = $layoutState;

    echo '<!DOCTYPE html>' . PHP_EOL;
    echo '<html lang="id">' . PHP_EOL;
    echo '    <head>' . PHP_EOL;
    echo '        <meta charset="UTF-8">' . PHP_EOL;
    echo '        <meta name="viewport" content="width=device-width, initial-scale=1.0">' . PHP_EOL;
    echo '        ' . adminIdentityMeta() . PHP_EOL;
    echo '        <title>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>' . PHP_EOL;
    $cssVersion = file_exists(__DIR__ . '/admin.css') ? (string) filemtime(__DIR__ . '/admin.css') : (string) time();
    echo '        <link rel="stylesheet" href="admin.css?v=' . $cssVersion . '">' . PHP_EOL;
    echo '        <script src="app.js" defer></script>' . PHP_EOL;
    if ($headExtras !== '') {
        echo '        ' . $headExtras . PHP_EOL;
    }
    echo '    </head>' . PHP_EOL;
    echo '    <body class="' . $bodyClassAttr . '">' . PHP_EOL;

    $sidebarActive = $options['sidebar_active'];
    require __DIR__ . '/partials/sidebar.php';

    echo '        <main class="content">' . PHP_EOL;
    adminRenderTopbar($topbarOptions);

    if ($layoutState['content_wrapper']) {
        echo '            <div class="workspace-content">' . PHP_EOL;
    }
}

function adminLayoutEnd(): void
{
    if (!isset($GLOBALS['_ADMIN_LAYOUT_STACK']) || $GLOBALS['_ADMIN_LAYOUT_STACK'] === []) {
        echo '        </main>' . PHP_EOL;
        echo '    </body>' . PHP_EOL;
        echo '</html>' . PHP_EOL;

        return;
    }

    $layoutState = array_pop($GLOBALS['_ADMIN_LAYOUT_STACK']);

    if (($layoutState['content_wrapper'] ?? true) === true) {
        echo '            </div>' . PHP_EOL;
    }

    echo '        </main>' . PHP_EOL;
    echo '    </body>' . PHP_EOL;
    echo '</html>' . PHP_EOL;
}

function adminCurrentUserHasRole(string ...$roleSlugs): bool
{
    $currentUser = adminCurrentUser();

    if ($currentUser === null) {
        return false;
    }

    if ($roleSlugs === []) {
        return true;
    }

    $currentSlug = $currentUser['role_slug'] ?? null;

    return $currentSlug !== null && in_array($currentSlug, $roleSlugs, true);
}

function adminRequireRole(string ...$roleSlugs): void
{
    adminRequireAuth();

    if ($roleSlugs === []) {
        return;
    }

    if (!adminCurrentUserHasRole(...$roleSlugs)) {
        abort_admin(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}

function adminLogout(): void
{
    $_SESSION[ADMIN_SESSION_KEY] = null;
    unset($_SESSION[ADMIN_SESSION_KEY]);
    session_regenerate_id(true);
}

function adminFetchUserByUsername(string $username): ?array
{
    $pdo = getPDO();

    $statement = $pdo->prepare(
        'SELECT u.id, u.username, u.full_name, u.email, u.password_hash, u.role_id, r.name AS role_name, r.slug AS role_slug
         FROM users u
         LEFT JOIN roles r ON r.id = u.role_id
         WHERE u.username = :username
         LIMIT 1'
    );

    $statement->execute(['username' => $username]);

    $user = $statement->fetch();

    return $user ?: null;
}

function adminFetchUserByEmail(string $email): ?array
{
    $email = trim($email);

    if ($email === '') {
        return null;
    }

    try {
        $pdo = getPDO();
        $statement = $pdo->prepare(
            'SELECT u.id, u.username, u.full_name, u.email, u.password_hash, u.role_id, r.name AS role_name, r.slug AS role_slug
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    } catch (Throwable $throwable) {
        error_log('Fetch user by email failed: ' . $throwable->getMessage());

        return null;
    }
}

function adminGetDashboardSummary(): array
{
    $defaults = [
        'metrics' => [
            ['label' => 'Total Admin', 'value' => 0, 'accent' => 'primary'],
            ['label' => 'Artikel Terbit', 'value' => 0, 'accent' => 'success'],
            ['label' => 'Produk Aktif', 'value' => 0, 'accent' => 'highlight'],
            ['label' => 'Layanan Aktif', 'value' => 0, 'accent' => 'service'],
            ['label' => 'Draft Artikel', 'value' => 0, 'accent' => 'warning'],
            ['label' => 'Kategori Konten', 'value' => 0, 'accent' => 'neutral'],
        ],
        'recent_posts' => [],
        'seo_alerts' => [],
    ];

    try {
        $pdo = getPDO();

        $totals = $pdo->query(
            "SELECT
                (SELECT COUNT(*) FROM users) AS users_total,
                (SELECT COUNT(*) FROM blog_posts WHERE status = 'published') AS published_posts,
                (SELECT COUNT(*) FROM products WHERE status = 'published') AS published_products,
                (SELECT COUNT(*) FROM services WHERE status = 'published') AS published_services,
                (SELECT COUNT(*) FROM blog_posts WHERE status = 'draft') AS draft_posts,
                (SELECT COUNT(*) FROM blog_categories) AS categories_total"
        )->fetch();

        if ($totals) {
            $defaults['metrics'][0]['value'] = (int) $totals['users_total'];
            $defaults['metrics'][1]['value'] = (int) $totals['published_posts'];
            $defaults['metrics'][2]['value'] = (int) ($totals['published_products'] ?? 0);
            $defaults['metrics'][3]['value'] = (int) ($totals['published_services'] ?? 0);
            $defaults['metrics'][4]['value'] = (int) $totals['draft_posts'];
            $defaults['metrics'][5]['value'] = (int) $totals['categories_total'];
        }

        $recentPostsStatement = $pdo->prepare(
            'SELECT bp.title, bp.slug, bp.status, bp.published_at, u.full_name AS author
             FROM blog_posts bp
             LEFT JOIN users u ON u.id = bp.author_id
             ORDER BY COALESCE(bp.published_at, bp.created_at) DESC
             LIMIT 5'
        );
        $recentPostsStatement->execute();
        $defaults['recent_posts'] = $recentPostsStatement->fetchAll() ?: [];

        $seoAlertsStatement = $pdo->prepare(
            "SELECT owner_type, owner_id, meta_title, meta_description, meta_keywords
             FROM seo_meta
             WHERE (meta_title IS NULL OR meta_title = '')
                OR (meta_description IS NULL OR CHAR_LENGTH(meta_description) < 110)
                OR (meta_keywords IS NULL OR meta_keywords = '')
             ORDER BY updated_at DESC
             LIMIT 5"
        );
        $seoAlertsStatement->execute();
        $defaults['seo_alerts'] = $seoAlertsStatement->fetchAll() ?: [];
    } catch (Throwable $throwable) {
        error_log('Dashboard summary error: ' . $throwable->getMessage());
    }

    return $defaults;
}

function adminFetchSiteSettings(): array
{
    static $cache;

    if (is_array($cache)) {
        return $cache;
    }

    try {
        $pdo = getPDO();
        $statement = $pdo->query('SELECT * FROM site_settings ORDER BY id ASC LIMIT 1');
        $settings = $statement ? $statement->fetch() : [];
        $cache = $settings ?: [];
    } catch (Throwable $throwable) {
        error_log('Fetch site settings failed: ' . $throwable->getMessage());
        $cache = [];
    }

    return $cache;
}

function adminEnsurePasswordResetTable(): void
{
    static $ensured = false;

    if ($ensured) {
        return;
    }

    try {
        $pdo = getPDO();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                token_hash VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_expires_at (expires_at),
                CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $ensured = true;
    } catch (Throwable $throwable) {
        error_log('Ensure password reset table failed: ' . $throwable->getMessage());
    }
}

function adminDeletePasswordResetTokens(int $userId): void
{
    try {
        $pdo = getPDO();
        $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id')->execute(['user_id' => $userId]);
    } catch (Throwable $throwable) {
        error_log('Delete reset tokens failed: ' . $throwable->getMessage());
    }
}

function adminCreatePasswordResetToken(int $userId): ?string
{
    adminEnsurePasswordResetTable();

    try {
        $pdo = getPDO();
        $token = bin2hex(random_bytes(32));
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id')->execute(['user_id' => $userId]);
        $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)')->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
        $pdo->commit();

        return $token;
    } catch (Throwable $throwable) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Create reset token failed: ' . $throwable->getMessage());

        return null;
    }
}

function adminValidatePasswordResetToken(int $userId, string $token): ?array
{
    adminEnsurePasswordResetTable();

    try {
        $pdo = getPDO();
        $statement = $pdo->prepare(
            'SELECT id, token_hash, expires_at, created_at
             FROM password_reset_tokens
             WHERE user_id = :user_id
             ORDER BY created_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        $tokens = $statement->fetchAll() ?: [];
        $now = new DateTimeImmutable('now');

        foreach ($tokens as $row) {
            if (!empty($row['expires_at'])) {
                try {
                    $expires = new DateTimeImmutable((string) $row['expires_at']);
                    if ($expires < $now) {
                        $pdo->prepare('DELETE FROM password_reset_tokens WHERE id = :id')->execute(['id' => (int) $row['id']]);
                        continue;
                    }
                } catch (Throwable $ignored) {
                    $pdo->prepare('DELETE FROM password_reset_tokens WHERE id = :id')->execute(['id' => (int) $row['id']]);
                    continue;
                }
            }

            if (password_verify($token, $row['token_hash'])) {
                return $row;
            }
        }
    } catch (Throwable $throwable) {
        error_log('Validate reset token failed: ' . $throwable->getMessage());
    }

    return null;
}

function adminSendPasswordResetEmail(string $email, string $token): bool
{
    $settings = adminFetchSiteSettings();
    $companyName = $settings['company_name'] ?? 'Aspal Emulsi';
    $fromEmail = $settings['company_email'] ?? ('no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'aspalemulsi.local'));
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $baseUrl = rtrim($scheme . '://' . $host, '/');
    $resetUrl = $baseUrl . admin_url('reset-password', [
        'token' => $token,
        'email' => $email,
    ]);

    $subject = $companyName . ' | Reset Password Admin';
    $lines = [
        'Halo,',
        '',
        'Kami menerima permintaan untuk reset password akun admin Anda.',
        'Silakan klik tautan berikut (berlaku 1 jam):',
        $resetUrl,
        '',
        'Jika Anda tidak meminta reset ini, abaikan email ini.',
        '',
        'Terima kasih,',
        $companyName,
    ];
    $message = implode("\n", $lines);

    $headers = [];
    $headers[] = 'From: ' . sprintf('%s <%s>', $companyName, $fromEmail);
    $headers[] = 'Reply-To: ' . $fromEmail;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';

    return @mail($email, $subject, $message, implode("\r\n", $headers));
}

function adminAnalyticsResolveRange(array $query): array
{
    $mode = strtolower(trim((string) ($query['range'] ?? '7d')));
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
    $end = $now;
    $label = '7 Hari Terakhir';

    switch ($mode) {
        case 'today':
            $start = $now->setTime(0, 0, 0);
            $label = 'Hari Ini';
            break;
        case 'yesterday':
            $start = $now->modify('-1 day')->setTime(0, 0, 0);
            $end = $start->setTime(23, 59, 59);
            $label = 'Kemarin';
            break;
        case '7d':
        case '7days':
            $start = $now->modify('-6 day')->setTime(0, 0, 0);
            $label = '7 Hari Terakhir';
            $mode = '7d';
            break;
        case '30d':
            $start = $now->modify('-29 day')->setTime(0, 0, 0);
            $label = '30 Hari Terakhir';
            break;
        case '90d':
            $start = $now->modify('-89 day')->setTime(0, 0, 0);
            $label = '90 Hari Terakhir';
            break;
        case '12m':
        case '1y':
            $start = $now->modify('-11 month')->setTime(0, 0, 0);
            $label = '12 Bulan Terakhir';
            $mode = '12m';
            break;
        case 'custom':
            $startInput = trim((string) ($query['start'] ?? ''));
            $endInput = trim((string) ($query['end'] ?? ''));

            try {
                $start = new DateTimeImmutable($startInput);
            } catch (Throwable) {
                $start = $now->modify('-6 day')->setTime(0, 0, 0);
            }

            try {
                $end = new DateTimeImmutable($endInput);
            } catch (Throwable) {
                $end = $now;
            }

            if ($start > $end) {
                [$start, $end] = [$end, $start];
            }

            $label = sprintf(
                '%s — %s',
                $start->format('d M Y'),
                $end->format('d M Y')
            );
            break;
        default:
            $start = $now->modify('-6 day')->setTime(0, 0, 0);
            $mode = '7d';
            $label = '7 Hari Terakhir';
            break;
    }

    return [
        'mode' => $mode,
        'label' => $label,
        'start' => $start,
        'end' => $end,
    ];
}

function adminAnalyticsBuildProductWhere(array $filters, array $range, string $alias = 'product_click_events'): array
{
    $prefix = $alias !== '' ? $alias . '.' : '';

    $where = [$prefix . 'created_at BETWEEN :start AND :end'];
    $params = [
        'start' => $range['start']->format('Y-m-d H:i:s'),
        'end' => $range['end']->format('Y-m-d H:i:s'),
    ];

    $channel = strtolower(trim((string) ($filters['channel'] ?? '')));
    if ($channel !== '') {
        $where[] = $prefix . 'cta_channel = :channel';
        $params['channel'] = $channel;
    }

    $productFilter = trim((string) ($filters['product'] ?? ''));
    if ($productFilter !== '') {
        $productId = ctype_digit($productFilter) ? (int) $productFilter : -1;
        $where[] = sprintf(
            '(LOWER(%sproduct_slug) = :product_slug_filter OR %sproduct_name LIKE :product_name_filter OR %sproduct_id = :product_id_filter)',
            $prefix,
            $prefix,
            $prefix
        );
        $params['product_slug_filter'] = strtolower($productFilter);
        $params['product_name_filter'] = '%' . $productFilter . '%';
        $params['product_id_filter'] = $productId;
    }

    return ['sql' => implode(' AND ', $where), 'params' => $params];
}

function adminAnalyticsBuildContentWhere(array $filters, array $range, string $alias = 'content_view_events', string $contentType = 'blog_post'): array
{
    $prefix = $alias !== '' ? $alias . '.' : '';
    $where = [
        $prefix . 'created_at BETWEEN :start AND :end',
        $prefix . 'content_type = :content_type',
    ];

    $params = [
        'start' => $range['start']->format('Y-m-d H:i:s'),
        'end' => $range['end']->format('Y-m-d H:i:s'),
        'content_type' => $contentType,
    ];

    $postFilter = trim((string) ($filters['post'] ?? ''));
    if ($postFilter !== '') {
        $postId = ctype_digit($postFilter) ? (int) $postFilter : -1;
        $where[] = sprintf(
            '(LOWER(%scontent_slug) = :content_slug_filter OR %scontent_title LIKE :content_title_filter OR %scontent_id = :content_id_filter)',
            $prefix,
            $prefix,
            $prefix
        );
        $params['content_slug_filter'] = strtolower($postFilter);
        $params['content_title_filter'] = '%' . $postFilter . '%';
        $params['content_id_filter'] = $postId;
    }

    return ['sql' => implode(' AND ', $where), 'params' => $params];
}

function adminAnalyticsFetchProductClickAnalytics(array $filters): array
{
    $pdo = getPDO();
    $range = adminAnalyticsResolveRange($filters);
    $where = adminAnalyticsBuildProductWhere($filters, $range, 'pce');
    $whereSql = $where['sql'];
    $params = $where['params'];

    $result = [
        'range' => $range,
        'summary' => [
            'total' => 0,
            'unique_ips' => 0,
            'first_event' => null,
            'last_event' => null,
        ],
        'channels' => [],
        'products' => [],
        'locations' => [],
        'devices' => [],
        'daily' => [],
        'recent' => [],
    ];

    try {
        $summaryStmt = $pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                COUNT(DISTINCT pce.ip_address) AS unique_ips,
                MIN(pce.created_at) AS first_event,
                MAX(pce.created_at) AS last_event
            FROM product_click_events pce
            WHERE ' . $whereSql
        );
        $summaryStmt->execute($params);
        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $result['summary'] = [
            'total' => (int) ($summary['total'] ?? 0),
            'unique_ips' => (int) ($summary['unique_ips'] ?? 0),
            'first_event' => $summary['first_event'] ?? null,
            'last_event' => $summary['last_event'] ?? null,
        ];
    } catch (Throwable $throwable) {
        error_log('Analytics summary failed: ' . $throwable->getMessage());
    }

    try {
        $channelStmt = $pdo->prepare(
            'SELECT pce.cta_channel AS channel, COUNT(*) AS total
             FROM product_click_events pce
             WHERE ' . $whereSql . '
             GROUP BY pce.cta_channel
             ORDER BY total DESC'
        );
        $channelStmt->execute($params);
        $result['channels'] = $channelStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Analytics channel breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $productStmt = $pdo->prepare(
            'SELECT
                pce.product_id,
                pce.product_slug,
                pce.product_name,
                COUNT(*) AS total
             FROM product_click_events pce
             WHERE ' . $whereSql . '
             GROUP BY pce.product_id, pce.product_slug, pce.product_name
             ORDER BY total DESC
             LIMIT 10'
        );
        $productStmt->execute($params);
        $result['products'] = $productStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Analytics product breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $locationStmt = $pdo->prepare(
            'SELECT
                pce.country,
                pce.region,
                pce.city,
                COUNT(*) AS total
             FROM product_click_events pce
             WHERE ' . $whereSql . '
             GROUP BY pce.country, pce.region, pce.city
             ORDER BY total DESC
             LIMIT 10'
        );
        $locationStmt->execute($params);
        $result['locations'] = $locationStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Analytics location breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $deviceStmt = $pdo->prepare(
            'SELECT
                COALESCE(pce.device_type, "unknown") AS device,
                COUNT(*) AS total
             FROM product_click_events pce
             WHERE ' . $whereSql . '
             GROUP BY device
             ORDER BY total DESC'
        );
        $deviceStmt->execute($params);
        $result['devices'] = $deviceStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Analytics device breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $dailyStmt = $pdo->prepare(
            'SELECT DATE(pce.created_at) AS period, COUNT(*) AS total
             FROM product_click_events pce
             WHERE ' . $whereSql . '
             GROUP BY period
             ORDER BY period DESC
             LIMIT 30'
        );
        $dailyStmt->execute($params);
        $daily = $dailyStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $result['daily'] = array_reverse($daily);
    } catch (Throwable $throwable) {
        error_log('Analytics daily breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $recentStmt = $pdo->prepare(
            'SELECT
                pce.created_at,
                pce.product_name,
                pce.product_slug,
                pce.cta_channel,
                pce.cta_label,
                pce.country,
                pce.region,
                pce.city,
                pce.ip_address,
                pce.device_type,
                pce.browser,
                pce.os,
                pce.referrer_url
             FROM product_click_events pce
             WHERE ' . $whereSql . '
             ORDER BY pce.created_at DESC
             LIMIT 100'
        );
        $recentStmt->execute($params);
        $result['recent'] = $recentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Analytics recent list failed: ' . $throwable->getMessage());
    }

    return $result;
}

function adminAnalyticsFetchContentViewAnalytics(array $filters): array
{
    $pdo = getPDO();
    $range = adminAnalyticsResolveRange($filters);
    $where = adminAnalyticsBuildContentWhere($filters, $range, 'cve', 'blog_post');
    $whereSql = $where['sql'];
    $params = $where['params'];

    $result = [
        'range' => $range,
        'summary' => [
            'total' => 0,
            'unique_ips' => 0,
            'first_event' => null,
            'last_event' => null,
        ],
        'posts' => [],
        'locations' => [],
        'daily' => [],
        'recent' => [],
    ];

    try {
        $summaryStmt = $pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                COUNT(DISTINCT cve.ip_address) AS unique_ips,
                MIN(cve.created_at) AS first_event,
                MAX(cve.created_at) AS last_event
             FROM content_view_events cve
             WHERE ' . $whereSql
        );
        $summaryStmt->execute($params);
        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $result['summary'] = [
            'total' => (int) ($summary['total'] ?? 0),
            'unique_ips' => (int) ($summary['unique_ips'] ?? 0),
            'first_event' => $summary['first_event'] ?? null,
            'last_event' => $summary['last_event'] ?? null,
        ];
    } catch (Throwable $throwable) {
        error_log('Content analytics summary failed: ' . $throwable->getMessage());
    }

    try {
        $postStmt = $pdo->prepare(
            'SELECT
                cve.content_id,
                cve.content_slug,
                cve.content_title,
                COUNT(*) AS total
             FROM content_view_events cve
             WHERE ' . $whereSql . '
             GROUP BY cve.content_id, cve.content_slug, cve.content_title
             ORDER BY total DESC
             LIMIT 10'
        );
        $postStmt->execute($params);
        $result['posts'] = $postStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Content analytics post breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $locationStmt = $pdo->prepare(
            'SELECT
                cve.country,
                cve.region,
                cve.city,
                COUNT(*) AS total
             FROM content_view_events cve
             WHERE ' . $whereSql . '
             GROUP BY cve.country, cve.region, cve.city
             ORDER BY total DESC
             LIMIT 10'
        );
        $locationStmt->execute($params);
        $result['locations'] = $locationStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Content analytics location breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $dailyStmt = $pdo->prepare(
            'SELECT DATE(cve.created_at) AS period, COUNT(*) AS total
             FROM content_view_events cve
             WHERE ' . $whereSql . '
             GROUP BY period
             ORDER BY period DESC
             LIMIT 30'
        );
        $dailyStmt->execute($params);
        $daily = $dailyStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $result['daily'] = array_reverse($daily);
    } catch (Throwable $throwable) {
        error_log('Content analytics daily breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $recentStmt = $pdo->prepare(
            'SELECT
                cve.created_at,
                cve.content_title,
                cve.content_slug,
                cve.country,
                cve.region,
                cve.city,
                cve.ip_address,
                cve.device_type,
                cve.browser,
                cve.os,
                cve.referrer_url
             FROM content_view_events cve
             WHERE ' . $whereSql . '
             ORDER BY cve.created_at DESC
             LIMIT 100'
        );
        $recentStmt->execute($params);
        $result['recent'] = $recentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Content analytics recent list failed: ' . $throwable->getMessage());
    }

    return $result;
}

function adminAnalyticsDistinctChannels(): array
{
    $channels = [];

    try {
        $pdo = getPDO();

        $productStmt = $pdo->query('SELECT DISTINCT cta_channel AS channel FROM product_click_events');
        if ($productStmt) {
            $channels = array_merge($channels, $productStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        }

        $marketStmt = $pdo->query('SELECT DISTINCT channel FROM marketplace_click_events');
        if ($marketStmt) {
            $channels = array_merge($channels, $marketStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        }
    } catch (Throwable $throwable) {
        error_log('Analytics channel list failed: ' . $throwable->getMessage());
    }

    $channels = array_filter(array_map(static function ($channel) {
        if (!is_string($channel)) {
            return null;
        }

        $trimmed = strtolower(trim($channel));

        return $trimmed === '' ? null : $trimmed;
    }, $channels));

    $channels = array_unique($channels);
    sort($channels);

    return array_values($channels);
}

function adminAnalyticsBuildMarketplaceWhere(array $filters, array $range, string $alias = 'marketplace_click_events'): array
{
    $prefix = $alias !== '' ? $alias . '.' : '';
    $where = [
        $prefix . 'created_at BETWEEN :start AND :end',
    ];

    $params = [
        'start' => $range['start']->format('Y-m-d H:i:s'),
        'end' => $range['end']->format('Y-m-d H:i:s'),
    ];

    $channel = strtolower(trim((string) ($filters['channel'] ?? '')));
    if ($channel !== '') {
        $where[] = $prefix . 'channel = :channel';
        $params['channel'] = $channel;
    }

    return ['sql' => implode(' AND ', $where), 'params' => $params];
}

function adminAnalyticsFetchMarketplaceAnalytics(array $filters): array
{
    $pdo = getPDO();
    $range = adminAnalyticsResolveRange($filters);
    $where = adminAnalyticsBuildMarketplaceWhere($filters, $range, 'mce');
    $whereSql = $where['sql'];
    $params = $where['params'];

    $result = [
        'range' => $range,
        'summary' => [
            'total' => 0,
            'unique_ips' => 0,
            'first_event' => null,
            'last_event' => null,
        ],
        'channels' => [],
        'pages' => [],
        'contexts' => [],
        'locations' => [],
        'daily' => [],
        'recent' => [],
    ];

    try {
        $summaryStmt = $pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                COUNT(DISTINCT mce.ip_address) AS unique_ips,
                MIN(mce.created_at) AS first_event,
                MAX(mce.created_at) AS last_event
             FROM marketplace_click_events mce
             WHERE ' . $whereSql
        );
        $summaryStmt->execute($params);
        $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $result['summary'] = [
            'total' => (int) ($summary['total'] ?? 0),
            'unique_ips' => (int) ($summary['unique_ips'] ?? 0),
            'first_event' => $summary['first_event'] ?? null,
            'last_event' => $summary['last_event'] ?? null,
        ];
    } catch (Throwable $throwable) {
        error_log('Marketplace analytics summary failed: ' . $throwable->getMessage());
    }

    try {
        $channelStmt = $pdo->prepare(
            'SELECT mce.channel AS channel, COUNT(*) AS total
             FROM marketplace_click_events mce
             WHERE ' . $whereSql . '
             GROUP BY mce.channel
             ORDER BY total DESC'
        );
        $channelStmt->execute($params);
        $result['channels'] = $channelStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Marketplace analytics channel breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $pageStmt = $pdo->prepare(
            'SELECT
                COALESCE(mce.source_page, "unknown") AS source_page,
                COUNT(*) AS total
             FROM marketplace_click_events mce
             WHERE ' . $whereSql . '
             GROUP BY source_page
             ORDER BY total DESC
             LIMIT 10'
        );
        $pageStmt->execute($params);
        $result['pages'] = $pageStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Marketplace analytics page breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $contextStmt = $pdo->prepare(
            'SELECT
                COALESCE(mce.source_context, "unknown") AS source_context,
                COUNT(*) AS total
             FROM marketplace_click_events mce
             WHERE ' . $whereSql . '
             GROUP BY source_context
             ORDER BY total DESC
             LIMIT 10'
        );
        $contextStmt->execute($params);
        $result['contexts'] = $contextStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Marketplace analytics context breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $locationStmt = $pdo->prepare(
            'SELECT
                mce.country,
                mce.region,
                mce.city,
                COUNT(*) AS total
             FROM marketplace_click_events mce
             WHERE ' . $whereSql . '
             GROUP BY mce.country, mce.region, mce.city
             ORDER BY total DESC
             LIMIT 10'
        );
        $locationStmt->execute($params);
        $result['locations'] = $locationStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Marketplace analytics location breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $dailyStmt = $pdo->prepare(
            'SELECT DATE(mce.created_at) AS period, COUNT(*) AS total
             FROM marketplace_click_events mce
             WHERE ' . $whereSql . '
             GROUP BY period
             ORDER BY period DESC
             LIMIT 30'
        );
        $dailyStmt->execute($params);
        $daily = $dailyStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $result['daily'] = array_reverse($daily);
    } catch (Throwable $throwable) {
        error_log('Marketplace analytics daily breakdown failed: ' . $throwable->getMessage());
    }

    try {
        $recentStmt = $pdo->prepare(
            'SELECT
                mce.created_at,
                mce.channel,
                mce.source_page,
                mce.source_context,
                mce.link_label,
                mce.country,
                mce.region,
                mce.city,
                mce.ip_address,
                mce.device_type,
                mce.browser,
                mce.os,
                mce.referrer_url
             FROM marketplace_click_events mce
             WHERE ' . $whereSql . '
             ORDER BY mce.created_at DESC
             LIMIT 100'
        );
        $recentStmt->execute($params);
        $result['recent'] = $recentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Marketplace analytics recent list failed: ' . $throwable->getMessage());
    }

    return $result;
}

function adminAnalyticsListProducts(int $limit = 50): array
{
    try {
        $pdo = getPDO();
        $statement = $pdo->prepare(
            'SELECT id, name, slug
             FROM products
             ORDER BY name ASC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Analytics product list failed: ' . $throwable->getMessage());

        return [];
    }
}

function adminAnalyticsListBlogPosts(int $limit = 50): array
{
    try {
        $pdo = getPDO();
        $statement = $pdo->prepare(
            'SELECT id, title, slug
             FROM blog_posts
             WHERE status = "published"
             ORDER BY title ASC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $throwable) {
        error_log('Analytics blog list failed: ' . $throwable->getMessage());

        return [];
    }
}

/**
 * Kompres dan konversi gambar ke WebP dengan kualitas optimal
 * 
 * @param string $sourcePath Path file gambar yang akan diproses
 * @param string $destinationPath Path tujuan untuk file WebP
 * @param int $maxWidth Maksimal lebar gambar (default: 1920)
 * @param int $quality Kualitas WebP 0-100 (default: 85 untuk kualitas bagus dengan ukuran kecil)
 * @return bool True jika berhasil, false jika gagal
 */
function adminCompressAndConvertToWebP(string $sourcePath, string $destinationPath, int $maxWidth = 1920, int $quality = 85): bool
{
    if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
        return false;
    }

    $imageInfo = @getimagesize($sourcePath);
    if ($imageInfo === false) {
        return false;
    }

    [$originalWidth, $originalHeight, $imageType] = $imageInfo;

    // Hitung dimensi baru dengan mempertahankan aspect ratio
    if ($originalWidth > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = (int) round(($originalHeight * $maxWidth) / $originalWidth);
    } else {
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;
    }

    // Load gambar berdasarkan tipe
    $sourceImage = null;
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = @imagecreatefrompng($sourcePath);
            // Preserve transparency untuk PNG
            imagealphablending($sourceImage, false);
            imagesavealpha($sourceImage, true);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    if ($sourceImage === false) {
        return false;
    }

    // Buat gambar baru dengan dimensi yang sudah di-resize
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency untuk PNG/WebP
    if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_WEBP) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparent);
    }

    // Resize gambar dengan kualitas tinggi
    imagecopyresampled(
        $newImage,
        $sourceImage,
        0, 0, 0, 0,
        $newWidth,
        $newHeight,
        $originalWidth,
        $originalHeight
    );

    // Simpan sebagai WebP
    $result = imagewebp($newImage, $destinationPath, $quality);

    // Cleanup memory
    imagedestroy($sourceImage);
    imagedestroy($newImage);

    return $result !== false;
}

/**
 * Handle upload gambar dengan auto compress dan konversi ke WebP
 * 
 * @param array|null $file File dari $_FILES
 * @param string $prefix Prefix untuk nama file (contoh: 'post', 'product')
 * @param string $uploadsDir Directory untuk menyimpan file
 * @param array &$errors Array untuk menyimpan error messages
 * @param int $maxSizeBytes Maksimal ukuran file upload dalam bytes (default: 10MB, akan dikompres otomatis)
 * @return string|null Path relatif file yang berhasil diupload, atau null jika gagal
 */
function adminHandleImageUpload(?array $file, string $prefix, string $uploadsDir, array &$errors, int $maxSizeBytes = 10485760): ?string
{
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload gambar gagal.';
        return null;
    }

    // Validasi ukuran file upload (maksimal 10MB, akan dikompres otomatis)
    if ($file['size'] > $maxSizeBytes) {
        $maxSizeMB = round($maxSizeBytes / 1048576, 1);
        $errors[] = sprintf('Ukuran file upload maksimal %s MB. File akan dikompres otomatis setelah upload.', $maxSizeMB);
        return null;
    }

    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    $detectedType = mime_content_type($file['tmp_name']);
    
    if (!in_array($detectedType, $allowedMime, true)) {
        $errors[] = 'Format gambar harus JPG, PNG, atau WEBP.';
        return null;
    }

    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
        $errors[] = 'Upload folder tidak dapat dibuat.';
        return null;
    }

    // Generate nama file WebP
    $newFilename = sprintf('%s-%s.webp', $prefix, date('YmdHis') . '-' . bin2hex(random_bytes(4)));
    $destination = $uploadsDir . '/' . $newFilename;

    // Kompres dan konversi ke WebP
    if (!adminCompressAndConvertToWebP($file['tmp_name'], $destination)) {
        // Fallback: jika konversi gagal, coba copy langsung (untuk file yang sudah WebP)
        if ($detectedType === 'image/webp') {
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors[] = 'Gagal menyimpan file gambar.';
                return null;
            }
        } else {
            $errors[] = 'Gagal memproses gambar. Pastikan ekstensi GD dengan dukungan WebP terinstall.';
            return null;
        }
    }

    return '/uploads/' . $newFilename;
}

function adminAnalyticsFetchWaveOverview(array $filters): array
{
    $pdo = getPDO();
    $range = adminAnalyticsResolveRange($filters);
    $startStr = $range['start']->format('Y-m-d 00:00:00');
    $endStr = $range['end']->format('Y-m-d 23:59:59');

    // Pastikan tabel leads ada
    try {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS leads (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                needs TEXT NULL,
                source VARCHAR(100) DEFAULT "floating_wa",
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_leads_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');
    } catch (Throwable) {
    }

    // Generate tanggal berurutan
    $currentDate = $range['start'];
    $labels = [];
    $displayLabels = [];
    $dailyVisitors = [];
    $dailyWa = [];
    $dailyLeads = [];

    while ($currentDate <= $range['end']) {
        $key = $currentDate->format('Y-m-d');
        $labels[] = $key;
        $displayLabels[] = $currentDate->format('d M');
        $dailyVisitors[$key] = 0;
        $dailyWa[$key] = 0;
        $dailyLeads[$key] = 0;
        $currentDate = $currentDate->modify('+1 day');
    }

    // 1. Visitor Harian & Total
    $totalVisitors = 0;
    try {
        $stmt = $pdo->prepare('
            SELECT DATE(created_at) as dt, COUNT(*) as cnt
            FROM content_view_events
            WHERE created_at BETWEEN :start AND :end
            GROUP BY DATE(created_at)
        ');
        $stmt->execute([':start' => $startStr, ':end' => $endStr]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dt = (string) $row['dt'];
            $c = (int) $row['cnt'];
            if (isset($dailyVisitors[$dt])) {
                $dailyVisitors[$dt] += $c;
            }
            $totalVisitors += $c;
        }
    } catch (Throwable) {
    }

    // Fallback visitor dari klik jika content_view_events masih sepi
    if ($totalVisitors === 0) {
        try {
            $stmt = $pdo->prepare('
                SELECT DATE(created_at) as dt, COUNT(DISTINCT ip_address) as cnt
                FROM product_click_events
                WHERE created_at BETWEEN :start AND :end
                GROUP BY DATE(created_at)
            ');
            $stmt->execute([':start' => $startStr, ':end' => $endStr]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dt = (string) $row['dt'];
                $c = (int) $row['cnt'];
                if (isset($dailyVisitors[$dt])) {
                    $dailyVisitors[$dt] += $c;
                }
                $totalVisitors += $c;
            }
        } catch (Throwable) {
        }
    }

    // 2. Klik WA Harian & Total (Marketplace click WA + Product click WA)
    $totalWa = 0;
    try {
        $stmt = $pdo->prepare('
            SELECT DATE(created_at) as dt, COUNT(*) as cnt
            FROM marketplace_click_events
            WHERE LOWER(channel) = "whatsapp" AND created_at BETWEEN :start AND :end
            GROUP BY DATE(created_at)
        ');
        $stmt->execute([':start' => $startStr, ':end' => $endStr]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dt = (string) $row['dt'];
            $c = (int) $row['cnt'];
            if (isset($dailyWa[$dt])) {
                $dailyWa[$dt] += $c;
            }
            $totalWa += $c;
        }

        $stmt2 = $pdo->prepare('
            SELECT DATE(created_at) as dt, COUNT(*) as cnt
            FROM product_click_events
            WHERE LOWER(cta_channel) = "whatsapp" AND created_at BETWEEN :start AND :end
            GROUP BY DATE(created_at)
        ');
        $stmt2->execute([':start' => $startStr, ':end' => $endStr]);
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $dt = (string) $row['dt'];
            $c = (int) $row['cnt'];
            if (isset($dailyWa[$dt])) {
                $dailyWa[$dt] += $c;
            }
            $totalWa += $c;
        }
    } catch (Throwable) {
    }

    // 3. Leads Harian & Total
    $totalLeads = 0;
    try {
        $stmt = $pdo->prepare('
            SELECT DATE(created_at) as dt, COUNT(*) as cnt
            FROM leads
            WHERE created_at BETWEEN :start AND :end
            GROUP BY DATE(created_at)
        ');
        $stmt->execute([':start' => $startStr, ':end' => $endStr]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dt = (string) $row['dt'];
            $c = (int) $row['cnt'];
            if (isset($dailyLeads[$dt])) {
                $dailyLeads[$dt] += $c;
            }
            $totalLeads += $c;
        }
    } catch (Throwable) {
    }

    return [
        'range' => $range,
        'totals' => [
            'visitors' => $totalVisitors,
            'wa' => $totalWa,
            'leads' => $totalLeads,
        ],
        'chart' => [
            'labels' => $displayLabels,
            'visitors' => array_values($dailyVisitors),
            'wa' => array_values($dailyWa),
            'leads' => array_values($dailyLeads),
        ],
    ];
}




