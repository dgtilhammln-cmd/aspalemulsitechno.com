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
    $cleanWaNumber = preg_replace('/\D+/', '', (string) ($floatingWhatsapp ?: '6281234567890'));
    if (!str_starts_with($cleanWaNumber, '62') && str_starts_with($cleanWaNumber, '0')) {
        $cleanWaNumber = '62' . substr($cleanWaNumber, 1);
    }
    ?>
    <button type="button" class="floating-btn floating-btn--whatsapp" id="btnFloatingWhatsApp" aria-label="Konsultasi WhatsApp">
        <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.472-.148-.672.15-.198.297-.769.966-.941 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.447-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.672-1.612-.921-2.207-.242-.58-.487-.501-.672-.51-.173-.008-.371-.01-.57-.01-.199 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.199 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.226 1.36.194 1.872.118.571-.085 1.758-.718 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"></path>
            <path d="M20.52 3.48A11.793 11.793 0 0 0 12 0C5.373 0 0 5.373 0 12c0 2.116.555 4.186 1.608 6.007L0 24l6.182-1.581A11.946 11.946 0 0 0 12 24c6.627 0 12-5.373 12-12 0-3.197-1.246-6.206-3.48-8.52zM12 22.125a10.07 10.07 0 0 1-5.143-1.409l-.368-.217-3.675.94.982-3.584-.24-.368A10.11 10.11 0 0 1 1.875 12c0-5.603 4.522-10.125 10.125-10.125S22.125 6.397 22.125 12 17.603 22.125 12 22.125z"></path>
        </svg>
    </button>
    <button class="floating-btn floating-btn--top" type="button" aria-label="Kembali ke atas">
        <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
            <path d="M12 5l7 7H5z"></path>
        </svg>
    </button>
</div>

<!-- Modal Pop Up Lead Tracker WhatsApp -->
<div id="waLeadModal" class="lead-modal-backdrop" aria-hidden="true" style="display:none;">
    <div class="lead-modal-card">
        <button type="button" class="lead-modal-close" id="btnCloseLeadModal" aria-label="Tutup">&times;</button>
        <div class="lead-modal-head">
            <div class="lead-modal-icon-badge">
                <svg viewBox="0 0 24 24" fill="currentColor" width="30" height="30">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.472-.148-.672.15-.198.297-.769.966-.941 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.447-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.672-1.612-.921-2.207-.242-.58-.487-.501-.672-.51-.173-.008-.371-.01-.57-.01-.199 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.199 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.226 1.36.194 1.872.118.571-.085 1.758-.718 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path d="M20.52 3.48A11.793 11.793 0 0 0 12 0C5.373 0 0 5.373 0 12c0 2.116.555 4.186 1.608 6.007L0 24l6.182-1.581A11.946 11.946 0 0 0 12 24c6.627 0 12-5.373 12-12 0-3.197-1.246-6.206-3.48-8.52zM12 22.125a10.07 10.07 0 0 1-5.143-1.409l-.368-.217-3.675.94.982-3.584-.24-.368A10.11 10.11 0 0 1 1.875 12c0-5.603 4.522-10.125 10.125-10.125S22.125 6.397 22.125 12 17.603 22.125 12 22.125z"/>
                </svg>
            </div>
            <h3 class="lead-modal-title">Konsultasi via WhatsApp</h3>
            <p class="lead-modal-desc">Isi form singkat di bawah ini untuk langsung terhubung dengan tim teknis kami.</p>
        </div>
        <form id="formWaLeadTracker" class="lead-modal-body">
            <div class="lead-form-group">
                <label for="leadInputName">Nama Lengkap <span style="color:#ef4444;">*</span></label>
                <input type="text" id="leadInputName" required placeholder="Nama Anda / Perusahaan">
            </div>
            <div class="lead-form-group">
                <label for="leadInputPhone">Nomor WhatsApp <span style="color:#ef4444;">*</span></label>
                <input type="tel" id="leadInputPhone" required placeholder="Contoh: 081234567890">
            </div>
            <div class="lead-form-group">
                <label for="leadInputKota">Kota / Lokasi Proyek <span style="color:#ef4444;">*</span></label>
                <input type="text" id="leadInputKota" required placeholder="Contoh: Jakarta, Surabaya, Bandung...">
            </div>
            <div class="lead-form-group">
                <label for="leadInputNeeds">Kebutuhan Proyek / Produk <span style="color:#ef4444;">*</span></label>
                <textarea id="leadInputNeeds" rows="3" required placeholder="Contoh: Butuh aspal emulsi 10 drum untuk perbaikan jalan..."></textarea>
            </div>
            <button type="submit" class="lead-btn-submit" id="btnSubmitLeadForm">
                <span>Mulai Chat Sekarang</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </form>
    </div>
