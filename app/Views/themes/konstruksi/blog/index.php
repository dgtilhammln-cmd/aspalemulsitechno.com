<?php
$siteSettings = $siteSettings ?? [];
$posts = $posts ?? [];
$featuredPost = $featuredPost ?? null;
$listPosts = $listPosts ?? [];
$availableTags = $availableTags ?? [];
$activeTag = $activeTag ?? null;
$pagination = $pagination ?? [
    'current' => 1,
    'total' => 1,
    'perPage' => count($posts),
    'totalItems' => count($posts),
    'tagSlug' => null,
];

$hasPosts = $featuredPost !== null || !empty($listPosts);
$allPostsForSchema = $posts;
$activeTagSlug = $pagination['tagSlug'] ?? null;

$whatsappNumber = $siteSettings['whatsapp_number'] ?? null;
$ctaWhatsapp = $siteSettings['whatsapp_url'] ?? (
    $whatsappNumber
        ? 'https://wa.me/' . preg_replace('/\D+/', '', (string) $whatsappNumber)
        : 'https://wa.me/6281234567890'
);

$ctaLabel = trim(strip_tags(get_content_section('blog-index', 'hero-cta-label', 'Ngobrol dengan Engineer')));
$filterHeading = get_content_section('blog-index', 'topics-heading', 'Pilih topik artikel', 'span', 'section-kicker section-kicker--subtle');
$filterDescription = get_content_section('blog-index', 'topics-description', 'Filter insight berdasarkan tag yang kamu butuhkan.', 'p', 'blog-topics__description');

