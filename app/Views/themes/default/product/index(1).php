<?php
$productList = $products ?? [];
?>
<main class="products-index" id="produk">
    <section class="products" data-animate="fade-up">
        <div class="container">
            <div class="products-inner" data-animate="fade-up">
                <div class="products-badge">
                    <span>★★★★★</span>
                    <span>Dipercaya klien proyek di seluruh Indonesia</span>
                </div>
                <h1>Katalog Produk Aspal Emulsi</h1>
                <p class="products-subtitle">
                    Pilih produk sesuai kebutuhan proyek jalan, waterproofing, dan infrastruktur kamu.
                </p>
            </div>

            <div class="product-grid">
                <?php
                // Generate Product schemas for ItemList
                $productSchemas = [];
                foreach ($productList as $product) {
                    $productSchemas[] = schema_product($product, url());
                }
                ?>
                <?php foreach ($productList as $index => $item): ?>
                    <?php $delay = $index * 60; ?>
                    <article class="product-card hover-glow" data-animate="tilt-up"<?= $delay ? ' data-animate-delay="' . e((string) $delay) . '"' : '' ?> >
                        <div class="product-icon">
                            <img src="<?= e($item['cover_image_url'] ?? asset('images/colabit-seal-coat.png')) ?>" alt="<?= e($item['name']) ?>">
                        </div>
                        <div class="card-content product-content">
                            <h3><?= e($item['name']) ?></h3>
                            <p><?= e(excerpt_text($item['short_description'] ?? '', 140)) ?></p>
                            <a href="<?= '/produk/' . e($item['slug']) ?>" class="product-link">Lihat detail</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php
// ItemList Schema for products collection
if (!empty($productSchemas ?? [])) {
    echo PHP_EOL . '<!-- ItemList Schema for Products -->' . PHP_EOL;
    echo schema_json_ld([
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Katalog Produk Aspal Emulsi',
        'description' => 'Koleksi produk aspal emulsi untuk proyek jalan, waterproofing, dan infrastruktur',
        'itemListElement' => array_map(function ($index, $product) {
            return [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => $product,
            ];
        }, array_keys($productSchemas), $productSchemas),
    ]) . PHP_EOL;
    
    // Also output individual product schemas
    echo PHP_EOL . '<!-- Individual Product Schemas -->' . PHP_EOL;
    foreach ($productSchemas as $schema) {
        echo schema_json_ld($schema) . PHP_EOL;
    }
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
    ],
]) . PHP_EOL;
?>