</div>

<style>
.lead-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 999999;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    animation: leadModalFadeIn 0.25s ease-out;
}
@keyframes leadModalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.lead-modal-card {
    background: #ffffff;
    width: 100%;
    max-width: 440px;
    border-radius: 20px;
    padding: 28px 24px 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    position: relative;
    animation: leadModalSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    color: #1e293b;
    box-sizing: border-box;
}
@keyframes leadModalSlideUp {
    from { transform: translateY(20px) scale(0.96); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}
.lead-modal-close {
    position: absolute;
    top: 14px;
    right: 18px;
    background: transparent;
    border: none;
    font-size: 28px;
    line-height: 1;
    color: #94a3b8;
    cursor: pointer;
    transition: color 0.15s;
}
.lead-modal-close:hover {
    color: #0f172a;
}
.lead-modal-head {
    text-align: center;
    margin-bottom: 20px;
}
.lead-modal-icon-badge {
    width: 58px;
    height: 58px;
    background: #25d366;
    color: #ffffff;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    box-shadow: 0 8px 16px rgba(37, 211, 102, 0.35);
}
.lead-modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px;
}
.lead-modal-desc {
    font-size: 0.88rem;
    color: #64748b;
    margin: 0;
    line-height: 1.45;
}
.lead-form-group {
    margin-bottom: 14px;
    text-align: left;
}
.lead-form-group label {
    display: block;
    font-size: 0.84rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.lead-form-group input,
.lead-form-group textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 0.92rem;
    color: #0f172a;
    background: #f8fafc;
    box-sizing: border-box;
    transition: border-color 0.15s, background 0.15s;
    font-family: inherit;
}
.lead-form-group input:focus,
.lead-form-group textarea:focus {
    outline: none;
    border-color: #25d366;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.15);
}
.lead-btn-submit {
    width: 100%;
    padding: 12px 18px;
    background: linear-gradient(135deg, #25d366, #128c7e);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 8px;
    box-shadow: 0 6px 18px rgba(37, 211, 102, 0.3);
    transition: transform 0.15s, box-shadow 0.15s;
}
.lead-btn-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(37, 211, 102, 0.4);
}
.lead-btn-submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
</style>

