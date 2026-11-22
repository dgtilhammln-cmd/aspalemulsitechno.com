<?php

declare(strict_types=1);

/**
 * MUNG CMS V1
 * File: admin/partials/sidebar.php
 * Author: Firmanda Himawan
 * Description: Shared sidebar component for the admin dashboard with dynamic active menu handling.
 */

$sidebarActive = $sidebarActive ?? '';

$sidebarMenu = [
    [
        'key' => 'dashboard',
        'label' => 'Overview',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M8.4 2.1c.6-.6 1.6-.6 2.2 0l5.2 5.2c.3.3.5.7.5 1.1v5.9c0 .9-.7 1.6-1.6 1.6h-2.8c-.9 0-1.6-.7-1.6-1.6v-2.1c0-.7-.5-1.2-1.2-1.2H9.5c-.7 0-1.2.5-1.2 1.2v2.1c0 .9-.7 1.6-1.6 1.6H3.9c-.9 0-1.6-.7-1.6-1.6V8.4c0-.4.2-.8.5-1.1l5.6-5.2Z"/></svg>',
        'href' => admin_url('dashboard'),
    ],
    [
        'key' => 'analytics',
        'label' => 'Analytics',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M3 15.75a.75.75 0 0 1-.75-.75v-2.5a.75.75 0 0 1 1.5 0v1.75h11.5a.75.75 0 0 1 0 1.5H3Zm3-4.5a.75.75 0 0 1-.75-.75v-4a.75.75 0 1 1 1.5 0v4H15a.75.75 0 0 1 0 1.5H6Zm3-5.5a.75.75 0 0 1-.75-.75V3.75a.75.75 0 1 1 1.5 0V5.5H15a.75.75 0 0 1 0 1.5h-4.5Z"/></svg>',
        'href' => admin_url('analytics'),
    ],
    [
        'key' => 'leads',
        'label' => 'Leads Tracker',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M9 1.5a7.5 7.5 0 1 0 7.5 7.5A7.51 7.51 0 0 0 9 1.5Zm0 13.5A6 6 0 1 1 15 9a6.01 6.01 0 0 1-6 6Zm0-10.5a4.5 4.5 0 1 0 4.5 4.5A4.51 4.51 0 0 0 9 4.5Zm0 7.5A3 3 0 1 1 12 9a3 3 0 0 1-3 3Zm-.75-4.5h1.5v3h-1.5v-3Zm0-3h1.5v1.5h-1.5V6Z"/></svg>',
        'href' => admin_url('leads'),
    ],
    [
        'key' => 'articles',
        'label' => 'Artikel',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M5 2.25h8c1.24 0 2.25 1 2.25 2.25v9c0 1.24-1 2.25-2.25 2.25H5A2.25 2.25 0 0 1 2.75 13.5v-9A2.25 2.25 0 0 1 5 2.25Zm3.25 2.5a.75.75 0 0 0-1.5 0v5.5a.75.75 0 1 0 1.5 0v-5.5Zm2 .5a.75.75 0 1 0 0 1.5H12a.75.75 0 0 0 0-1.5h-1.75ZM13 9h-1.75a.75.75 0 0 0 0 1.5H13a.75.75 0 0 0 0-1.5Z"/></svg>',
        'href' => admin_url('articles'),
    ],
    [
        'key' => 'categories',
        'label' => 'Kategori Blog',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M3 3.75A.75.75 0 0 1 3.75 3h10.5a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H3.75A.75.75 0 0 1 3 14.25V3.75ZM5.5 6a.75.75 0 1 0 0 1.5h7a.75.75 0 0 0 0-1.5h-7Zm0 3a.75.75 0 1 0 0 1.5h7a.75.75 0 0 0 0-1.5h-7Zm0 3a.75.75 0 1 0 0 1.5h4.5a.75.75 0 0 0 0-1.5H5.5Z"/></svg>',
        'href' => admin_url('categories'),
    ],
    [
        'key' => 'products',
        'label' => 'Produk',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M8.36 2.4c.44-.24.96-.24 1.4 0l4.38 2.4c.52.28.86.84.86 1.44v5.52c0 .6-.34 1.16-.86 1.44l-4.38 2.4c-.44.24-.96.24-1.4 0l-4.38-2.4A1.61 1.61 0 0 1 3 11.76V6.24c0-.6.34-1.16.86-1.44l4.5-2.4Z"/></svg>',
        'href' => admin_url('products'),
    ],
    [
        'key' => 'services',
        'label' => 'Layanan',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M9 2.25a6.75 6.75 0 1 0 6.75 6.75A6.76 6.76 0 0 0 9 2.25Zm0 1.5A5.25 5.25 0 1 1 3.75 9 5.26 5.26 0 0 1 9 3.75Zm-.75 2.25a.75.75 0 0 1 1.5 0v2.5H12a.75.75 0 0 1 0 1.5H8.25a.75.75 0 0 1-.75-.75V6Z"/></svg>',
        'href' => admin_url('services'),
    ],
    [
        'key' => 'portfolio',
        'label' => 'Galeri',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M4.5 3h9c.83 0 1.5.67 1.5 1.5v9c0 .83-.67 1.5-1.5 1.5H4.5A1.5 1.5 0 0 1 3 13.5v-9C3 3.67 3.67 3 4.5 3Zm8.25 6.75-2.33-2.89a.5.5 0 0 0-.78-.01L7.5 10.5 6.2 9.18a.5.5 0 0 0-.7.01l-1.5 1.5v1.56c0 .24.19.44.44.44h8.63c.4 0 .62-.46.37-.78l-1.69-2.16Z"/></svg>',
        'href' => admin_url('portfolio'),
    ],
    [
        'key' => 'testimonials',
        'label' => 'Testimoni',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M4.5 3.75A2.25 2.25 0 0 0 2.25 6v6c0 1.24 1 2.25 2.25 2.25h9A2.25 2.25 0 0 0 15.75 12V6A2.25 2.25 0 0 0 13.5 3.75h-9Zm.75 3a.75.75 0 1 1 0 1.5H4.5a.75.75 0 0 1 0-1.5h.75Zm3 0a.75.75 0 1 1 0 1.5h-1.5a.75.75 0 1 1 0-1.5h1.5ZM9 12c-1.24 0-2.25.6-2.25 1.34 0 .23.17.41.38.41h3.74c.21 0 .38-.18.38-.41C11.25 12.6 10.24 12 9 12Zm4.5-4.5H12.75a.75.75 0 1 1 0-1.5h.75a.75.75 0 0 1 0 1.5Z"/></svg>',
        'href' => admin_url('testimonials'),
    ],
    [
        'key' => 'users',
        'label' => 'Manajemen User',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M11.25 5.25a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0ZM6.5 8.25a3.75 3.75 0 1 1 5 0c1.65.5 2.75 2 2.75 3.73v.77c0 .62-.5 1.13-1.13 1.13H4.88A1.12 1.12 0 0 1 3.75 12.75v-.77c0-1.73 1.1-3.23 2.75-3.73Z"/></svg>',
        'href' => admin_url('users'),
        'visible' => adminCurrentUserHasRole('superadmin'),
    ],
    [
        'key' => 'home-slider',
        'label' => 'Home Slider',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M4.5 4.5A1.5 1.5 0 0 0 3 6v6a1.5 1.5 0 0 0 1.5 1.5h9A1.5 1.5 0 0 0 15 12V6a1.5 1.5 0 0 0-1.5-1.5h-9ZM6 6.75h6a.75.75 0 0 1 0 1.5H6a.75.75 0 0 1 0-1.5Zm0 3h4.5a.75.75 0 0 1 0 1.5H6a.75.75 0 0 1 0-1.5Z"/></svg>',
        'href' => admin_url('home-slider'),
    ],
    [
        'key' => 'sections',
        'label' => 'Konten Section',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M3 3.75A.75.75 0 0 1 3.75 3h10.5a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H3.75A.75.75 0 0 1 3 14.25V3.75ZM5.5 6a.75.75 0 1 0 0 1.5h7a.75.75 0 0 0 0-1.5h-7Zm0 3a.75.75 0 1 0 0 1.5h7a.75.75 0 0 0 0-1.5h-7Zm0 3a.75.75 0 1 0 0 1.5h4.5a.75.75 0 0 0 0-1.5H5.5Z"/></svg>',
        'href' => admin_url('sections'),
    ],
    [
        'key' => 'theme-settings',
        'label' => 'Pengaturan Tema',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M9 5.25a3.75 3.75 0 1 1 0 7.5 3.75 3.75 0 0 1 0-7.5Zm0 1.5a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Zm6.39 1.5-.8-.46.17-.87a.96.96 0 0 0-.76-1.12l-.91-.17-.17-.9a.97.97 0 0 0-1.13-.77l-.87.18-.46-.8a.96.96 0 0 0-1.31-.35l-.78.45-.78-.45a.96.96 0 0 0-1.31.35l-.46.8-.87-.18a.97.97 0 0 0-1.13.76l-.17.91-.9.17a.96.96 0 0 0-.77 1.12l.18.87-.8.46a.96.96 0 0 0-.35 1.32l.45.77-.45.78a.96.96 0 0 0 .35 1.31l.8.46-.18.87a.96.96 0 0 0 .77 1.12l.9.17.17.91a.97.97 0 0 0 1.13.77l.87-.18.46.8c.26.45.84.6 1.31.35l.78-.45.78.45a.96.96 0 0 0 1.31-.35l.46-.8.87.18a.97.97 0 0 0 1.13-.76l.17-.91.91-.17a.96.96 0 0 0 .77-1.12l-.18-.87.8-.46a.96.96 0 0 0 .35-1.32l-.45-.77.45-.78a.96.96 0 0 0-.35-1.31Z"/></svg>',
        'href' => admin_url('theme-settings'),
        'visible' => adminCurrentUserHasRole('superadmin'),
    ],
    [
        'key' => 'settings',
        'label' => 'Pengaturan',
        'icon' => '<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path fill="currentColor" d="M9 5.25a3.75 3.75 0 1 1 0 7.5 3.75 3.75 0 0 1 0-7.5Zm0 1.5a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Zm6.39 1.5-.8-.46.17-.87a.96.96 0 0 0-.76-1.12l-.91-.17-.17-.9a.97.97 0 0 0-1.13-.77l-.87.18-.46-.8a.96.96 0 0 0-1.31-.35l-.78.45-.78-.45a.96.96 0 0 0-1.31.35l-.46.8-.87-.18a.97.97 0 0 0-1.13.76l-.17.91-.9.17a.96.96 0 0 0-.77 1.12l.18.87-.8.46a.96.96 0 0 0-.35 1.32l.45.77-.45.78a.96.96 0 0 0 .35 1.31l.8.46-.18.87a.96.96 0 0 0 .77 1.12l.9.17.17.91a.97.97 0 0 0 1.13.77l.87-.18.46.8c.26.45.84.6 1.31.35l.78-.45.78.45a.96.96 0 0 0 1.31-.35l.46-.8.87.18a.97.97 0 0 0 1.13-.76l.17-.91.91-.17a.96.96 0 0 0 .77-1.12l-.18-.87.8-.46a.96.96 0 0 0 .35-1.32l-.45-.77.45-.78a.96.96 0 0 0-.35-1.31Z"/></svg>',
        'href' => admin_url('settings'),
    ],
];

