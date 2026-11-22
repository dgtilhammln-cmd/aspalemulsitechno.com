<?php

$settings = $siteSettings ?? [];
$companyName = $settings['company_name'] ?? 'PT Aspal Emulsi Techno';
$tagline = $settings['tagline'] ?? 'Mitra aspal emulsi dingin dan panas untuk proyek jalan, kawasan industri, hingga fasilitas kampus.';
$logoUrl = $settings['logo_url'] ?? asset('images/logo.png');
$whatsappNumber = $settings['whatsapp_number'] ?? '+62 812-3456-7890';
$email = $settings['company_email'] ?? 'support@aspalemulsi.com';
$phone = $settings['company_phone'] ?? '+62 812-3456-7890';
$address = $settings['address'] ?? 'Jl. Industri Raya No. 18, Bekasi';
$workingHours = $settings['working_hours'] ?? 'Senin - Sabtu, 08.00 - 20.00 WIB';
$instagramUrl = $settings['instagram_url'] ?? '#';
$tiktokUrl = $settings['tiktok_url'] ?? '#';
$whatsappUrl = $settings['whatsapp_number'] ? 'https://wa.me/' . preg_replace('/\D+/', '', (string) $settings['whatsapp_number']) : '#';
$anchorBase = ($currentRoute ?? null) === 'home' ? '#' : '/#';
?>
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <span class="footer-badge"><img src="<?= e($logoUrl) ?>" alt="<?= e($companyName) ?>" width="280px"></span>
                <h3 class="footer-title"><?= e($companyName) ?></h3>
                <p class="footer-text">
                    <?= e($tagline) ?>
                </p>
                <div class="footer-social">
                    <?php if ($instagramUrl && $instagramUrl !== '#'): ?>
                        <a class="social-pill" href="<?= e($instagramUrl) ?>" target="_blank" rel="noopener">
                            <span class="social-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img">
                                    <path d="M7 2C4.243 2 2 4.243 2 7v10c0 2.757 2.243 5 5 5h10c2.757 0 5-2.243 5-5V7c0-2.757-2.243-5-5-5H7zm0 2h10a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3zm5 3a5 5 0 1 0 0 10a5 5 0 0 0 0-10zm0 2a3 3 0 1 1 0 6a3 3 0 0 1 0-6zm6.5-3a1.5 1.5 0 1 0 0 3a1.5 1.5 0 0 0 0-3z" />
                                </svg>
                            </span>
                            <span>Instagram</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($tiktokUrl && $tiktokUrl !== '#'): ?>
                        <a class="social-pill" href="<?= e($tiktokUrl) ?>" target="_blank" rel="noopener">
                            <span class="social-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img">
                                    <path d="M16 2h-3.5v12.6a2.2 2.2 0 0 1-4.4 0a2.2 2.2 0 0 1 3.3-1.9V8.1a5.6 5.6 0 0 0-2.4-.54A5.6 5.6 0 0 0 3.9 13.2a5.6 5.6 0 0 0 11.2 0V7.5a8 8 0 0 0 4.9 1.64V5.9a4.6 4.6 0 0 1-4.9-3.9z" />
                                </svg>
                            </span>
                            <span>TikTok</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($whatsappUrl && $whatsappUrl !== '#'): ?>
                        <a class="social-pill" href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener" data-market-channel="whatsapp" data-market-context="footer-social" data-market-label="WhatsApp Footer">
                            <span class="social-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img">
                                    <path d="M20.52 3.48A11.793 11.793 0 0 0 12 0C5.373 0 0 5.373 0 12c0 2.116.555 4.186 1.608 6.007L0 24l6.182-1.581A11.946 11.946 0 0 0 12 24c6.627 0 12-5.373 12-12 0-3.197-1.246-6.206-3.48-8.52zM12 22.125a10.07 10.07 0 0 1-5.143-1.409l-.368-.217-3.675.94.982-3.584-.24-.368A10.11 10.11 0 0 1 1.875 12c0-5.603 4.522-10.125 10.125-10.125S22.125 6.397 22.125 12 17.603 22.125 12 22.125z" />
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.472-.148-.672.15-.198.297-.769.966-.941 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.447-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.672-1.612-.921-2.207-.242-.58-.487-.501-.672-.51-.173-.008-.371-.01-.57-.01-.199 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.199 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.226 1.36.194 1.872.118.571-.085 1.758-.718 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
                                </svg>
                            </span>
                            <span>WhatsApp</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="footer-links">
                <h4>Jelajah Cepat</h4>
                <ul>
                    <li><a href="<?= e($anchorBase . 'beranda') ?>">Beranda</a></li>
                    <li><a href="<?= e($anchorBase . 'layanan') ?>">Layanan</a></li>
                    <li><a href="<?= e($anchorBase . 'produk') ?>">Produk</a></li>
                    <li><a href="<?= e($anchorBase . 'blog') ?>">Blog</a></li>
                    <li><a href="<?= e($anchorBase . 'testimoni') ?>">Testimoni</a></li>
                    <li><a href="<?= e($anchorBase . 'galeri') ?>">Portofolio</a></li>
                    <li><a href="<?= e($anchorBase . 'hubungi') ?>">Hubungi</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h4>Kontak</h4>
                <ul>
                    <li><span>Hotline</span> <?= e($phone) ?></li>
                    <li><span>Email</span> <?= e($email) ?></li>
                    <li><span>Workshop</span> <?= e($address) ?></li>
                    <li><span>Jam Operasional</span> <?= e($workingHours) ?></li>
                </ul>
            </div>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-meta">
            <p>©<?= date('Y') ?> HVM Digital ID. All rights reserved.</p>
            <div class="footer-policy">
                <a href="#">Kebijakan Privasi</a>
                <a href="#">Syarat Layanan</a>
                <a href="#">FAQ</a>
            </div>
        </div>
    </div>
