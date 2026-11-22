-- Migration: Create content_sections table
-- Description: Table untuk menyimpan konten section yang bisa di-customize per halaman
-- Date: 2024

CREATE TABLE IF NOT EXISTS content_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_identifier VARCHAR(100) NOT NULL COMMENT 'Identifier halaman (e.g., blog-detail, home)',
    section_key VARCHAR(100) NOT NULL COMMENT 'Key section (e.g., workflow-heading, services-heading)',
    content_html LONGTEXT NULL COMMENT 'HTML content untuk section',
    description VARCHAR(255) NULL COMMENT 'Deskripsi section (optional)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_page_section (page_identifier, section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contoh data default (optional)
-- INSERT INTO content_sections (page_identifier, section_key, content_html, description) VALUES
-- ('blog-detail', 'workflow-heading', '<h2>Workflow Eksekusi</h2>', 'Heading untuk section timeline/workflow'),
-- ('blog-detail', 'highlights-heading', '<h2>Highlights Project</h2>', 'Heading untuk section statistik/highlights'),
-- ('blog-detail', 'checklist-heading', '<h2>Checklist Maintenance</h2>', 'Heading untuk section checklist'),
-- ('blog-detail', 'toc-heading', '<h2>Isi Artikel</h2>', 'Heading untuk table of contents'),
-- ('home', 'services-heading', '<h2>Solusi Terintegrasi Aspal Emulsi &amp; Waterproofing</h2>', 'Heading untuk section layanan'),
-- ('home', 'services-description', '<p>Kami tangani dari preparasi permukaan hingga finishing akhir dengan formulasi emulsi pilihan, memastikan daya lekat maksimum plus ketahanan cuaca ekstrem.</p>', 'Deskripsi untuk section layanan'),
-- ('home', 'products-heading', '<h2>Solusi Produk Aspal Untuk Workflow Pengerjaan Lebih Mulus</h2>', 'Heading untuk section produk'),
-- ('home', 'products-description', '<p class="products-subtitle">Formulasi emulsi kami siap pakai untuk berbagai skenario konstruksi jalan, jembatan, dan bangunan. Tingkatkan kecepatan pengerjaan tanpa mengorbankan mutu lapisan.</p>', 'Deskripsi untuk section produk'),
-- ('home', 'gallery-heading', '<h2 class="gallery-title">Gallery Pengerjaan</h2>', 'Heading untuk section gallery'),
-- ('home', 'gallery-description', '<p class="gallery-intro">Pengerjaan dilakukan dengan standar keamanan, menggunakan bahan baku berkualitas dan tenaga kerja berpengalaman, sehingga menghasilkan lapisan yang kuat, teruji, dan tahan lama.</p>', 'Deskripsi untuk section gallery'),
-- ('home', 'blog-heading', '<h2>Insight Teknis &amp; Tips Perawatan Permukaan</h2>', 'Heading untuk section blog'),
-- ('home', 'blog-description', '<p>Update terbaru dari tim engineer kami seputar teknologi emulsi, manajemen pemeliharaan jalan, serta strategi efisiensi supaya proyek kamu lebih presisi dan mudah di-maintain.</p>', 'Deskripsi untuk section blog'),
-- ('home', 'testimonials-heading', '<h2>Partner Kami Bicara Soal Hasil Akhir</h2>', 'Heading untuk section testimonials'),
-- ('home', 'testimonials-description', '<p>Dari akses kampus, kawasan industri sampai jalur logistik, mereka percaya sama kualitas layer dan servis aftersales kami.</p>', 'Deskripsi untuk section testimonials');