?>
<aside class="sidebar" data-sidebar>
    <div class="sidebar__inner">
        <div class="sidebar__brand">
            <span class="sidebar__logo" aria-hidden="true">
                <img src="/favicon.png" alt="Aspal Emulsi" class="sidebar__logo-img">
            </span>
            <div class="sidebar__brand-meta">
                <strong>Aspal Emulsi</strong>
                <small>Admin Control</small>
            </div>
        </div>
        <div class="sidebar__status">
            <span class="sidebar__status-dot" aria-hidden="true"></span>
            <span>Workspace online</span>
        </div>
        <nav class="sidebar__nav" aria-label="Navigasi utama">
            <?php foreach ($sidebarMenu as $item): ?>
                <?php if (array_key_exists('visible', $item) && $item['visible'] === false): ?>
                    <?php continue; ?>
                <?php endif; ?>
                <?php
                    $isActive = $sidebarActive === $item['key'];
                    $classes = 'sidebar__link' . ($isActive ? ' is-active' : '');
                    $icon = $item['icon'] ?? '';
                ?>
                <a class="<?= $classes ?>" href="<?= $item['href'] ?>">
                    <span class="sidebar__icon" aria-hidden="true"><?= $icon ?></span>
                    <span class="sidebar__label"><?= htmlspecialchars($item['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
    <div class="sidebar__footer">
        <a class="sidebar__help" href="/" target="_blank" rel="noopener">Lihat website</a>
        <form method="post" action="<?= admin_url('logout') ?>" class="sidebar__logout">
            <button type="submit" class="btn btn-outline">Keluar</button>
        </form>
    </div>
</aside>
<div class="sidebar-overlay" data-sidebar-overlay></div>