</footer>

<div class="floating-actions" aria-label="Kontak cepat" data-animate="fade-left">
    <?php
    $floatingWhatsapp = $settings['whatsapp_number'] ?? ($settings['whatsapp_url'] ?? null);
    $floatingWhatsappUrl = $floatingWhatsapp ? 'https://wa.me/' . preg_replace('/\D+/', '', (string) $floatingWhatsapp) : '#';
    if ($floatingWhatsappUrl && $floatingWhatsappUrl !== '#') {
        $waSeparator = str_contains($floatingWhatsappUrl, '?') ? '&' : '?';
        $floatingWhatsappUrl .= $waSeparator . 'text=' . rawurlencode('Halo tim Aspal Emulsi, saya ingin konsultasi lebih lanjut.');
    }
    ?>
    <?php if ($floatingWhatsappUrl && $floatingWhatsappUrl !== '#'): ?>
        <a class="floating-btn floating-btn--whatsapp" href="<?= e($floatingWhatsappUrl) ?>" target="_blank" rel="noopener" aria-label="Hubungi via WhatsApp" data-market-channel="whatsapp" data-market-context="global-floating">
            <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.472-.148-.672.15-.198.297-.769.966-.941 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.447-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.672-1.612-.921-2.207-.242-.58-.487-.501-.672-.51-.173-.008-.371-.01-.57-.01-.199 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.199 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.226 1.36.194 1.872.118.571-.085 1.758-.718 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"></path>
                <path d="M20.52 3.48A11.793 11.793 0 0 0 12 0C5.373 0 0 5.373 0 12c0 2.116.555 4.186 1.608 6.007L0 24l6.182-1.581A11.946 11.946 0 0 0 12 24c6.627 0 12-5.373 12-12 0-3.197-1.246-6.206-3.48-8.52zM12 22.125a10.07 10.07 0 0 1-5.143-1.409l-.368-.217-3.675.94.982-3.584-.24-.368A10.11 10.11 0 0 1 1.875 12c0-5.603 4.522-10.125 10.125-10.125S22.125 6.397 22.125 12 17.603 22.125 12 22.125z"></path>
            </svg>
        </a>
    <?php endif; ?>
    <a class="floating-btn floating-btn--shopee" href="<?= e($settings['shopee_url'] ?? 'https://shopee.co.id') ?>" target="_blank" rel="noopener" aria-label="Belanja di Shopee" data-market-channel="shopee" data-market-context="global-floating">
        <img src="<?= asset('images/shopee-circle.webp') ?>" alt="Shopee" class="floating-icon-image">
    </a>
    <a class="floating-btn floating-btn--tokopedia" href="<?= e($settings['tokopedia_url'] ?? 'https://tokopedia.com') ?>" target="_blank" rel="noopener" aria-label="Belanja di Tokopedia" data-market-channel="tokopedia" data-market-context="global-floating">
        <img src="<?= asset('images/tokopedia-icon-png-7.png') ?>" alt="Tokopedia" class="floating-icon-image">
    </a>
    <button class="floating-btn floating-btn--top" type="button" aria-label="Kembali ke atas">
        <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
            <path d="M12 5l7 7H5z"></path>
        </svg>
    </button>
</div>
