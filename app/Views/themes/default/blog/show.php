<?php
$postData = $post ?? [];
$related = $relatedPosts ?? [];
$author = $postData['author_display'] ?? 'Tim Aspal Emulsi';
$publishedLabel = $postData['published_label'] ?? null;
$readingTime = $postData['hero_reading_time'] ?? '5 menit';
$coverImage = $postData['cover_image_url'] ?? asset('images/blog-slurry-cover.jpg');
$coverAlt = $postData['cover_image_alt'] ?? ($postData['title'] ?? '');
$ctaHeading = $postData['cta_heading'] ?? 'Mau Planning Slurry Seal di Kampus Kamu?';
$ctaSubheading = $postData['cta_subheading'] ?? 'Klik tombol di bawah buat langsung ngobrol bareng engineer kami atau cek price list terbaru di e-commerce resmi.';
$ctaShopee = $postData['cta_shopee_url'] ?? ($siteSettings['shopee_url'] ?? 'https://shopee.co.id');
$ctaTokopedia = $postData['cta_tokopedia_url'] ?? ($siteSettings['tokopedia_url'] ?? 'https://tokopedia.com');
$ctaWhatsapp = $postData['cta_whatsapp_url'] ?? ('https://wa.me/' . preg_replace('/\D+/', '', (string) ($siteSettings['whatsapp_number'] ?? '+6281234567890')));
?>
<main>
    <article class="blog-detail">
        <section class="blog-detail__hero" data-animate="fade-up">
            <div class="container blog-detail__hero-inner">
                <div class="blog-detail__meta" data-animate="fade-right">
                    <?php if (!empty($postData['hero_tag'])): ?>
                        <span class="blog-detail__tag"><?= e($postData['hero_tag']) ?></span>
                    <?php endif; ?>
                    <h1><?= e($postData['title'] ?? '') ?></h1>
                    <div class="blog-detail__info">
                        <div>
                            <span>Ditulis oleh</span>
                            <strong><?= e($author) ?></strong>
                        </div>
                        <?php if ($publishedLabel): ?>
                            <div>
                                <span>Dipublikasikan</span>
                                <strong><?= e($publishedLabel) ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if ($readingTime): ?>
                            <div>
                                <span>Durasi baca</span>
                                <strong><?= e($readingTime) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <figure class="blog-detail__cover" data-animate="fade-left">
                    <img src="<?= e($coverImage) ?>" alt="<?= e($coverAlt) ?>">
                </figure>
            </div>
        </section>

        <section class="blog-detail__body">
            <div class="container blog-detail__layout">
                <aside class="blog-detail__toc">
                    <?= get_content_section('blog-detail', 'toc-heading', 'Isi Artikel', 'h2') ?>
                    <?php if (!empty($postData['table_of_contents'])): ?>
                        <nav aria-label="Daftar isi">
                            <ol class="toc-list">
                                <?php 
                                $currentLevel = 2; // Start dari H2
                                foreach ($postData['table_of_contents'] as $entry): 
                                    $level = (int) ($entry['level'] ?? 2);
                                    $indent = ($level - 2) * 16; // 16px per level (H2=0, H3=16px, H4=32px, dll)
                                ?>
                                    <li class="toc-item toc-item--level-<?= $level ?>" style="margin-left: <?= $indent ?>px;">
                                        <a href="#<?= e($entry['anchor']) ?>" class="toc-link" data-anchor="<?= e($entry['anchor']) ?>"><?= e($entry['label']) ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                    <?php else: ?>
                        <p>Tidak ada daftar isi untuk artikel ini.</p>
                    <?php endif; ?>
                    <a class="blog-detail__share" href="https://wa.me/?text=<?= rawurlencode(($postData['title'] ?? 'Artikel') . ' ' . (url('/blog/' . ($postData['slug'] ?? '')))) ?>" target="_blank" rel="noopener" data-market-channel="whatsapp" data-market-context="blog-share" data-market-label="Bagikan Artikel WhatsApp" data-market-page="blog-detail">Bagikan ke WhatsApp →</a>
                </aside>

                <div class="blog-detail__content" itemprop="articleBody">
                    <?php if (!empty($postData['summary'])): ?>
                        <section class="blog-section">
                            <p class="intro">
                                <?= nl2br(e($postData['summary'])) ?>
                            </p>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($postData['intro_quote'])): ?>
                        <blockquote>
                            “<?= e($postData['intro_quote']) ?>”
                            <?php if (!empty($postData['intro_quote_cite'])): ?>
                                <cite>— <?= e($postData['intro_quote_cite']) ?></cite>
                            <?php endif; ?>
                        </blockquote>
                    <?php endif; ?>

                    <?php if (!empty($postData['content'])): ?>
                        <section class="blog-section">
                            <?= $postData['content'] ?>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($postData['timeline'])): ?>
                        <section class="blog-section" id="workflow">
                            <?= get_content_section('blog-detail', 'workflow-heading', 'Workflow Eksekusi', 'h2') ?>
                            <?php
                            $timelineLabels = $postData['timeline_labels'] ?? ['left' => 'Tahap', 'right' => 'Deskripsi'];
                            ?>
                            <div class="blog-timeline-wrapper">
                                <table class="blog-timeline-table">
                                    <thead>
                                        <tr>
                                            <th><?= e($timelineLabels['left']) ?></th>
                                            <th><?= e($timelineLabels['right']) ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($postData['timeline'] as $step): ?>
                                            <tr>
                                                <td><strong><?= e($step['label']) ?></strong></td>
                                                <td><?= e($step['value']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($postData['stat_blocks'])): ?>
                        <section class="blog-section" id="studi-kasus">
                            <?= get_content_section('blog-detail', 'highlights-heading', 'Highlights Project', 'h2') ?>
                            <?php
                            $statLabels = $postData['stat_blocks_labels'] ?? ['left' => 'Label', 'right' => 'Nilai'];
                            ?>
                            <div class="blog-stat-wrapper">
                                <table class="blog-stat-table">
                                    <thead>
                                        <tr>
                                            <th><?= e($statLabels['left']) ?></th>
                                            <th><?= e($statLabels['right']) ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($postData['stat_blocks'] as $stat): ?>
                                            <tr>
                                                <td><span class="blog-stat__label"><?= e($stat['label']) ?></span></td>
                                                <td><strong class="blog-stat__value"><?= e($stat['value']) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($postData['checklist'])): ?>
                        <section class="blog-section" id="maintenance">
                            <?= get_content_section('blog-detail', 'checklist-heading', 'Checklist Maintenance', 'h2') ?>
                            <div class="blog-checklist">
                                <?php foreach ($postData['checklist'] as $item): ?>
                                    <label>
                                        <input type="checkbox" disabled>
                                        <?= e($item) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <div class="blog-detail__cta">
                        <h3><?= e($ctaHeading) ?></h3>
                        <p><?= e($ctaSubheading) ?></p>
                        <div class="product-detail__cta">
                            <a class="btn-primary" href="<?= e($ctaShopee) ?>" target="_blank" rel="noopener" data-market-channel="shopee" data-market-context="blog-cta" data-market-label="CTA Shopee Blog" data-market-page="blog-detail">Beli via Shopee</a>
                            <a class="btn-secondary" href="<?= e($ctaTokopedia) ?>" target="_blank" rel="noopener" data-market-channel="tokopedia" data-market-context="blog-cta" data-market-label="CTA Tokopedia Blog" data-market-page="blog-detail">Beli via Tokopedia</a>
                            <a class="btn-primary btn-primary--outline" href="<?= e($ctaWhatsapp) ?>" target="_blank" rel="noopener" data-market-channel="whatsapp" data-market-context="blog-cta" data-market-label="CTA WhatsApp Blog" data-market-page="blog-detail">Chat WhatsApp</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </article>

    <?php if (!empty($related)): ?>
        <section class="product-related blog-related" data-animate="fade-up">
            <div class="container">
                <div class="section-head" data-animate="fade-up">
                    <?= get_content_section('blog-detail', 'related-kicker', 'Artikel Lainnya', 'span', 'section-kicker') ?>
                    <?= get_content_section('blog-detail', 'related-heading', 'Upgrade Insight Lapangan Kamu', 'h2') ?>
                    <?= get_content_section('blog-detail', 'related-description', 'Referensi tambahan buat optimasi proyek infrastruktur dan waterproofing.', 'p') ?>
                </div>
                <div class="product-related__grid">
                    <?php foreach ($related as $index => $item): ?>
                        <?php $delay = 60 * $index; ?>
                        <article class="blog-related-card hover-glow" data-animate="tilt-up"<?= $delay ? ' data-animate-delay="' . e((string) $delay) . '"' : '' ?> >
                            <div class="blog-related-card__media">
                                <img src="<?= e($item['cover_image_url'] ?? asset('images/blog-emulsi.jpg')) ?>" alt="<?= e($item['title']) ?>">
                            </div>
                            <div class="blog-related-card__content">
                                <span class="blog-related-card__tag"><?= e($item['hero_tag'] ?? 'Blog') ?></span>
                                <h3><?= e($item['title']) ?></h3>
                                <a href="<?= '/blog/' . e($item['slug']) ?>" class="blog-related-card__link">Baca selengkapnya →</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="cta" data-animate="fade-up" style="background-image: url('<?= asset('images/efek-bintang.png') ?>');">
        <div class="container">
            <div class="cta-inner" data-animate="fade-up">
                <?= get_content_section('blog-detail', 'cta-badge', 'Siap bersinergi', 'span', 'cta-badge') ?>
                <?= get_content_section('blog-detail', 'cta-heading', 'Yuk Planning Project Bareng!', 'h2', 'cta-title') ?>
                <?= get_content_section('blog-detail', 'cta-description', 'Dari survei, rekomendasi formulasi, sampai eksekusi lapangan, kita siap bantu kamu deliver hasil yang konsisten dan tahan lama.', 'p', 'cta-text') ?>
                <div class="cta-actions">
                    <a href="/#hubungi" class="btn-primary">Hubungi Kami</a>
                    <a href="/#galeri" class="btn-secondary">Lihat Portofolio</a>
                </div>
                <div class="" style="margin-top: -200px;">
                    <div class="cta-glow"></div>
                    <img src="<?= asset('images/peta-indo.png') ?>" alt="Peta Indonesia" width="100%">
                </div>
                <?= get_content_section('blog-detail', 'coverage-text', 'Pengiriman & Pengerjaan Seluruh Indonesia', 'p', 'coverage-text', 'margin-top: -136px; text-align: center;') ?>
            </div>
        </div>
    </section>
