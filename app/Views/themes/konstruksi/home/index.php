<?php
$site = $siteSettings ?? [];
$companyName = $site['company_name'] ?? 'Aspal Emulsi Techno';
$tagline = $site['tagline'] ?? 'Solusi terintegrasi aspal emulsi dan waterproofing.';
$heroCopy = $tagline;
$shopeeUrl = $site['shopee_url'] ?? 'https://shopee.co.id';
$tokopediaUrl = $site['tokopedia_url'] ?? 'https://tokopedia.com';
$whatsappNumber = $site['whatsapp_number'] ?? '+6281234567890';
$whatsappUrl = 'https://wa.me/' . preg_replace('/\D+/', '', $whatsappNumber);

$servicesPrimary = array_slice($services ?? [], 0, 3);
$servicesSecondary = array_slice($services ?? [], 0, 3);
$productsList = $products ?? [];
$blogList = $blogPosts ?? [];
$portfolioList = $portfolioItems ?? [];
$testimonialsList = $testimonials ?? [];

$heroSlidesData = $heroSlides ?? [];
?>
<main>
    <section class="hero-slider-style-1" id="beranda" data-hero-slider-style-1>
        <div class="hero-slider-style-1__canvas">
            <div class="hero-slider-style-1__backgrounds">
                <?php foreach ($heroSlidesData as $index => $slide): ?>
                    <div
                        class="hero-slider-style-1__background<?= $index === 0 ? ' is-active' : '' ?>"
                        data-index="<?= (int) $index ?>"
                        style="background-image: url('<?= e($slide['image']) ?>');"
                    ></div>
                <?php endforeach; ?>
            </div>
            <div class="hero-slider-style-1__scrim"></div>
            <div class="hero-slider-style-1__content">
                <?php foreach ($heroSlidesData as $index => $slide): ?>
                    <?php
                        $primaryLink = $slide['detail_url'] ?: '#';
                        $waLink = $whatsappUrl;
                        if ($waLink && $waLink !== '#') {
                            $separator = str_contains($waLink, '?') ? '&' : '?';
                            $waLink .= $separator . 'text=' . rawurlencode(sprintf('Halo tim Aspal Emulsi, saya ingin konsultasi mengenai %s.', $slide['title']));
                        }
                    ?>
                    <article class="hero-slider-style-1__slide<?= $index === 0 ? ' is-active' : '' ?>" data-index="<?= (int) $index ?>">
                        <div class="hero-slider-style-1__illustration">
                            <img src="<?= e($slide['image']) ?>" alt="<?= e($slide['title']) ?>">
                            <span class="hero-slider-style-1__illustration-glow"></span>
                        </div>
                        <span class="hero-slider-style-1__eyebrow"><?= e($slide['subtitle']) ?></span>
                        <h1 class="hero-slider-style-1__title"><?= e($slide['title']) ?></h1>
                        <p class="hero-slider-style-1__description"><?= e($slide['description']) ?></p>
                        <div class="hero-slider-style-1__cta">
                            <a class="hero-slider-style-1__button hero-slider-style-1__button--primary" href="<?= e($primaryLink) ?>">Lihat Detail</a>
                            <a
                                class="hero-slider-style-1__button hero-slider-style-1__button--ghost"
                                href="<?= e($waLink) ?>"
                                target="_blank"
                                rel="noopener"
                                data-market-channel="whatsapp"
                                data-market-context="hero-slider-style-1"
                                data-market-label="<?= e('WA Hero ' . $slide['title']) ?>"
                            >
                                Konsultasi WA
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="hero-slider-style-1__controls">
                <button class="hero-slider-style-1__arrow hero-slider-style-1__arrow--prev" type="button" aria-label="Slide sebelumnya" data-hero-prev>
                    <span>&larr;</span>
                </button>
                <button class="hero-slider-style-1__arrow hero-slider-style-1__arrow--next" type="button" aria-label="Slide selanjutnya" data-hero-next>
                    <span>&rarr;</span>
                </button>
            </div>
        </div>
    </section>

    <section class="services2" id="layanan" data-animate="fade-up">
        <div class="container">
            <div class="services2-head" data-animate="fade-up">
                <?= get_content_section('home', 'services-kicker', 'Layanan Unggulan', 'span', 'services2-kicker') ?>
                <?= get_content_section('home', 'services-heading', 'Solusi Terintegrasi Aspal Emulsi & Waterproofing', 'h2') ?>
                <?= get_content_section('home', 'services-description', 'Kami tangani dari preparasi permukaan hingga finishing akhir dengan formulasi emulsi pilihan, memastikan daya lekat maksimum plus ketahanan cuaca ekstrem.', 'p') ?>
            </div>

            <div class="services2-grid">
                <?php
                // Generate Service schemas
                $serviceSchemas = [];
                foreach ($servicesPrimary as $service) {
                    $serviceSchemas[] = schema_service($service, url());
                }
                ?>
                <?php foreach ($servicesPrimary as $service): ?>
                    <?php
                        $serviceName = $service['name'] ?? 'Jasa Aspal Emulsi';
                        $serviceDescription = $service['short_description'] ?? excerpt_text((string) ($service['description'] ?? ''), 140);
                        $serviceImage = $service['cover_image_url'] ?: asset('images/pengerjaan.jpeg');
                        $serviceMessage = sprintf('Halo tim Aspal Emulsi, saya tertarik dengan jasa %s. Mohon info lebih lanjut ya.', $serviceName);
                        $serviceWhatsappLink = $whatsappUrl;
                        if ($serviceWhatsappLink && $serviceWhatsappLink !== '#') {
                            $separator = str_contains($serviceWhatsappLink, '?') ? '&' : '?';
                            $serviceWhatsappLink .= $separator . 'text=' . rawurlencode($serviceMessage);
                        }
                    ?>
                    <article class="services2-card hover-glow" data-animate="tilt-up">
                        <div class="services2-copy">
                            <span class="services2-badge">Jasa</span>
                            <h3><?= e($serviceName) ?></h3>
                            <p><?= e($serviceDescription) ?></p>
                            <a
                                class="services2-link"
                                href="<?= e($serviceWhatsappLink) ?>"
                                target="_blank"
                                rel="noopener"
                                data-market-channel="whatsapp"
                                data-market-context="home-service-card"
                                data-market-label="<?= e('WA Konsultasi ' . $serviceName) ?>"
                            >
                                Konsultasi pengerjaan →
                            </a>
                        </div>
                        <div class="services2-visual">
                            <div class="services2-float services2-float--amber"></div>
                            <img 
                                src="<?= e($serviceImage) ?>" 
                                alt="<?= e($service['name']) ?>"
                                loading="lazy"
                                decoding="async"
                            >
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="clients" id="klien" data-animate="fade-up">
        <div class="container">
            <div class="clients-highlight">
                <div class="clients-badge">CLIENT KAMI:</div>
                <div class="clients-list">
                    <ul>
                        <li><span>PT. SUNAN MURIA</span> - JEMBER</li>
                        <li><span>CV. SAFARA JAYA PRAKARSA</span> - SIDOARJO</li>
                        <li><span>CV. MEGA JAYA AMERTA</span> - SIDOARJO</li>
                        <li><span>DPU BINA MARGA</span> PROVINSI JAWA TIMUR</li>
                    </ul>
                    <ul>
                        <li><span>CV. ANUGERAH TRITUNGGAL JAYA</span> - SURABAYA</li>
                        <li><span>CV MAGALINDO</span> - MAGETAN</li>
                        <li><span>PT. PETOSEA</span> (FREEPORT INDONESIA GROUP)</li>
                        <li><span>PT. TRIPALINDO TRANSMIX</span> - SURABAYA</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="products" id="produk" data-animate="fade-up">
        <div class="container">
            <div class="products-inner" data-animate="fade-up">
                <div class="products-badge">
                    <span>★★★★★</span>
                    <span>Dipercaya klien proyek di seluruh Indonesia</span>
                </div>
                <?= get_content_section('home', 'products-heading', 'Solusi Produk Aspal Untuk Workflow Pengerjaan Lebih Mulus', 'h2') ?>
                <?= get_content_section('home', 'products-description', 'Formulasi emulsi kami siap pakai untuk berbagai skenario konstruksi jalan, jembatan, dan bangunan. Tingkatkan kecepatan pengerjaan tanpa mengorbankan mutu lapisan.', 'p', 'products-subtitle') ?>
                <div class="products-cta">
                    <a href="#hubungi" class="btn-primary">Mulai Konsultasi</a>
                </div>
            </div>

            <div class="product-carousel">
                <button class="product-arrow prev" aria-label="Produk sebelumnya">&#10094;</button>
                <div class="product-track-wrapper">
                    <div class="product-track">
                        <?php
                        // Generate Product schemas
                        $productSchemas = [];
                        foreach ($productsList as $product) {
                            $productSchemas[] = schema_product($product, url());
                        }
                        ?>
                        <?php foreach ($productsList as $index => $product): ?>
                            <?php
                                $delay = $index * 60;
                                $productImage = $product['cover_image_url'] ?: asset('images/colabit-seal-coat.png');
                                $productSummary = $product['short_description'] ?? excerpt_text($product['hero_eyebrow'] ?? '', 140);
                            ?>
                            <article class="product-card hover-glow" data-animate="tilt-up"<?= $delay ? ' data-animate-delay="' . e((string) $delay) . '"' : '' ?> >
                                <div class="product-icon">
                                    <img src="<?= e($productImage) ?>" alt="<?= e($product['name']) ?>">
                                </div>
                                <div class="card-content product-content">
                                    <h3><?= e($product['name']) ?></h3>
                                    <p><?= e($productSummary) ?></p>
                                    <a href="<?= '/produk/' . e($product['slug']) ?>" class="product-link">Lihat detail</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="product-arrow next" aria-label="Produk selanjutnya">&#10095;</button>
            </div>

            <div class="card-content ecommerce-links">
                <div class="ecommerce-links-grid">
                    <div class="ecommerce-links-text" style="padding:100px;">
                        <span>Produk kami tersedia di e-commerce |</span>
                    </div>
                    <div class="ecommerce-links-icons">
                        <a href="<?= e($shopeeUrl) ?>" target="_blank" rel="noopener" class="ecommerce-icon" data-market-channel="shopee" data-market-context="home-hero-icons" data-market-label="Shopee Hero Icon">
                            <img class="icon-ecommerce" src="<?= asset('images/Shopee.svg.png') ?>" alt="Shopee">
                        </a>
                        <a href="<?= e($tokopediaUrl) ?>" target="_blank" rel="noopener" class="ecommerce-icon" data-market-channel="tokopedia" data-market-context="home-hero-icons" data-market-label="Tokopedia Hero Icon">
                            <img class="icon-ecommerce" src="<?= asset('images/tokopedia-38840.png') ?>" alt="Tokopedia">
                        </a>
                    </div>
                </div>               
            </div>
        </div>
    </section>

    <section class="gallery" id="galeri" data-animate="fade-up">
        <div class="container">
            <div class="gallery-head">
                <div class="gallery-badge">Project Highlight</div>
                <?= get_content_section('home', 'gallery-heading', 'Gallery Pengerjaan', 'h2', 'gallery-title') ?>
                <?= get_content_section('home', 'gallery-description', 'Pengerjaan dilakukan dengan standar keamanan, menggunakan bahan baku berkualitas dan tenaga kerja berpengalaman, sehingga menghasilkan lapisan yang kuat, teruji, dan tahan lama.', 'p', 'gallery-intro') ?>
            </div>

            <div class="gallery-carousel">
                <button class="gallery-arrow prev" aria-label="Galeri sebelumnya">&#10094;</button>
                <div class="gallery-track-wrapper">
                    <div class="gallery-track">
                        <?php foreach ($portfolioList as $index => $portfolio): ?>
                            <?php $delay = $index * 60; ?>
                            <article class="gallery-card hover-glow" data-animate="rise"<?= $delay ? ' data-animate-delay="' . e((string) $delay) . '"' : '' ?> >
                                <figure class="gallery-media">
                                    <img src="<?= e($portfolio['image_url'] ?: asset('images/galeri1.jpeg')) ?>" alt="<?= e($portfolio['title']) ?>">
                                </figure>
                                <div class="gallery-content">
                                    <h3><?= e($portfolio['title']) ?></h3>
                                    <p><?= e($portfolio['description'] ?? '') ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="gallery-arrow next" aria-label="Galeri selanjutnya">&#10095;</button>
            </div>
        </div>
    </section>

    <section class="cta" id="hubungi" data-animate="fade-up" style="background-image: url('<?= asset('images/efek-bintang.png') ?>');">
        <div class="container">
            <div class="cta-inner" data-animate="fade-up">
                <?= get_content_section('home', 'cta-badge', 'Siap bersinergi', 'span', 'cta-badge') ?>
                <?= get_content_section('home', 'cta-heading', 'Percayakan Ke-Ahlinya!', 'h2', 'cta-title') ?>
                <?= get_content_section('home', 'cta-description', 'Telah menyelesaikan puluhan proyek dan ribuan pesanan dengan hasil kuat, tahan lama, dan terpercaya. Hubungi kami untuk konsultasi pengerjaan, atau checkout produk langsung melalui e-commerce kami.', 'p', 'cta-text') ?>
                <div class="cta-actions">
                    <a href="#hubungi" class="btn-primary">Hubungi Kami</a>
                    <a href="#galeri" class="btn-secondary">Lihat Portofolio</a>
                </div>

                <div class="" style="margin-top: -200px;">
                    <div class="cta-glow"></div>
                    <img src="<?= asset('images/peta-indo.png') ?>" alt="Peta Indonesia" width="100%">
                </div>
                <?= get_content_section('home', 'coverage-text', 'Pengiriman & Pengerjaan Seluruh Indonesia', 'p', 'coverage-text', 'margin-top: -136px; text-align: center;') ?>
            </div>
        </div>
    </section>

    <section class="blog" id="blog" data-animate="fade-up">
        <div class="container">
            <div class="blog-head" data-animate="fade-up">
                <?= get_content_section('home', 'blog-kicker', 'Wawasan Lapangan', 'span', 'blog-kicker') ?>
                <?= get_content_section('home', 'blog-heading', 'Insight Teknis & Tips Perawatan Permukaan', 'h2') ?>
                <?= get_content_section('home', 'blog-description', 'Update terbaru dari tim engineer kami seputar teknologi emulsi, manajemen pemeliharaan jalan, serta strategi efisiensi supaya proyek kamu lebih presisi dan mudah di-maintain.', 'p') ?>
            </div>

            <div class="blog-grid">
                <?php
                // Generate BlogPosting schemas
                $blogSchemas = [];
                foreach ($blogList as $post) {
                    $blogSchemas[] = schema_blog_posting($post, url());
                }
                ?>
                <?php foreach ($blogList as $index => $post): ?>
                    <?php $delay = $index * 80; ?>
                    <article class="blog-card hover-glow" data-animate="tilt-up"<?= $delay ? ' data-animate-delay="' . e((string) $delay) . '"' : '' ?> >
                        <div class="blog-media">
                            <img src="<?= e($post['cover_image_url'] ?: asset('images/blog-emulsi.jpg')) ?>" alt="<?= e($post['title']) ?>">
                        </div>
                        <div class="blog-content">
                            <span class="blog-tag"><?= e($post['hero_tag'] ?? 'Insight') ?></span>
                            <h3><?= e($post['title']) ?></h3>
                            <p><?= e(excerpt_text($post['summary'] ?? '', 140)) ?></p>
                            <div class="blog-meta">
                                <span><?= $post['published_at'] ? e(date('d M Y', strtotime((string) $post['published_at']))) : 'Segera' ?></span>
                                <span><?= e($post['hero_reading_time'] ?? '5 menit baca') ?></span>
                            </div>
                            <a class="blog-link" href="<?= '/blog/' . e($post['slug']) ?>">Baca artikel →</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="testimonials" id="testimoni" data-animate="fade-up">
        <div class="container">
            <div class="testimonials-head" data-animate="fade-up">
                <?= get_content_section('home', 'testimonials-kicker', 'Suara Klien', 'span', 'testimonials-kicker') ?>
                <?= get_content_section('home', 'testimonials-heading', 'Partner Kami Bicara Soal Hasil Akhir', 'h2') ?>
                <?= get_content_section('home', 'testimonials-description', 'Dari akses kampus, kawasan industri sampai jalur logistik, mereka percaya sama kualitas layer dan servis aftersales kami.', 'p') ?>
            </div>

            <div class="testimonials-carousel">
                <button class="testi-arrow prev" aria-label="Testimoni sebelumnya">&#10094;</button>
                <div class="testi-track-wrapper">
                    <div class="testi-track">
                        <?php
                        // Generate Review schemas
                        $reviewSchemas = [];
                        foreach ($testimonialsList as $testimonial) {
                            $reviewSchemas[] = schema_review($testimonial, $site);
                        }
                        ?>
                        <?php foreach ($testimonialsList as $index => $testimonial): ?>
                            <?php
                                $delay = $index * 80;
                                $rating = (int) ($testimonial['rating'] ?? 5);
                                $stars = str_repeat('★', $rating) . str_repeat('☆', max(0, 5 - $rating));
                            ?>
                            <article class="testi-card hover-glow" data-animate="tilt-up"<?= $delay ? ' data-animate-delay="' . e((string) $delay) . '"' : '' ?> >
                                <div class="testi-rating"><?= e($stars) ?></div>
                                <p class="testi-quote">
                                    “<?= e($testimonial['quote'] ?? '') ?>”
                                </p>
                                <div class="testi-author">
                                    <img src="<?= e($testimonial['avatar_url'] ?: asset('images/testi-1.jpg')) ?>" alt="Foto klien">
                                    <div>
                                        <h4><?= e($testimonial['client_name'] ?? 'Klien Aspal Emulsi') ?></h4>
                                        <span><?= e(trim(($testimonial['role'] ?? '') . ($testimonial['company'] ? ', ' . $testimonial['company'] : ''))) ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="testi-arrow next" aria-label="Testimoni selanjutnya">&#10095;</button>
            </div>
        </div>
    </section>