$buildPageUrl = static function (int $page, ?string $tagSlug = null): string {
    $params = [];
    if ($tagSlug) {
        $params['tag'] = $tagSlug;
    }
    if ($page > 1) {
        $params['page'] = $page;
    }
    $query = $params ? '?' . http_build_query($params) : '';

    return url('/blog' . $query);
};
?>
<main class="blog-index">
    <section class="blog-index__hero" data-animate="fade-up">
        <div class="container blog-index__hero-inner">
            <div class="blog-index__hero-copy" data-animate="fade-right">
                <?= get_content_section('blog-index', 'hero-kicker', 'Wawasan Lapangan', 'span', 'section-kicker') ?>
                <?= get_content_section('blog-index', 'hero-heading', 'Artikel & Insight Terbaru', 'h1', 'blog-index__title') ?>
                <?= get_content_section('blog-index', 'hero-description', 'Kompilasi insight teknis, studi kasus, dan best practice lapangan langsung dari tim engineer Aspal Emulsi.', 'p', 'blog-index__subtitle') ?>
                <div class="blog-index__cta">
                    <a class="btn btn-primary" href="<?= e($ctaWhatsapp) ?>" target="_blank" rel="noopener" data-market-channel="whatsapp" data-market-context="blog-index-hero" data-market-label="<?= e($ctaLabel ?: 'Ngobrol dengan Engineer') ?>" data-market-page="blog-index">
                        <span><?= e($ctaLabel ?: 'Ngobrol dengan Engineer') ?></span>
                        <svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill="currentColor" d="M13.172 12l-4.95-4.95a1 1 0 0 1 1.414-1.414L16 12l-6.364 6.364a1 1 0 0 1-1.414-1.414z"/>
                        </svg>
                    </a>
                    <div class="blog-index__meta">
                        <div class="blog-index__meta-item">
                            <span class="blog-index__meta-label">Total Artikel</span>
                            <strong><?= number_format((int) ($pagination['totalItems'] ?? 0)) ?></strong>
                        </div>
                        <div class="blog-index__meta-item">
                            <span class="blog-index__meta-label">Topik</span>
                            <strong><?= number_format(count($availableTags)) ?></strong>
                        </div>
                        <?php if (($allPostsForSchema[0]['published_label'] ?? null) !== null): ?>
                            <div class="blog-index__meta-item">
                                <span class="blog-index__meta-label">Update Terakhir</span>
                                <strong><?= e($allPostsForSchema[0]['published_label']) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if ($featuredPost): ?>
                <article class="blog-card blog-card--featured hover-glow" data-animate="tilt-up">
                    <a class="blog-card__cover" href="<?= e('/blog/' . $featuredPost['slug']) ?>" aria-label="<?= e($featuredPost['title']) ?>">
                        <img src="<?= e($featuredPost['cover_image_url'] ?? asset('images/blog-emulsi.jpg')) ?>" alt="<?= e($featuredPost['title']) ?>">
                        <span class="blog-card__badge">Artikel Unggulan</span>
                    </a>
                    <div class="blog-card__content">
                        <div class="blog-card__tags">
                            <span><?= e($featuredPost['hero_tag'] ?? 'Insight') ?></span>
                            <?php if (!empty($featuredPost['hero_reading_time'])): ?>
                                <span><?= e($featuredPost['hero_reading_time']) ?></span>
                            <?php endif; ?>
                        </div>
                        <h2 class="blog-card__title">
                            <a href="<?= e('/blog/' . $featuredPost['slug']) ?>"><?= e($featuredPost['title']) ?></a>
                        </h2>
                        <?php if (!empty($featuredPost['summary'])): ?>
                            <p class="blog-card__excerpt"><?= e(excerpt_text((string) $featuredPost['summary'], 180)) ?></p>
                        <?php endif; ?>
                        <div class="blog-card__footer">
                            <div class="blog-card__meta">
                                <?php if (!empty($featuredPost['author'])): ?>
                                    <span><?= e($featuredPost['author']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($featuredPost['published_label'])): ?>
                                    <span><?= e($featuredPost['published_label']) ?></span>
                                <?php endif; ?>
                            </div>
                            <a class="blog-card__link" href="<?= e('/blog/' . $featuredPost['slug']) ?>" aria-label="Baca <?= e($featuredPost['title']) ?>">
                                Baca artikel
                                <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill="currentColor" d="M5 12h12M13 6l6 6-6 6"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($availableTags)): ?>
        <section class="blog-topics" aria-label="Filter artikel berdasarkan topik">
            <div class="container">
                <div class="blog-topics__head" data-animate="fade-up">
                    <?= $filterHeading ?>
                    <?= $filterDescription ?>
                </div>
                <div class="blog-topics__chips" data-animate="fade-up">
                    <a class="blog-topics__chip<?= $activeTag === null ? ' is-active' : '' ?>" href="<?= e(url('/blog')) ?>">
                        Semua
                        <span><?= number_format((int) ($pagination['totalItems'] ?? 0)) ?></span>
                    </a>
                    <?php foreach ($availableTags as $tag): ?>
                        <a class="blog-topics__chip<?= $activeTag && $activeTag['slug'] === $tag['slug'] ? ' is-active' : '' ?>"
                           href="<?= e($buildPageUrl(1, $tag['slug'])) ?>">
                            <?= e($tag['name']) ?>
                            <span><?= number_format((int) $tag['total']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="blog-index__list" data-animate="fade-up">
        <div class="container">
            <?php if ($hasPosts): ?>
                <div class="blog-grid">
                    <?php foreach ($listPosts as $index => $post): ?>
                        <article class="blog-card<?= $index % 2 === 0 ? ' hover-glow' : '' ?>" data-animate="tilt-up" data-animate-delay="<?= e((string) ($index * 60)) ?>">
                            <a class="blog-card__cover" href="<?= e('/blog/' . $post['slug']) ?>" aria-label="<?= e($post['title']) ?>">
                                <img src="<?= e($post['cover_image_url'] ?? asset('images/blog-emulsi.jpg')) ?>" alt="<?= e($post['title']) ?>">
                            </a>
                            <div class="blog-card__content">
                                <div class="blog-card__tags">
                                    <span><?= e($post['hero_tag'] ?? 'Insight') ?></span>
                                    <?php if (!empty($post['hero_reading_time'])): ?>
                                        <span><?= e($post['hero_reading_time']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="blog-card__title">
                                    <a href="<?= e('/blog/' . $post['slug']) ?>"><?= e($post['title']) ?></a>
                                </h3>
                                <?php if (!empty($post['summary'])): ?>
                                    <p class="blog-card__excerpt"><?= e(excerpt_text((string) $post['summary'], 150)) ?></p>
                                <?php endif; ?>
                                <div class="blog-card__footer">
                                    <div class="blog-card__meta">
                                        <?php if (!empty($post['author'])): ?>
                                            <span><?= e($post['author']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($post['published_label'])): ?>
                                            <span><?= e($post['published_label']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <a class="blog-card__link" href="<?= e('/blog/' . $post['slug']) ?>" aria-label="Baca <?= e($post['title']) ?>">
                                        Baca artikel
                                        <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true">
                                            <path fill="currentColor" d="M5 12h12M13 6l6 6-6 6"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if (($pagination['total'] ?? 1) > 1): ?>
                    <?php
                    $current = (int) ($pagination['current'] ?? 1);
                    $total = (int) ($pagination['total'] ?? 1);
                    $window = 2;
                    $start = max(1, $current - $window);
                    $end = min($total, $current + $window);
                    ?>
                    <nav class="blog-pagination" aria-label="Navigasi halaman blog">
                        <?php if (!empty($pagination['hasPrevious'])): ?>
                            <a class="blog-pagination__nav" href="<?= e($pagination['previousUrl']) ?>" rel="prev" aria-label="Halaman sebelumnya">
                                <span>Previous</span>
                            </a>
                        <?php endif; ?>

                        <div class="blog-pagination__pages">
                            <?php if ($start > 1): ?>
                                <a class="blog-pagination__page<?= $current === 1 ? ' is-active' : '' ?>" href="<?= e($buildPageUrl(1, $activeTagSlug)) ?>">1</a>
                                <?php if ($start > 2): ?>
                                    <span class="blog-pagination__ellipsis" aria-hidden="true">…</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($page = $start; $page <= $end; $page++): ?>
                                <a class="blog-pagination__page<?= $current === $page ? ' is-active' : '' ?>" href="<?= e($buildPageUrl($page, $activeTagSlug)) ?>"<?= $current === $page ? ' aria-current="page"' : '' ?>>
                                    <?= $page ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($end < $total): ?>
                                <?php if ($end < $total - 1): ?>
                                    <span class="blog-pagination__ellipsis" aria-hidden="true">…</span>
                                <?php endif; ?>
                                <a class="blog-pagination__page<?= $current === $total ? ' is-active' : '' ?>" href="<?= e($buildPageUrl($total, $activeTagSlug)) ?>"><?= $total ?></a>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($pagination['hasNext'])): ?>
                            <a class="blog-pagination__nav" href="<?= e($pagination['nextUrl']) ?>" rel="next" aria-label="Halaman selanjutnya">
                                <span>Next</span>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="blog-empty-state" data-animate="fade-up">
                    <?= get_content_section('blog-index', 'empty-heading', 'Konten segera hadir!', 'h2') ?>
                    <?= get_content_section('blog-index', 'empty-description', 'Kami sedang menyiapkan insight terbaru. Pantau terus blog Aspal Emulsi untuk update project dan studi kasus menarik.', 'p') ?>
                    <a class="btn btn-primary" href="<?= e($ctaWhatsapp) ?>" target="_blank" rel="noopener" data-market-channel="whatsapp" data-market-context="blog-index-empty" data-market-label="Hubungi Engineer" data-market-page="blog-index">
                        Hubungi Engineer
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
if (!empty($allPostsForSchema)) {
    $blogSchemas = array_map(
        static fn (array $post) => schema_blog_posting($post, url()),
        $allPostsForSchema
    );

    echo PHP_EOL . '<!-- ItemList Schema for Blog Posts -->' . PHP_EOL;
    echo schema_json_ld([
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Artikel & Insight Terbaru',
        'description' => 'Kompilasi konten teknis dari tim engineer Aspal Emulsi',
        'itemListElement' => array_map(
            static function (int $index, array $postSchema): array {
                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'item' => $postSchema,
                ];
            },
            array_keys($blogSchemas),
            $blogSchemas
        ),
    ]) . PHP_EOL;

    echo PHP_EOL . '<!-- Individual BlogPosting Schemas -->' . PHP_EOL;
    foreach ($blogSchemas as $schema) {
        echo schema_json_ld($schema) . PHP_EOL;
    }
}

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
    ],
]) . PHP_EOL;
?>