</main>

<?php
// BlogPosting Schema for detail page
if (!empty($postData)) {
    $blogSchema = schema_blog_posting($postData, url());
    // Enhance with additional details
    $blogSchema['articleBody'] = strip_tags($postData['content'] ?? $postData['summary'] ?? '');
    
    if (!empty($postData['published_at'])) {
        $blogSchema['datePublished'] = date('c', strtotime((string) $postData['published_at']));
    }
    
    if (!empty($postData['updated_at'])) {
        $blogSchema['dateModified'] = date('c', strtotime((string) $postData['updated_at']));
    } else {
        $blogSchema['dateModified'] = $blogSchema['datePublished'];
    }
    
    if (!empty($postData['hero_reading_time'])) {
        $blogSchema['timeRequired'] = 'PT' . preg_replace('/[^0-9]/', '', (string) $postData['hero_reading_time']) . 'M';
    }
    
    // Add keywords if available
    if (!empty($postData['tags'])) {
        $blogSchema['keywords'] = is_array($postData['tags']) ? implode(', ', $postData['tags']) : $postData['tags'];
    }
    
    echo PHP_EOL . '<!-- BlogPosting Schema -->' . PHP_EOL;
    echo schema_json_ld($blogSchema) . PHP_EOL;
}

// BreadcrumbList Schema
echo PHP_EOL . '<!-- BreadcrumbList Schema -->' . PHP_EOL;
echo schema_json_ld([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Beranda',
            'item' => url(),
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Blog',
            'item' => url('/blog'),
        ],
        [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $postData['title'] ?? 'Detail Artikel',
            'item' => url('/blog/' . ($postData['slug'] ?? '')),
        ],
    ],
]) . PHP_EOL;
?>
<script>
(function() {
    'use strict';
    
    // Smooth scroll untuk TOC links dengan offset
    document.addEventListener('DOMContentLoaded', function() {
        const tocLinks = document.querySelectorAll('.toc-link');
        const scrollOffset = 80; // Offset untuk fixed header atau spacing
        
        tocLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const anchor = this.getAttribute('data-anchor') || this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(anchor);
                
                if (targetElement) {
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - scrollOffset;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Update URL hash tanpa trigger scroll
                    if (history.pushState) {
                        history.pushState(null, null, '#' + anchor);
                    } else {
                        window.location.hash = '#' + anchor;
                    }
                }
            });
        });
        
        // Handle hash on page load
        if (window.location.hash) {
            const hash = window.location.hash.substring(1);
            const targetElement = document.getElementById(hash);
            
            if (targetElement) {
                // Delay sedikit untuk memastikan page sudah fully loaded
                setTimeout(function() {
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - scrollOffset;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }, 100);
            }
        }
    });
})();
</script>