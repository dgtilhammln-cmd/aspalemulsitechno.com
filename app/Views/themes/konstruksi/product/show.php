<?php
/**
 * MUNG CMS V1
 * File: app/Views/product/show.php
 * Author: Firmanda Himawan
 * Description: Product detail page template with CTA instrumentation for analytics tracking.
 */
$productData = $product ?? [];
$related = $relatedProducts ?? [];
$productId = isset($productData['id']) ? (int) $productData['id'] : null;
$productSlug = $productData['slug'] ?? '';
$productName = $productData['name'] ?? '';
$badge = $productData['hero_badge'] ?? 'Produk Premium';
$eyebrow = $productData['hero_eyebrow'] ?? 'Solusi Pelapisan Kedap Air';
$coverImage = $productData['cover_image_url'] ?? asset('images/colabit-seal-coat.png');
$shortDescription = $productData['short_description'] ?? $productData['description'] ?? '';
$highlights = $productData['highlights'] ?? [];
$specs = $productData['specs'] ?? [];
$summaryPoints = $productData['summary_points'] ?? [];
$documents = $productData['documents'] ?? [];
$highlightBlock = $productData['highlights_block'] ?? [
    'title' => $productData['highlights_title'] ?? 'Benefit Teknis',
    'items' => $highlights,
];
$specBlock = $productData['spec_block'] ?? [
    'title' => $productData['spec_title'] ?? 'Rangkuman Teknis',
    'items' => $specs,
];
$summaryBlock = $productData['summary_block'] ?? [
    'title' => $productData['summary_title'] ?? 'Benefit Teknis',
    'items' => $summaryPoints,
];
$documentsBlock = $productData['documents_block'] ?? [
    'title' => $productData['documents_title'] ?? 'Dokumen Pendukung',
    'items' => $documents,
];
$highlightItems = $highlightBlock['items'] ?? [];
$specItems = $specBlock['items'] ?? [];
$summaryItems = $summaryBlock['items'] ?? [];
$documentItems = $documentsBlock['items'] ?? [];
$shopeeUrl = $productData['shopee_url'] ?? ($siteSettings['shopee_url'] ?? 'https://shopee.co.id');
$tokopediaUrl = $productData['tokopedia_url'] ?? ($siteSettings['tokopedia_url'] ?? 'https://tokopedia.com');
$whatsappUrl = $productData['whatsapp_url'] ?? ('https://wa.me/' . preg_replace('/\D+/', '', (string) ($siteSettings['whatsapp_number'] ?? '+6281234567890')));
?>
<main>
    <section class="product-detail-hero" data-animate="fade-up">
        <div class="container product-detail-hero__inner">
            <div class="product-detail-hero__media" data-animate="fade-right">
                <div class="product-detail-hero__tag"><?= e($badge) ?></div>
                <figure class="product-detail-hero__figure">
                    <img src="<?= e($coverImage) ?>" alt="<?= e($productData['name'] ?? '') ?>">
                </figure>
            </div>
            <div class="product-detail-hero__content" data-animate="fade-left">
                <?php if ($eyebrow !== ''): ?>
                    <span class="product-detail-hero__eyebrow"><?= e($eyebrow) ?></span>
                <?php endif; ?>
                <h1><?= e($productData['name'] ?? '') ?></h1>
                <?php if ($shortDescription !== ''): ?>
                    <p><?= nl2br(e($shortDescription)) ?></p>
                <?php endif; ?>
                <?php if (!empty($highlightItems)): ?>
                    <h3><?= e($highlightBlock['title']) ?></h3>
                    <ul class="product-detail__highlights">
                        <?php foreach ($highlightItems as $highlight): ?>
                            <li><?= e($highlight) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if (!empty($specItems)): ?>
                    <div class="product-detail__specs">
                        <?php foreach ($specItems as $spec): ?>
                            <div>
                                <span class="spec-label"><?= e($spec['label']) ?></span>
                                <span class="spec-value"><?= e($spec['value']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="product-detail__cta">
                    <a
                        class="btn-primary"
                        href="<?= e($shopeeUrl) ?>"
                        target="_blank"
                        rel="noopener"
                        data-track-cta="shopee"
                        data-product-id="<?= $productId !== null ? $productId : '' ?>"
                        data-product-slug="<?= e($productSlug) ?>"
                        data-product-name="<?= e($productName) ?>"
                        data-cta-label="Beli via Shopee"
                    >
                        Beli via Shopee
                    </a>
                    <a
                        class="btn-secondary"
                        href="<?= e($tokopediaUrl) ?>"
                        target="_blank"
                        rel="noopener"
                        data-track-cta="tokopedia"
                        data-product-id="<?= $productId !== null ? $productId : '' ?>"
                        data-product-slug="<?= e($productSlug) ?>"
                        data-product-name="<?= e($productName) ?>"
                        data-cta-label="Beli via Tokopedia"
                    >
                        Beli via Tokopedia
                    </a>
                    <a
                        class="btn-primary btn-primary--outline"
                        href="<?= e($whatsappUrl) ?>"
                        target="_blank"
                        rel="noopener"
                        data-track-cta="whatsapp"
                        data-product-id="<?= $productId !== null ? $productId : '' ?>"
                        data-product-slug="<?= e($productSlug) ?>"
                        data-product-name="<?= e($productName) ?>"
                        data-cta-label="Konsultasi WhatsApp"
                    >
                        Konsultasi WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="product-detail-body" data-animate="fade-up">
        <div class="container product-detail-body__grid">
            <article class="product-detail__description" data-animate="rise">
                <?php
                // Get text from database or default, then replace placeholder
                $descriptionHeadingText = get_content_section('product-detail', 'description-heading', 'Kenapa {product_name}?', null, null, null);
                if ($descriptionHeadingText === '') {
                    $descriptionHeadingText = 'Kenapa {product_name}?';
                }
                // Replace placeholder with actual product name
                $descriptionHeadingText = str_replace('{product_name}', $productData['name'] ?? '', $descriptionHeadingText);
                // Wrap with h2 tag
                echo '<h2>' . e($descriptionHeadingText) . '</h2>';
                ?>
                <?php if (!empty($productData['description'])): ?>
                    <?= $productData['description'] ?>
                <?php else: ?>
                    <p>Hubungi tim kami untuk mendapatkan detail teknis produk.</p>
                <?php endif; ?>
                <?php if (!empty($summaryItems)): ?>
                    <div class="product-detail__list">
                        <?php if (($summaryBlock['title'] ?? '') !== ''): ?>
                            <h3><?= e($summaryBlock['title']) ?><?= strpos($summaryBlock['title'], ':') === false ? ':' : '' ?></h3>
                        <?php endif; ?>
                        <ul>
                            <?php foreach ($summaryItems as $point): ?>
                                <li><?= e($point['label']) ?> <?= $point['value'] !== '' ? e('— ' . $point['value']) : '' ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </article>

            <aside class="product-detail__sidebar" data-animate="fade-left">
                <div class="product-summary-card hover-glow">
                    <h3><?= e($specBlock['title'] ?? 'Rangkuman Teknis') ?></h3>
                    <?php if (!empty($specItems)): ?>
                        <dl>
                            <?php foreach ($specItems as $spec): ?>
                                <div>
                                    <dt><?= e($spec['label']) ?></dt>
                                    <dd><?= e($spec['value']) ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    <?php else: ?>
                        <p>Rangkuman teknis segera tersedia.</p>
                    <?php endif; ?>
                    <a class="product-summary-card__cta" href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener">
                        Konsultasi Project →
                    </a>
                </div>
                <?php if (!empty($documentItems)): ?>
                    <div class="product-download-card hover-glow" data-animate="tilt-up">
                        <h3><?= e($documentsBlock['title'] ?? 'Dokumen Pendukung') ?></h3>
                        <ul>
                            <?php foreach ($documentItems as $document): ?>
                                <li><a href="<?= e($document['url']) ?>" class="product-download-link" target="_blank" rel="noopener"><?= e($document['title']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </section>

    <?php if (!empty($related)): ?>
        <section class="product-related" data-animate="fade-up">
            <div class="container">
                <div class="section-head" data-animate="fade-up">
                    <?= get_content_section('product-detail', 'related-kicker', 'Produk Terkait', 'span', 'section-kicker') ?>
                    <?= get_content_section('product-detail', 'related-heading', 'Lengkapi Workflow Waterproofing Kamu', 'h2') ?>
                    <?= get_content_section('product-detail', 'related-description', 'Mix and match material pendukung supaya hasil lapisan makin rapat dan tahan lama.', 'p') ?>
                </div>
                <div class="product-related__grid">
                    <?php foreach ($related as $index => $item): ?>
                        <?php $delay = $index * 80; ?>
                        <article class="product-card hover-glow" data-animate="tilt-up"<?= $delay ? ' data-animate-delay="' . e((string) $delay) . '"' : '' ?> >
                            <div class="product-icon">
                                <img src="<?= e($item['cover_image_url'] ?? asset('images/xolabit-bitumen.png')) ?>" alt="<?= e($item['name']) ?>">
                            </div>
                            <div class="card-content product-content">
                                <h3><?= e($item['name']) ?></h3>
                                <a href="<?= '/produk/' . e($item['slug']) ?>" class="product-link">Lihat detail</a>
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
                <?= get_content_section('product-detail', 'cta-badge', 'Siap bersinergi', 'span', 'cta-badge') ?>
                <?= get_content_section('product-detail', 'cta-heading', 'Butuh Volume Project Lebih Besar?', 'h2', 'cta-title') ?>
                <?= get_content_section('product-detail', 'cta-description', 'Tim engineer kami siap bantu survei, rekomendasi formulasi, hingga eksekusi lapangan. Konsultasikan kebutuhan project kamu sekarang juga.', 'p', 'cta-text') ?>
                <div class="cta-actions">
                    <a href="/#hubungi" class="btn-primary">Hubungi Kami</a>
                    <a href="/#galeri" class="btn-secondary">Lihat Portofolio</a>
                </div>
                <div class="" style="margin-top: -200px;">
                    <div class="cta-glow"></div>
                    <img src="<?= asset('images/peta-indo.png') ?>" alt="Peta Indonesia" width="100%">
                </div>
                <?= get_content_section('product-detail', 'coverage-text', 'Pengiriman & Pengerjaan Seluruh Indonesia', 'p', 'coverage-text', 'margin-top: -136px; text-align: center;') ?>
            </div>
        </div>
    </section>
</main>

<?php
// Product Schema for detail page
if (!empty($productData)) {
    $productSchema = schema_product($productData, url());
    // Enhance with additional details
    $productSchema['description'] = $productData['description'] ?? $productData['short_description'] ?? '';
    
    if (!empty($specItems)) {
        $productSchema['additionalProperty'] = array_map(function($spec) {
            return [
                '@type' => 'PropertyValue',
                'name' => $spec['label'],
                'value' => $spec['value'],
            ];
        }, $specItems);
    }
    
    if (!empty($highlightItems)) {
        $productSchema['featureList'] = $highlightItems;
    }
    
    echo PHP_EOL . '<!-- Product Schema -->' . PHP_EOL;
    echo schema_json_ld($productSchema) . PHP_EOL;
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
            'name' => 'Produk',
            'item' => url('/produk'),
        ],
        [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $productData['name'] ?? 'Detail Produk',
            'item' => url('/produk/' . ($productData['slug'] ?? '')),
        ],
    ],
]) . PHP_EOL;
?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buttons = document.querySelectorAll('[data-track-cta]');

        if (!buttons.length) {
            return;
        }

        const endpoint = '/api/track/product-click';

        const sendPayload = function (payload) {
            try {
                const body = JSON.stringify(payload);

                if (navigator.sendBeacon) {
                    const blob = new Blob([body], { type: 'application/json' });
                    navigator.sendBeacon(endpoint, blob);
                    return;
                }

                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body,
                    keepalive: true,
                }).catch(function () {
                    /* silent */
                });
            } catch (error) {
                console.error('CTA tracking failed', error);
            }
        };

        const parseNumber = function (value) {
            if (!value) {
                return null;
            }

            const numeric = Number(value);

            return Number.isFinite(numeric) ? numeric : null;
        };

        buttons.forEach(function (button) {
            button.addEventListener(
                'click',
                function () {
                    const dataset = button.dataset;

                    const payload = {
                        product_id: parseNumber(dataset.productId),
                        product_slug: dataset.productSlug || null,
                        product_name: dataset.productName || null,
                        cta_channel: (dataset.trackCta || dataset.ctaChannel || '').toLowerCase(),
                        cta_label: dataset.ctaLabel || button.textContent.trim(),
                        target_url: button.getAttribute('href'),
                        page_url: window.location.href,
                        referrer: document.referrer || null,
                        client_event_time: new Date().toISOString(),
                    };

                    sendPayload(payload);
                },
                { passive: true }
            );
        });
    });
</script>
