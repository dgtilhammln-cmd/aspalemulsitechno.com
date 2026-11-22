<?php
$productList = $products ?? [];
?>

<!-- Import Font Modern Premium -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --lux-orange: linear-gradient(180deg, #FFB800 0%, #FF7A00 100%);
        --lux-black: #0F0F0F;
        --lux-inner: #1A1A1A;
        --lux-gold: #FFB800;
        --radius-outer: 50px;
        --radius-inner: 40px;
    }

    .products-index {
        background-color: #000;
        font-family: 'Plus Jakarta Sans', sans-serif;
        padding: 100px 0;
        overflow: hidden;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 25px;
    }

    /* Header Styling */
    .products-inner {
        text-align: center;
        margin-bottom: 120px; /* Space besar untuk elemen floating di bawah */
    }

    .products-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 184, 0, 0.1);
        padding: 8px 20px;
        border-radius: 100px;
        border: 1px solid rgba(255, 184, 0, 0.2);
        margin-bottom: 25px;
    }

    .products-badge span {
        color: var(--lux-gold);
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .products-index h1 {
        font-size: clamp(2.5rem, 5vw, 3.5rem);
        font-weight: 800;
        color: #fff;
        margin: 0;
        letter-spacing: -2px;
        line-height: 1.1;
    }

    /* Grid System */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 80px 35px; /* Gap vertikal besar untuk space pop-out image */
    }

    /* THE PREMIUM CARD */
    .premium-card-outer {
        background: var(--lux-orange);
        border-radius: var(--radius-outer);
        padding: 15px; /* Ketebalan bingkai orange */
        position: relative;
        display: flex;
        flex-direction: column;
        transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        text-decoration: none;
        min-height: 520px;
    }

    .premium-card-outer:hover {
        transform: translateY(-15px) scale(1.02);
        box-shadow: 0 30px 60px rgba(255, 122, 0, 0.4);
    }

    /* Floating Product Image */
    .product-popout-img {
        position: absolute;
        top: -70px; /* Nongol ke atas */
        left: 50%;
        transform: translateX(-50%);
        width: 85%;
        height: 250px;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .product-popout-img img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        filter: drop-shadow(0 20px 30px rgba(0,0,0,0.5));
    }

    .premium-card-outer:hover .product-popout-img {
        transform: translateX(-50%) scale(1.1) translateY(-10px);
    }

    /* Inner Dark Content Box */
    .premium-card-inner {
        background-color: var(--lux-inner);
        border-radius: var(--radius-inner);
        padding: 180px 30px 40px 30px; /* Padding atas besar supaya teks tidak tertutup gambar */
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        text-align: center;
        justify-content: space-between;
        border: 1px solid rgba(255,255,255,0.05);
    }

    .premium-card-inner h3 {
        color: #fff;
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 15px;
        line-height: 1.2;
        text-transform: uppercase;
        letter-spacing: -0.5px;
    }

    .premium-card-inner p {
        color: #999;
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 25px;
    }

    /* Canggih Detail Button */
    .lux-detail-btn {
        color: var(--lux-gold);
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: gap 0.3s ease;
    }

    .premium-card-outer:hover .lux-detail-btn {
        gap: 20px;
    }

    /* Mobile Friendly & Responsive */
    @media (max-width: 1100px) {
        .product-grid { grid-template-columns: repeat(2, 1fr); gap: 100px 30px; }
    }

    @media (max-width: 768px) {
        .products-inner { margin-bottom: 80px; }
        .product-grid { 
            grid-template-columns: 1fr; 
            gap: 120px; 
            padding-top: 50px;
        }
        .premium-card-outer { min-height: 480px; }
        .premium-card-inner { padding-top: 150px; }
    }
</style>

<main class="products-index" id="produk">
    <section class="products" data-animate="fade-up">
        <div class="container">
            <div class="products-inner" data-animate="fade-up">
                <div class="products-badge">
                    <span>★★★★★ Industrial Grade</span>
                </div>
                <h1>Katalog Produk<br>Aspal Emulsi</h1>
            </div>

            <div class="product-grid">
                <?php
                // Logic Schema Tetap Aman
                $productSchemas = [];
                foreach ($productList as $product) {
                    $productSchemas[] = schema_product($product, url());
                }
                ?>
                
                <?php foreach ($productList as $index => $item): ?>
                    <?php $delay = $index * 100; ?>
                    <a href="<?= '/produk/' . e($item['slug']) ?>" 
                       class="premium-card-outer" 
                       data-animate="fade-up" 
                       <?= $delay ? ' data-animate-delay="' . e((string) $delay) . '"' : '' ?>>
                        
                        <!-- Floating Product Image Layer -->
                        <div class="product-popout-img">
                            <img src="<?= e($item['cover_image_url'] ?? asset('images/colabit-seal-coat.png')) ?>" 
                                 alt="<?= e($item['name']) ?>"
                                 loading="lazy">
                        </div>

                        <!-- Inner Content Box -->
                        <div class="premium-card-inner">
                            <div class="card-text">
                                <h3><?= e($item['name']) ?></h3>
                                <p><?= e(excerpt_text($item['short_description'] ?? '', 110)) ?></p>
                            </div>
                            <div class="lux-detail-btn">
                                Lihat Detail <span>&rarr;</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php
/**
 * SEMUA LOGIKA SCHEMA & SEO DI BAWAH INI TIDAK DIRUBAH
 * Agar fungsi framework tetap jalan 100%
 */
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