</main>

<?php
// Output all schemas at the end of the page
if (!empty($serviceSchemas ?? [])) {
    echo PHP_EOL . '<!-- Service Schemas -->' . PHP_EOL;
    foreach ($serviceSchemas as $schema) {
        echo schema_json_ld($schema) . PHP_EOL;
    }
}

if (!empty($productSchemas ?? [])) {
    echo PHP_EOL . '<!-- Product Schemas -->' . PHP_EOL;
    foreach ($productSchemas as $schema) {
        echo schema_json_ld($schema) . PHP_EOL;
    }
}

if (!empty($blogSchemas ?? [])) {
    echo PHP_EOL . '<!-- BlogPosting Schemas -->' . PHP_EOL;
    foreach ($blogSchemas as $schema) {
        echo schema_json_ld($schema) . PHP_EOL;
    }
}

if (!empty($reviewSchemas ?? [])) {
    echo PHP_EOL . '<!-- Review Schemas -->' . PHP_EOL;
    // Wrap reviews in AggregateRating
    $aggregateRating = [
        '@context' => 'https://schema.org',
        '@type' => 'AggregateRating',
        'ratingValue' => '5',
        'reviewCount' => count($reviewSchemas),
    ];
    
    // Create ItemList of reviews
    echo schema_json_ld([
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'itemListElement' => array_map(function ($index, $review) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => $review,
            ];
        }, array_keys($reviewSchemas), $reviewSchemas),
    ]) . PHP_EOL;
}
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const slider = document.querySelector('[data-hero-slider-style-1]');
    if (!slider) {
        return;
    }

    const backgrounds = slider.querySelectorAll('.hero-slider-style-1__background');
    const slides = slider.querySelectorAll('.hero-slider-style-1__slide');
    const prevBtn = slider.querySelector('[data-hero-prev]');
    const nextBtn = slider.querySelector('[data-hero-next]');

    let current = 0;
    let timer;
    let touchStartX = null;
    let touchStartY = null;
    let touchMoved = false;
    const swipeThreshold = 42;

    const setActive = (index) => {
        if (!slides[index]) {
            return;
        }

        backgrounds.forEach((bg) => bg.classList.remove('is-active'));
        slides.forEach((slide) => slide.classList.remove('is-active'));

        backgrounds[index]?.classList.add('is-active');
        slides[index]?.classList.add('is-active');
        current = index;
    };

    const goTo = (index) => {
        const nextIndex = (index + slides.length) % slides.length;
        setActive(nextIndex);
    };

    const resetTimer = () => {
        if (timer) {
            clearInterval(timer);
        }
        timer = setInterval(() => {
            goTo(current + 1);
        }, 7000);
    };

    prevBtn?.addEventListener('click', () => {
        goTo(current - 1);
        resetTimer();
    });

    nextBtn?.addEventListener('click', () => {
        goTo(current + 1);
        resetTimer();
    });

    slider.addEventListener('mouseenter', () => timer && clearInterval(timer));
    slider.addEventListener('mouseleave', () => resetTimer());

    slider.addEventListener('mousemove', (event) => {
        const activeBg = backgrounds[current];
        if (!activeBg) {
            return;
        }

        const rect = slider.getBoundingClientRect();
        const offsetX = (event.clientX - rect.left) / rect.width - 0.5;
        const offsetY = (event.clientY - rect.top) / rect.height - 0.5;
        const translateX = offsetX * 30;
        const translateY = offsetY * 24;

        activeBg.style.transform = `scale(1.05) translate3d(${translateX}px, ${translateY}px, 0)`;
    });

    slider.addEventListener('mouseleave', () => {
        backgrounds.forEach((bg) => {
            bg.style.transform = '';
        });
    });

    slider.addEventListener('touchstart', (event) => {
        if (!event.touches || event.touches.length !== 1) {
            return;
        }

        const touch = event.touches[0];
        touchStartX = touch.clientX;
        touchStartY = touch.clientY;
        touchMoved = false;
        if (timer) {
            clearInterval(timer);
        }
    }, { passive: true });

    slider.addEventListener('touchmove', (event) => {
        if (touchStartX === null) {
            return;
        }

        const touch = event.touches[0];
        const deltaX = touch.clientX - touchStartX;
        const deltaY = touch.clientY - touchStartY;

        if (Math.abs(deltaY) > Math.abs(deltaX)) {
            return;
        }

        if (Math.abs(deltaX) > 12) {
            touchMoved = true;
        }
    }, { passive: true });

    const handleTouchEnd = (event) => {
        if (touchStartX === null) {
            return;
        }

        const touch = event.changedTouches ? event.changedTouches[0] : null;
        if (!touch) {
            touchStartX = null;
            touchStartY = null;
            resetTimer();
            return;
        }

        const deltaX = touch.clientX - touchStartX;

        if (touchMoved && Math.abs(deltaX) > swipeThreshold) {
            if (deltaX > 0) {
                goTo(current - 1);
            } else {
                goTo(current + 1);
            }
        }

        touchStartX = null;
        touchStartY = null;
        touchMoved = false;
        resetTimer();
    };

    slider.addEventListener('touchend', handleTouchEnd);
    slider.addEventListener('touchcancel', handleTouchEnd);

    setActive(0);
    resetTimer();
});
</script>