<script>
(function() {
    var modal    = document.getElementById('waLeadModal');
    var btnClose = document.getElementById('btnCloseLeadModal');
    var form     = document.getElementById('formWaLeadTracker');
    var btnSubmit = document.getElementById('btnSubmitLeadForm');
    var targetWa  = '<?= $cleanWaNumber ?>';

    // ── Destinasi WA yang akan dibuka setelah form diisi ──────────────────
    // Disimpan saat user klik link WA manapun di halaman
    var pendingWaUrl = '';

    // ── Fungsi buka modal ─────────────────────────────────────────────────
    function openModal(pendingUrl) {
        if (!modal) return;
        pendingWaUrl = pendingUrl || '';
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        var firstInput = document.getElementById('leadInputName');
        if (firstInput) setTimeout(function() { firstInput.focus(); }, 80);
    }

    // ── Fungsi tutup modal ────────────────────────────────────────────────
    function closeModal() {
        if (!modal) return;
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }

    // ── Floating WA button ────────────────────────────────────────────────
    var btnOpen = document.getElementById('btnFloatingWhatsApp');
    if (btnOpen) {
        btnOpen.addEventListener('click', function(e) {
            e.preventDefault();
            openModal('');
        });
    }

    // ── GLOBAL INTERCEPTOR: Tangkap SEMUA klik link wa.me di halaman ─────
    // Berjalan setelah DOM selesai dimuat, termasuk link yang di-render JS
    function interceptAllWaLinks() {
        document.querySelectorAll('a[href*="wa.me"], a[href*="whatsapp.com/send"], a[href*="api.whatsapp.com"]').forEach(function(link) {
            if (link.dataset.waIntercepted) return; // Jangan duplikat listener
            link.dataset.waIntercepted = '1';
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openModal(this.href);
            });
        });
    }

    // Jalankan saat DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', interceptAllWaLinks);
    } else {
        interceptAllWaLinks();
    }

    // Jalankan ulang setelah 1.5 detik untuk link yang di-render JS (lazy)
    setTimeout(interceptAllWaLinks, 1500);

    // ── Tutup modal klik luar / tombol close ──────────────────────────────
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });

    // ── Submit Form ───────────────────────────────────────────────────────
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var name  = (document.getElementById('leadInputName').value  || '').trim();
            var phone = (document.getElementById('leadInputPhone').value || '').trim();
            var kota  = (document.getElementById('leadInputKota').value  || '').trim();
            var needs = (document.getElementById('leadInputNeeds').value || '').trim();

            if (!name || !phone || !kota) {
                // Highlight field kosong
                ['leadInputName','leadInputPhone','leadInputKota','leadInputNeeds'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el && !el.value.trim()) {
                        el.style.borderColor = '#ef4444';
                        el.addEventListener('input', function() { el.style.borderColor = ''; }, { once: true });
                    }
                });
                return;
            }

            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Menyimpan & Mengalihkan...';

            // Ekstrak UTM & Referrer
            var urlParams   = new URLSearchParams(window.location.search);
            var utmSource   = urlParams.get('utm_source')   || '';
            var utmMedium   = urlParams.get('utm_medium')   || '';
            var utmCampaign = urlParams.get('utm_campaign') || '';
            var utmTerm     = urlParams.get('utm_term')     || '';
            var utmContent  = urlParams.get('utm_content')  || '';
            var referrer    = document.referrer || '';

            // Tracking backend (fire & forget)
            try {
                fetch('/api/track/lead', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: name, phone: phone, kota: kota, needs: needs,
                        source: pendingWaUrl ? 'wa_link_intercept' : 'floating_wa_modal',
                        utm_source: utmSource, utm_medium: utmMedium, utm_campaign: utmCampaign,
                        utm_term: utmTerm, utm_content: utmContent,
                        referrer: referrer, page_url: window.location.href
                    })
                }).catch(function() {});
            } catch (err) {}

            // Template pesan WA
            var msg = 'Halo tim Aspal Emulsi Techno,\n' +
                      'Saya mendapatkan kontak dari *aspalemulsitechno.com* dan ingin konsultasi penawaran:\n\n' +
                      '\u2022 *Nama:* ' + name + '\n' +
                      '\u2022 *No. WhatsApp:* ' + phone + '\n' +
                      '\u2022 *Kota / Lokasi:* ' + kota + '\n' +
                      '\u2022 *Kebutuhan:* ' + needs + '\n\n' +
                      'Mohon info penawaran harga & spesifikasi lengkapnya. Terima kasih!';

            // Gunakan pendingWaUrl jika ada (link yang di-intercept), fallback ke nomor utama
            var finalUrl = pendingWaUrl || ('https://wa.me/' + targetWa + '?text=' + encodeURIComponent(msg));
            // Ganti text di URL jika sudah ada ?text= (dari link produk dll)
            if (finalUrl.indexOf('wa.me/') !== -1) {
                var baseWa = finalUrl.split('?')[0];
                finalUrl = baseWa + '?text=' + encodeURIComponent(msg);
            }

            setTimeout(function() {
                window.open(finalUrl, '_blank');
                closeModal();
                form.reset();
                pendingWaUrl = '';
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<span>Mulai Chat Sekarang</span> <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
            }, 300);
        });
    }
})();
</script>
