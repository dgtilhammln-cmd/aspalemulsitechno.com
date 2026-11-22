<?php

$settings = $siteSettings ?? [];
$companyName = $settings['company_name'] ?? 'ASPAL EMULSI TECHNO.COM';
$logoUrl = $settings['logo_url'] ?? asset('images/logo.png');
$instagramUrl = $settings['instagram_url'] ?? 'https://instagram.com';
$anchorBase = ($currentRoute ?? null) === 'home' ? '#' : '/#';
$navItems = [
    ['label' => 'Beranda', 'anchor' => 'beranda'],
    ['label' => 'Layanan', 'anchor' => 'layanan'],
    ['label' => 'Jasa Teknis', 'anchor' => 'jasa'],
    ['label' => 'Produk', 'anchor' => 'produk'],
    ['label' => 'Blog', 'anchor' => 'blog'],
    ['label' => 'Testimoni', 'anchor' => 'testimoni'],
    ['label' => 'Portofolio', 'anchor' => 'galeri'],
];
?>
<header class="header" id="top">
    <div class="container header-inner">
        <a class="header-icon header-icon--social" href="<?= e($instagramUrl) ?>" target="_blank" rel="noopener" aria-label="Ikuti Instagram">
            <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                <path d="M7 2C4.243 2 2 4.243 2 7v10c0 2.757 2.243 5 5 5h10c2.757 0 5-2.243 5-5V7c0-2.757-2.243-5-5-5H7zm0 2h10a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3zm5 3a5 5 0 1 0 0 10a5 5 0 0 0 0-10zm0 2a3 3 0 1 1 0 6a3 3 0 0 1 0-6zm6.5-3a1.5 1.5 0 1 0 0 3a1.5 1.5 0 0 0 0-3z" />
            </svg>
        </a>
        <div class="logo" data-animate="fade-down">
            <a href="/">
                <img src="<?= e($logoUrl) ?>" style="text-align: center; width: 260px; height: auto; max-width: min(260px, 50vw);" alt="<?= e($companyName) ?>">
            </a>
        </div>
        <button class="nav-toggle" type="button" aria-label="Toggle navigasi" aria-expanded="false">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3.5 6.25A1.25 1.25 0 0 1 4.75 5h14.5a1.25 1.25 0 0 1 0 2.5H4.75A1.25 1.25 0 0 1 3.5 6.25Zm0 5.75A1.25 1.25 0 0 1 4.75 10.75h14.5a1.25 1.25 0 0 1 0 2.5H4.75A1.25 1.25 0 0 1 3.5 12Zm1.25 4.5a1.25 1.25 0 0 0 0 2.5h14.5a1.25 1.25 0 0 0 0-2.5H4.75Z"/>
            </svg>
        </button>
        <nav class="main-nav" aria-label="Navigasi utama">
            <?php foreach ($navItems as $item): ?>
                <a class="nav-link" href="<?= e($anchorBase . $item['anchor']) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
            <a class="nav-link nav-link--cta" href="<?= e($anchorBase . 'hubungi') ?>">Hubungi</a>
        </nav>
    </div>
</header>
