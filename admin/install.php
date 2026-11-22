<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

try {
    $pdo = getPDO();

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS roles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            role_id INT UNSIGNED NULL,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(120) UNIQUE,
            full_name VARCHAR(120) NULL,
            password_hash VARCHAR(255) NOT NULL,
            status ENUM("active", "inactive", "suspended") DEFAULT "active",
            last_login_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_users_roles FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS blog_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(120) NOT NULL UNIQUE,
            name VARCHAR(150) NOT NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS blog_posts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            author_id INT UNSIGNED NULL,
            title VARCHAR(200) NOT NULL,
            slug VARCHAR(220) NOT NULL UNIQUE,
            hero_tag VARCHAR(100) NULL,
            hero_author_override VARCHAR(150) NULL,
            hero_reading_time VARCHAR(60) NULL,
            summary TEXT NULL,
            content LONGTEXT NULL,
            cover_image_url VARCHAR(255) NULL,
            cover_image_alt VARCHAR(255) NULL,
            intro_quote TEXT NULL,
            intro_quote_cite VARCHAR(180) NULL,
            table_of_contents_json TEXT NULL,
            timeline_json TEXT NULL,
            stat_blocks_json TEXT NULL,
            checklist_json TEXT NULL,
            cta_heading VARCHAR(180) NULL,
            cta_subheading TEXT NULL,
            cta_shopee_url VARCHAR(255) NULL,
            cta_tokopedia_url VARCHAR(255) NULL,
            cta_whatsapp_url VARCHAR(255) NULL,
            status ENUM("draft", "published", "archived") DEFAULT "draft",
            published_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_blog_posts_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $blogPostColumns = [
        'hero_tag' => 'ALTER TABLE blog_posts ADD COLUMN hero_tag VARCHAR(100) NULL AFTER slug',
        'hero_author_override' => 'ALTER TABLE blog_posts ADD COLUMN hero_author_override VARCHAR(150) NULL AFTER hero_tag',
        'hero_reading_time' => 'ALTER TABLE blog_posts ADD COLUMN hero_reading_time VARCHAR(60) NULL AFTER hero_author_override',
        'cover_image_alt' => 'ALTER TABLE blog_posts ADD COLUMN cover_image_alt VARCHAR(255) NULL AFTER cover_image_url',
        'intro_quote' => 'ALTER TABLE blog_posts ADD COLUMN intro_quote TEXT NULL AFTER cover_image_alt',
        'intro_quote_cite' => 'ALTER TABLE blog_posts ADD COLUMN intro_quote_cite VARCHAR(180) NULL AFTER intro_quote',
        'table_of_contents_json' => 'ALTER TABLE blog_posts ADD COLUMN table_of_contents_json TEXT NULL AFTER intro_quote_cite',
        'timeline_json' => 'ALTER TABLE blog_posts ADD COLUMN timeline_json TEXT NULL AFTER table_of_contents_json',
        'stat_blocks_json' => 'ALTER TABLE blog_posts ADD COLUMN stat_blocks_json TEXT NULL AFTER timeline_json',
        'checklist_json' => 'ALTER TABLE blog_posts ADD COLUMN checklist_json TEXT NULL AFTER stat_blocks_json',
        'cta_heading' => 'ALTER TABLE blog_posts ADD COLUMN cta_heading VARCHAR(180) NULL AFTER checklist_json',
        'cta_subheading' => 'ALTER TABLE blog_posts ADD COLUMN cta_subheading TEXT NULL AFTER cta_heading',
        'cta_shopee_url' => 'ALTER TABLE blog_posts ADD COLUMN cta_shopee_url VARCHAR(255) NULL AFTER cta_subheading',
        'cta_tokopedia_url' => 'ALTER TABLE blog_posts ADD COLUMN cta_tokopedia_url VARCHAR(255) NULL AFTER cta_shopee_url',
        'cta_whatsapp_url' => 'ALTER TABLE blog_posts ADD COLUMN cta_whatsapp_url VARCHAR(255) NULL AFTER cta_tokopedia_url',
    ];

    foreach ($blogPostColumns as $column => $ddl) {
        try {
            $exists = $pdo->query(sprintf("SHOW COLUMNS FROM blog_posts LIKE '%s'", $column))->fetch();
            if (!$exists) {
                $pdo->exec($ddl);
            }
        } catch (Throwable $ignored) {
            // ignore migration conflicts
        }
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS blog_post_categories (
            post_id INT UNSIGNED NOT NULL,
            category_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (post_id, category_id),
            CONSTRAINT fk_bpc_post FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
            CONSTRAINT fk_bpc_category FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS seo_meta (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            owner_type VARCHAR(30) NOT NULL,
            owner_id INT UNSIGNED NOT NULL,
            meta_title VARCHAR(255) NULL,
            meta_description TEXT NULL,
            meta_keywords VARCHAR(255) NULL,
            og_title VARCHAR(255) NULL,
            og_description TEXT NULL,
            og_image_url VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_owner (owner_type, owner_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    try {
        $ownerTypeColumn = $pdo->query("SHOW COLUMNS FROM seo_meta LIKE 'owner_type'")->fetch();
        if ($ownerTypeColumn && stripos((string) $ownerTypeColumn['Type'], 'enum') !== false) {
            $pdo->exec('ALTER TABLE seo_meta MODIFY owner_type VARCHAR(30) NOT NULL');
        }
    } catch (Throwable $ignored) {
        // ignore migration errors, install will proceed
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS site_settings (
            id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(180) NOT NULL,
            tagline VARCHAR(255) NULL,
            meta_title VARCHAR(255) NULL,
            meta_description TEXT NULL,
            meta_keywords VARCHAR(255) NULL,
            meta_robots VARCHAR(255) NULL,
            company_email VARCHAR(150) NULL,
            company_phone VARCHAR(50) NULL,
            whatsapp_number VARCHAR(50) NULL,
            address TEXT NULL,
            map_embed_url TEXT NULL,
            working_hours TEXT NULL,
            shopee_url VARCHAR(255) NULL,
            tokopedia_url VARCHAR(255) NULL,
            facebook_url VARCHAR(255) NULL,
            instagram_url VARCHAR(255) NULL,
            linkedin_url VARCHAR(255) NULL,
            youtube_url VARCHAR(255) NULL,
            tiktok_url VARCHAR(255) NULL,
            twitter_url VARCHAR(255) NULL,
            logo_url VARCHAR(255) NULL,
            favicon_url VARCHAR(255) NULL,
            updated_by INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_site_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS theme_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            active_theme VARCHAR(50) NOT NULL DEFAULT "default",
            theme_colors JSON NULL,
            updated_by INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active_theme (active_theme),
            CONSTRAINT fk_theme_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    // Insert default theme settings if not exists
    try {
        $existingTheme = $pdo->query('SELECT COUNT(*) FROM theme_settings')->fetchColumn();
        if ((int) $existingTheme === 0) {
            $pdo->exec(
                "INSERT INTO theme_settings (active_theme, theme_colors) 
                 VALUES ('default', '{}')"
            );
        }
    } catch (\Throwable $throwable) {
        error_log('Theme settings initialization failed: ' . $throwable->getMessage());
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS content_sections (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_identifier VARCHAR(100) NOT NULL,
            section_key VARCHAR(100) NOT NULL,
            content_html LONGTEXT NULL,
            description VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_page_section (page_identifier, section_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(180) NOT NULL,
            slug VARCHAR(200) NOT NULL UNIQUE,
            short_description TEXT NULL,
            description LONGTEXT NULL,
            cover_image_url VARCHAR(255) NULL,
            hero_badge VARCHAR(120) NULL,
            hero_eyebrow VARCHAR(180) NULL,
            highlights_json TEXT NULL,
            specs_json TEXT NULL,
            summary_json TEXT NULL,
            documents_json TEXT NULL,
            shopee_url VARCHAR(255) NULL,
            tokopedia_url VARCHAR(255) NULL,
            whatsapp_url VARCHAR(255) NULL,
            status ENUM("draft", "published", "archived") DEFAULT "draft",
            published_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $productColumns = [
        'hero_badge' => 'ALTER TABLE products ADD COLUMN hero_badge VARCHAR(120) NULL AFTER cover_image_url',
        'hero_eyebrow' => 'ALTER TABLE products ADD COLUMN hero_eyebrow VARCHAR(180) NULL AFTER hero_badge',
        'highlights_json' => 'ALTER TABLE products ADD COLUMN highlights_json TEXT NULL AFTER hero_eyebrow',
        'specs_json' => 'ALTER TABLE products ADD COLUMN specs_json TEXT NULL AFTER highlights_json',
        'summary_json' => 'ALTER TABLE products ADD COLUMN summary_json TEXT NULL AFTER specs_json',
        'documents_json' => 'ALTER TABLE products ADD COLUMN documents_json TEXT NULL AFTER summary_json',
        'whatsapp_url' => 'ALTER TABLE products ADD COLUMN whatsapp_url VARCHAR(255) NULL AFTER tokopedia_url',
    ];

    foreach ($productColumns as $column => $ddl) {
        try {
            $exists = $pdo->query(sprintf("SHOW COLUMNS FROM products LIKE '%s'", $column))->fetch();
            if (!$exists) {
                $pdo->exec($ddl);
            }
        } catch (Throwable $ignored) {
            // ignore migration issues
        }
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS services (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(180) NOT NULL,
            slug VARCHAR(200) NOT NULL UNIQUE,
            short_description TEXT NULL,
            description LONGTEXT NULL,
            cover_image_url VARCHAR(255) NULL,
            status ENUM("draft", "published", "archived") DEFAULT "draft",
            published_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS portfolio_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL UNIQUE,
            category VARCHAR(120) NULL,
            description TEXT NULL,
            image_url VARCHAR(255) NULL,
            location VARCHAR(150) NULL,
            featured TINYINT(1) DEFAULT 0,
            status ENUM("draft", "published") DEFAULT "published",
            showcased_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS testimonials (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_name VARCHAR(150) NOT NULL,
            company VARCHAR(150) NULL,
            role VARCHAR(150) NULL,
            quote TEXT NOT NULL,
            rating TINYINT UNSIGNED NULL,
            avatar_url VARCHAR(255) NULL,
            status ENUM("draft", "published") DEFAULT "published",
            published_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS product_click_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NULL,
            product_slug VARCHAR(200) NULL,
            product_name VARCHAR(180) NULL,
            cta_channel VARCHAR(120) NOT NULL,
            cta_label VARCHAR(180) NULL,
            target_url TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            device_type VARCHAR(50) NULL,
            browser VARCHAR(120) NULL,
            os VARCHAR(120) NULL,
            referrer_url TEXT NULL,
            utm_source VARCHAR(120) NULL,
            utm_medium VARCHAR(120) NULL,
            utm_campaign VARCHAR(120) NULL,
            country VARCHAR(120) NULL,
            region VARCHAR(120) NULL,
            city VARCHAR(120) NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            extra_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product_click_created (product_id, created_at),
            INDEX idx_channel_click_created (cta_channel, created_at),
            INDEX idx_click_country (country),
            INDEX idx_click_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS content_view_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            content_type VARCHAR(30) NOT NULL,
            content_id INT UNSIGNED NULL,
            content_slug VARCHAR(220) NULL,
            content_title VARCHAR(255) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            device_type VARCHAR(50) NULL,
            browser VARCHAR(120) NULL,
            os VARCHAR(120) NULL,
            referrer_url TEXT NULL,
            utm_source VARCHAR(120) NULL,
            utm_medium VARCHAR(120) NULL,
            utm_campaign VARCHAR(120) NULL,
            country VARCHAR(120) NULL,
            region VARCHAR(120) NULL,
            city VARCHAR(120) NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            extra_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_content_type_created (content_type, created_at),
            INDEX idx_content_id_created (content_id, created_at),
            INDEX idx_content_country (country),
            INDEX idx_content_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS marketplace_click_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            channel VARCHAR(120) NOT NULL,
            source_page VARCHAR(60) NULL,
            source_context VARCHAR(120) NULL,
            link_label VARCHAR(180) NULL,
            target_url TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            device_type VARCHAR(50) NULL,
            browser VARCHAR(120) NULL,
            os VARCHAR(120) NULL,
            referrer_url TEXT NULL,
            utm_source VARCHAR(120) NULL,
            utm_medium VARCHAR(120) NULL,
            utm_campaign VARCHAR(120) NULL,
            country VARCHAR(120) NULL,
            region VARCHAR(120) NULL,
            city VARCHAR(120) NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            extra_json TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_market_channel_created (channel, created_at),
            INDEX idx_market_page_created (source_page, created_at),
            INDEX idx_market_country (country),
            INDEX idx_market_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS home_slider_slides (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(160) NOT NULL,
            subtitle VARCHAR(120) NULL,
            description TEXT NULL,
            image_url VARCHAR(255) NULL,
            link_url VARCHAR(255) NULL,
            status ENUM("draft", "published") DEFAULT "draft",
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_home_slider_status (status),
            INDEX idx_home_slider_sort (sort_order, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    try {
        $existingSlides = $pdo->query('SELECT COUNT(*) FROM home_slider_slides')->fetchColumn();
        if ((int) $existingSlides === 0) {
            $slideStmt = $pdo->prepare(
                'INSERT INTO home_slider_slides (title, subtitle, description, image_url, link_url, status, sort_order)
                 VALUES (:title, :subtitle, :description, :image_url, :link_url, :status, :sort_order)'
            );
            $defaultSlides = [
                [
                    'title' => 'Xolabit Seal Coat',
                    'subtitle' => 'Produk Premium',
                    'description' => 'Lapisan pelindung cold-applied untuk menjaga dek beton, atap, dan permukaan beraspal tetap awet.',
                    'image_url' => 'images/product-xolabit-seal-coat.png',
                    'link_url' => '/produk/xolabit-seal-coat',
                ],
                [
                    'title' => 'Cold Mix Patch Pro',
                    'subtitle' => 'Perbaikan Cepat',
                    'description' => 'Solusi patching cepat tanpa pemanasan. Tinggal buka, tabur, padatkan.',
                    'image_url' => 'images/product-cold-mix-patch-pro.png',
                    'link_url' => '/produk/cold-mix-patch-pro',
                ],
                [
                    'title' => 'Support Engineer',
                    'subtitle' => 'Pendampingan Teknis',
                    'description' => 'Tim ahli siap survey dan dampingi pengerjaan supaya hasil lapangan maksimal.',
                    'image_url' => 'images/service-waterproofing.jpeg',
                    'link_url' => '#layanan',
                ],
            ];
            foreach ($defaultSlides as $index => $slide) {
                $slideStmt->execute([
                    'title' => $slide['title'],
                    'subtitle' => $slide['subtitle'],
                    'description' => $slide['description'],
                    'image_url' => $slide['image_url'],
                    'link_url' => $slide['link_url'],
                    'status' => 'published',
                    'sort_order' => $index,
                ]);
            }
        }
    } catch (Throwable $ignored) {
        // ignore seeding failure
    }

    $pdo->beginTransaction();

    $roleStatement = $pdo->prepare(
        'INSERT INTO roles (slug, name, description)
         VALUES (:slug, :name, :description)
         ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)'
    );

    $roles = [
        ['slug' => 'superadmin', 'name' => 'Super Admin', 'description' => 'Kontrol penuh sistem termasuk manajemen admin.'],
        ['slug' => 'administrator', 'name' => 'Administrator', 'description' => 'Akses penuh ke seluruh modul.'],
        ['slug' => 'supervisor', 'name' => 'Supervisor', 'description' => 'Monitoring proyek dan approval konten.'],
        ['slug' => 'editor', 'name' => 'Editor', 'description' => 'Kelola konten blog dan SEO.'],
    ];

    foreach ($roles as $role) {
        $roleStatement->execute($role);
    }

    $fetchRoleId = $pdo->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1');

    $roleIds = [];
    foreach ($roles as $role) {
        $fetchRoleId->execute(['slug' => $role['slug']]);
        $roleIds[$role['slug']] = (int) $fetchRoleId->fetchColumn();
    }

    $userStatement = $pdo->prepare(
        'INSERT INTO users (role_id, username, email, full_name, password_hash, status)
         VALUES (:role_id, :username, :email, :full_name, :password_hash, :status)
         ON DUPLICATE KEY UPDATE role_id = VALUES(role_id), email = VALUES(email), full_name = VALUES(full_name), status = VALUES(status)'
    );

    $users = [
        [
            'role' => 'superadmin',
            'username' => 'superadmin',
            'email' => 'superadmin@aspalemulsi.local',
            'full_name' => 'Super Administrator',
            'password' => 'superadmin123',
        ],
        [
            'role' => 'administrator',
            'username' => 'admin',
            'email' => 'admin@aspalemulsi.local',
            'full_name' => 'Administrator Utama',
            'password' => 'aspalemulsi123',
        ],
        [
            'role' => 'supervisor',
            'username' => 'supervisor',
            'email' => 'supervisor@aspalemulsi.local',
            'full_name' => 'Site Supervisor',
            'password' => 'supervisor123',
        ],
        [
            'role' => 'editor',
            'username' => 'editor',
            'email' => 'editor@aspalemulsi.local',
            'full_name' => 'Editor Konten',
            'password' => 'editor123',
        ],
    ];

    foreach ($users as $user) {
        $userStatement->execute([
            'role_id' => $roleIds[$user['role']] ?? null,
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'password_hash' => password_hash($user['password'], PASSWORD_DEFAULT),
            'status' => 'active',
        ]);
    }

    $fetchUserId = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');

    $userIds = [];
    foreach ($users as $user) {
        $fetchUserId->execute(['username' => $user['username']]);
        $userIds[$user['username']] = (int) $fetchUserId->fetchColumn();
    }

    $settingsStatement = $pdo->prepare(
        'INSERT INTO site_settings (
            id,
            company_name,
            tagline,
            meta_title,
            meta_description,
            meta_keywords,
            meta_robots,
            company_email,
            company_phone,
            whatsapp_number,
            address,
            map_embed_url,
            working_hours,
            shopee_url,
            tokopedia_url,
            facebook_url,
            instagram_url,
            linkedin_url,
            youtube_url,
            tiktok_url,
            twitter_url,
            logo_url,
            favicon_url,
            updated_by
        )
        VALUES (
            1,
            :company_name,
            :tagline,
            :meta_title,
            :meta_description,
            :meta_keywords,
            :meta_robots,
            :company_email,
            :company_phone,
            :whatsapp_number,
            :address,
            :map_embed_url,
            :working_hours,
            :shopee_url,
            :tokopedia_url,
            :facebook_url,
            :instagram_url,
            :linkedin_url,
            :youtube_url,
            :tiktok_url,
            :twitter_url,
            :logo_url,
            :favicon_url,
            :updated_by
        )
        ON DUPLICATE KEY UPDATE
            company_name = VALUES(company_name),
            tagline = VALUES(tagline),
            meta_title = VALUES(meta_title),
            meta_description = VALUES(meta_description),
            meta_keywords = VALUES(meta_keywords),
            meta_robots = VALUES(meta_robots),
            company_email = VALUES(company_email),
            company_phone = VALUES(company_phone),
            whatsapp_number = VALUES(whatsapp_number),
            address = VALUES(address),
            map_embed_url = VALUES(map_embed_url),
            working_hours = VALUES(working_hours),
            shopee_url = VALUES(shopee_url),
            tokopedia_url = VALUES(tokopedia_url),
            facebook_url = VALUES(facebook_url),
            instagram_url = VALUES(instagram_url),
            linkedin_url = VALUES(linkedin_url),
            youtube_url = VALUES(youtube_url),
            tiktok_url = VALUES(tiktok_url),
            twitter_url = VALUES(twitter_url),
            logo_url = VALUES(logo_url),
            favicon_url = VALUES(favicon_url),
            updated_by = VALUES(updated_by)'
    );

    $settingsStatement->execute([
        'company_name' => 'PT Aspal Emulsi Indonesia',
        'tagline' => 'Solusi Infrastruktur Jalan Berkelanjutan',
        'meta_title' => 'Aspal Emulsi Indonesia | Solusi Infrastruktur Jalan',
        'meta_description' => 'PT Aspal Emulsi Indonesia menyediakan layanan overlay jalan, waterproofing, dan produk aspal emulsi berkualitas tinggi untuk proyek infrastruktur di seluruh Indonesia.',
        'meta_keywords' => 'aspal emulsi, overlay jalan, waterproofing membran, cold mix, infrastruktur jalan',
        'meta_robots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        'company_email' => 'info@aspalemulsi.local',
        'company_phone' => '+62 21 5089 4400',
        'whatsapp_number' => '+62 812 3456 7890',
        'address' => "Kawasan Industri Delta Silicon\nBlock F2 No. 12\nCikarang, Jawa Barat 17530",
        'map_embed_url' => 'https://maps.google.com/?q=Jakarta',
        'working_hours' => "Senin - Jumat 08:00 - 17:00\nSabtu 08:00 - 14:00",
        'shopee_url' => 'https://shopee.co.id/aspal-emulsi-official',
        'tokopedia_url' => 'https://www.tokopedia.com/aspalemulsi',
        'facebook_url' => 'https://facebook.com/aspalemulsi',
        'instagram_url' => 'https://instagram.com/aspalemulsi',
        'linkedin_url' => 'https://linkedin.com/company/aspalemulsi',
        'youtube_url' => 'https://youtube.com/@aspalemulsi',
        'tiktok_url' => 'https://www.tiktok.com/@aspalemulsi',
        'twitter_url' => 'https://twitter.com/aspalemulsi',
        'logo_url' => '/images/LOGO.png',
        'favicon_url' => '/images/LOGO.png',
        'updated_by' => $userIds['admin'] ?? null,
    ]);

    $categoryStatement = $pdo->prepare(
        'INSERT INTO blog_categories (slug, name, description)
         VALUES (:slug, :name, :description)
         ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)'
    );

    $categories = [
        [
            'slug' => 'teknologi',
            'name' => 'Teknologi',
            'description' => 'Update riset emulsi, aditif, dan inovasi material untuk performa perkerasan maksimal.',
        ],
        [
            'slug' => 'perawatan',
            'name' => 'Perawatan',
            'description' => 'Checklist preventif, SOP inspeksi, dan strategi menjaga permukaan tetap prima.',
        ],
        [
            'slug' => 'project-insight',
            'name' => 'Project Insight',
            'description' => 'Studi kasus detail dari implementasi proyek Aspal Emulsi Techno di lapangan.',
        ],
    ];

    foreach ($categories as $category) {
        $categoryStatement->execute($category);
    }

    $fetchCategoryId = $pdo->prepare('SELECT id FROM blog_categories WHERE slug = :slug LIMIT 1');

    $categoryIds = [];
    foreach ($categories as $category) {
        $fetchCategoryId->execute(['slug' => $category['slug']]);
        $categoryIds[$category['slug']] = (int) $fetchCategoryId->fetchColumn();
    }

    $postStatement = $pdo->prepare(
        'INSERT INTO blog_posts (
            author_id,
            title,
            slug,
            hero_tag,
            hero_author_override,
            hero_reading_time,
            summary,
            content,
            cover_image_url,
            cover_image_alt,
            intro_quote,
            intro_quote_cite,
            table_of_contents_json,
            timeline_json,
            stat_blocks_json,
            checklist_json,
            cta_heading,
            cta_subheading,
            cta_shopee_url,
            cta_tokopedia_url,
            cta_whatsapp_url,
            status,
            published_at
        )
        VALUES (
            :author_id,
            :title,
            :slug,
            :hero_tag,
            :hero_author_override,
            :hero_reading_time,
            :summary,
            :content,
            :cover_image_url,
            :cover_image_alt,
            :intro_quote,
            :intro_quote_cite,
            :table_of_contents_json,
            :timeline_json,
            :stat_blocks_json,
            :checklist_json,
            :cta_heading,
            :cta_subheading,
            :cta_shopee_url,
            :cta_tokopedia_url,
            :cta_whatsapp_url,
            :status,
            :published_at
        )
        ON DUPLICATE KEY UPDATE
            author_id = VALUES(author_id),
            hero_tag = VALUES(hero_tag),
            hero_author_override = VALUES(hero_author_override),
            hero_reading_time = VALUES(hero_reading_time),
            summary = VALUES(summary),
            content = VALUES(content),
            cover_image_url = VALUES(cover_image_url),
            cover_image_alt = VALUES(cover_image_alt),
            intro_quote = VALUES(intro_quote),
            intro_quote_cite = VALUES(intro_quote_cite),
            table_of_contents_json = VALUES(table_of_contents_json),
            timeline_json = VALUES(timeline_json),
            stat_blocks_json = VALUES(stat_blocks_json),
            checklist_json = VALUES(checklist_json),
            cta_heading = VALUES(cta_heading),
            cta_subheading = VALUES(cta_subheading),
            cta_shopee_url = VALUES(cta_shopee_url),
            cta_tokopedia_url = VALUES(cta_tokopedia_url),
            cta_whatsapp_url = VALUES(cta_whatsapp_url),
            status = VALUES(status),
            published_at = VALUES(published_at)'
    );

    $now = new DateTimeImmutable('now');

    $posts = [
        [
            'title' => 'Strategi Slurry Seal Untuk Kampus Bebas Retak',
            'slug' => 'strategi-slurry-seal-untuk-kampus-bebas-retak',
            'summary' => 'Panduan lengkap menjaga akses kampus tetap halus dengan slurry seal berbasis emulsi, lengkap parameter teknis, workflow 48 jam, dan checklist maintenance.',
            'content' => <<<'HTML'
<section id="pendahuluan">
    <p>Jalur pedestrian dan akses kendaraan di lingkungan kampus punya traffic variatif, mulai dari dosen yang parkir sampai kendaraan logistik yang drop barang ke laboratorium. Tanpa perawatan berkala, retak rambut bisa muncul setiap semester dan biaya tambal sulam meroket.</p>
    <p>Strategi slurry seal berbasis aspal emulsi jadi solusi paling efektif karena mampu menutup retak mikro, mengembalikan tekstur, dan menciptakan lapisan kedap air dalam sekali workflow.</p>
    <blockquote>
        <p>"Slurry seal itu bukan sekadar kosmetik. Kalau komposisi agregat dan emulsi tepat, lapisan baru bisa memperpanjang umur service road sampai empat hingga lima tahun."</p>
        <footer>Fajar, Project Lead Kampus ABC</footer>
    </blockquote>
</section>
<section id="parameter">
    <h2>Parameter Teknis Yang Harus Dijaga</h2>
    <p>Sebelum batching, tim quality control kami memastikan parameter berikut terkunci supaya hasilnya konsisten:</p>
    <ul>
        <li>Gradasi agregat diseleksi di range tipe II atau III mengikuti tekstur awal.</li>
        <li>Emulsi anionik dengan residu bitumen minimal 60 persen untuk daya rekat ekstra.</li>
        <li>Retention water content dijaga supaya slurry tidak cepat kering saat suhu siang di atas 32C.</li>
        <li>Admix hydration time minimal 15 menit agar tidak terjadi bleeding saat curing.</li>
    </ul>
</section>
<section id="workflow">
    <h2>Workflow 48 Jam Tanpa Ganggu Aktivitas Akademik</h2>
    <p>Kunci sukses di lingkungan kampus adalah scheduling ketat yang meminimalkan penutupan area. Template timeline yang kami pakai:</p>
    <ol>
        <li><strong>Day 0 (Malam)</strong> - Briefing bersama pengelola kampus, pemasangan signage, dan rekayasa lalu lintas.</li>
        <li><strong>Day 1 (Pagi)</strong> - Pembersihan permukaan, pressure washing, dan penutupan retak selektif.</li>
        <li><strong>Day 1 (Siang)</strong> - Aplikasi slurry layer pertama, curing dua jam dibantu blower portabel.</li>
        <li><strong>Day 1 (Sore)</strong> - Layer kedua plus fog seal tipis untuk menutup pori, kemudian penandaan area.</li>
        <li><strong>Day 2 (Pagi)</strong> - Pembukaan lalu lintas terbatas dan remarking jalur prioritas.</li>
    </ol>
</section>
<section id="studi-kasus">
    <h2>Studi Kasus Kampus Negeri di Jawa Timur</h2>
    <p>Kami menangani 12.000 m2 akses utama yang mulai aus dan memiliki jadwal akademik ketat. Area drop-off tidak boleh tutup lebih dari dua hari sehingga logistik harus presisi.</p>
    <p>Dengan slurry seal dua layer dan pengaturan shift malam, downtime tercatat hanya 46 jam. Nilai skid resistance naik 28 persen berdasarkan uji British Pendulum Test internal.</p>
</section>
<section id="maintenance">
    <h2>Checklist Maintenance Setelah Slurry Seal</h2>
    <p>Agar lapisan awet, tim maintenance kampus kami bekali checklist harian berikut:</p>
    <ul>
        <li>Bersihkan butiran pasir lepas tiap pagi menggunakan blower ringan.</li>
        <li>Periksa drainase bebas sampah minimal sekali seminggu.</li>
        <li>Remarking area parkir setiap semester agar marka tidak merusak lapisan.</li>
        <li>Laporkan potensi retak di atas dua milimeter dalam 24 jam.</li>
    </ul>
    <p>Butuh template inspeksi versi PDF lengkap dengan form checklist? Hubungi kami lewat CTA di bawah.</p>
</section>
HTML,
            'cover_image_url' => '/uploads/blog-slurry-seal.jpeg',
            'cover_image_alt' => 'Teknisi memeriksa permukaan jalan kampus yang baru selesai slurry seal',
            'hero_tag' => 'Perawatan',
            'hero_author_override' => 'Tim Engineer Aspal Emulsi',
            'hero_reading_time' => '7 menit',
            'intro_quote' => 'Slurry seal itu bukan sekadar kosmetik. Kalau komposisi agregat dan emulsi tepat, lapisan baru bisa memperpanjang umur service road sampai empat hingga lima tahun.',
            'intro_quote_cite' => 'Fajar, Project Lead Kampus ABC',
            'table_of_contents' => [
                ['label' => 'Kenapa Kampus Perlu Slurry Seal?', 'anchor' => 'pendahuluan'],
                ['label' => 'Parameter Teknis', 'anchor' => 'parameter'],
                ['label' => 'Workflow 48 Jam', 'anchor' => 'workflow'],
                ['label' => 'Studi Kasus', 'anchor' => 'studi-kasus'],
                ['label' => 'Checklist Maintenance', 'anchor' => 'maintenance'],
            ],
            'timeline' => [
                ['label' => 'Day 0 (Malam)', 'value' => 'Briefing, pemasangan signage, dan rekayasa lalu lintas.'],
                ['label' => 'Day 1 (Pagi)', 'value' => 'Pembersihan permukaan dan crack sealing.'],
                ['label' => 'Day 1 (Siang)', 'value' => 'Penyemprotan layer pertama dengan curing dua jam.'],
                ['label' => 'Day 1 (Sore)', 'value' => 'Layer kedua dan fog seal tipis sebelum demarkasi.'],
                ['label' => 'Day 2 (Pagi)', 'value' => 'Pembukaan lalu lintas terbatas dan inspeksi akhir.'],
            ],
            'stats' => [
                ['label' => 'Luas Area', 'value' => '12.000 m2'],
                ['label' => 'Downtime', 'value' => '46 jam'],
                ['label' => 'Jumlah Crew', 'value' => '18 personel'],
                ['label' => 'Complaint', 'value' => '0 issue'],
            ],
            'checklist' => [
                'Bersihkan butiran pasir lepas tiap pagi menggunakan blower ringan.',
                'Periksa drainase bebas sampah minimal sekali seminggu.',
                'Remarking area parkir setiap semester agar marka tidak merusak lapisan.',
                'Laporkan potensi retak di atas dua milimeter dalam 24 jam.',
            ],
            'cta_heading' => 'Mau Planning Slurry Seal di Kampus Kamu?',
            'cta_subheading' => 'Klik tombol di bawah untuk ngobrol dengan engineer kami atau cek price list terbaru di e-commerce resmi.',
            'cta_shopee_url' => 'https://shopee.co.id',
            'cta_tokopedia_url' => 'https://tokopedia.com',
            'cta_whatsapp_url' => 'https://wa.me/6281234567890?text=Halo%20ASPAL%20EMULSI%20Techno%2C%20saya%20butuh%20konsultasi%20slurry%20seal%20untuk%20kampus.%20Bisa%20dibantu%3F',
            'status' => 'published',
            'published_at' => '2025-05-04 09:00:00',
            'author' => 'editor',
            'categories' => ['perawatan', 'project-insight'],
        ],
        [
            'title' => 'Formula Emulsi Generasi Baru Untuk Curah Hujan Tinggi',
            'slug' => 'formula-emulsi-generasi-baru-curah-hujan-tinggi',
            'summary' => 'Kombinasi lateks dan bitumen termodifikasi menjaga durabilitas jalur logistik di area dengan intensitas hujan ekstrem.',
            'content' => <<<'HTML'
<section id="tantangan">
    <p>Curah hujan di jalur logistik timur Indonesia bisa menyentuh 2800 milimeter per tahun. Itu membuat residu emulsi cepat tererosi dan prime coat gagal menahan shear load forklift.</p>
    <p>Riset internal kami memadukan latex berbasis styrene butadiene dengan bitumen penetrasi 60 70 sehingga membentuk film kuat tanpa kehilangan fleksibilitas.</p>
</section>
<section id="riset">
    <h2>Blueprint Formulasi Generasi Baru</h2>
    <p>Formulasi ini dikembangkan lewat kolaborasi lab internal dan mitra kimia. Parameter utama yang kami kunci:</p>
    <ul>
        <li>Residue bitumen minimal 65 persen dengan viskositas 90 SSF.</li>
        <li>Pakai emulsifier kationik tipe slow setting supaya coating merata di agregat basah.</li>
        <li>Latex 3 persen meningkatkan elastisitas dan daya tahan terhadap siklus panas hujan.</li>
    </ul>
</section>
<section id="validasi">
    <h2>Validasi Laboratorium dan Field Trial</h2>
    <p>Batch pilot kami diuji dengan wheel tracking dan simulasi hujan buatan selama 24 jam. Hasilnya, bleeding turun 42 persen dibanding emulsi konvensional.</p>
    <p>Field trial di jalur logistik Kalimantan menunjukan adhesi tack coat tetap stabil setelah paparan hujan lebat tiga hari berturut turut.</p>
</section>
<section id="deploy">
    <h2>Deploy di Lapangan</h2>
    <p>Produksi massal dilakukan dengan kontrol suhu pada 62C saat mixing, kemudian langsung dikirim menggunakan IBC tank agar suhu stabil.</p>
    <p>Tim lapangan tinggal mengatur pressure sprayer di 2 bar dan memastikan laju aplikasi 1.2 liter per meter persegi.</p>
</section>
HTML,
            'cover_image_url' => '/uploads/blog-formula-emulsi.jpg',
            'cover_image_alt' => 'Teknisi memeriksa kualitas aspal emulsi',
            'hero_tag' => 'Teknologi',
            'hero_author_override' => 'Tim R&D Aspal Emulsi',
            'hero_reading_time' => '6 menit',
            'intro_quote' => 'Kombinasi lateks dan bitumen termodifikasi menjaga durabilitas jalur logistik di area dengan intensitas hujan tinggi.',
            'intro_quote_cite' => 'Tim R&D Aspal Emulsi',
            'table_of_contents' => [
                ['label' => 'Tantangan Curah Hujan Tinggi', 'anchor' => 'tantangan'],
                ['label' => 'Blueprint Formulasi', 'anchor' => 'riset'],
                ['label' => 'Validasi Lapangan', 'anchor' => 'validasi'],
                ['label' => 'Strategi Deploy', 'anchor' => 'deploy'],
            ],
            'timeline' => [
                ['label' => 'Batch Lab', 'value' => 'Simulasi hujan 120 menit dan siklus beku cair 15 kali.'],
                ['label' => 'Pilot Plant', 'value' => 'Produksi 5 ton menggunakan emulsifier kationik generasi baru.'],
                ['label' => 'Field Trial', 'value' => 'Pengujian di jalur logistik dengan beban forklift 12 ton.'],
            ],
            'stats' => [
                ['label' => 'Bleeding Turun', 'value' => '42 persen'],
                ['label' => 'Shear Strength', 'value' => 'Naik 18 persen'],
                ['label' => 'Lead Time Produksi', 'value' => '3 hari'],
            ],
            'checklist' => [
                'Gunakan bitumen base penetrasi 60 70 untuk menjaga elastisitas.',
                'Tambahkan latex maksimal 3 persen agar viskositas tetap stabil.',
                'Kontrol suhu mixing di 62C dan hindari overheating.',
                'Lakukan uji residu setiap batch sebelum dikirim.',
            ],
            'cta_heading' => 'Siap upgrade formulasi emulsi?',
            'cta_subheading' => 'Jadwalkan sesi konsultasi bersama tim R&D kami untuk kalibrasi emulsi sesuai kondisi curah hujan lokasi proyek.',
            'cta_shopee_url' => 'https://shopee.co.id',
            'cta_tokopedia_url' => 'https://tokopedia.com',
            'cta_whatsapp_url' => 'https://wa.me/6281234567890?text=Halo%20saya%20mau%20diskusi%20formula%20emulsi%20untuk%20curah%20hujan%20tinggi',
            'status' => 'published',
            'published_at' => '2025-05-04 08:00:00',
            'author' => 'admin',
            'categories' => ['teknologi'],
        ],
        [
            'title' => 'Checklist Pemeliharaan Jalan Kampus Dalam 48 Jam',
            'slug' => 'checklist-pemeliharaan-jalan-kampus-48-jam',
            'summary' => 'SOP ringkas untuk tim internal kampus agar crack kecil tidak berkembang jadi kerusakan besar.',
            'content' => <<<'HTML'
<section id="kenapa">
    <p>Musim hujan bikin permukaan jalan kampus rawan retak rambut. Kalau dibiarkan dua semester, retak bisa berubah jadi lubang lima sentimeter.</p>
    <p>Checklist 48 jam membuat tim maintenance bisa bergerak cepat tanpa harus menunggu anggaran tahunan turun.</p>
</section>
<section id="checklist-48-jam">
    <h2>Checklist 48 Jam</h2>
    <ol>
        <li><strong>Jam 0-12</strong> - Bersihkan permukaan dari pasir, gunakan blower atau vacuum industrial.</li>
        <li><strong>Jam 12-24</strong> - Dokumentasikan retak di atas dua milimeter dan lakukan sealant spot.</li>
        <li><strong>Jam 24-36</strong> - Terapkan fog seal tipis di area parkir yang aus.</li>
        <li><strong>Jam 36-48</strong> - Tutup akses sementara untuk curing dan lakukan inspeksi ulang.</li>
    </ol>
</section>
<section id="tools">
    <h2>Tools dan Resource</h2>
    <p>Berikut tool minimum yang wajib siaga di kampus:</p>
    <ul>
        <li>Blower listrik, sprayer portable, dan traffic cone untuk marking.</li>
        <li>Sealant berbasis emulsi dan agregat halus kering.</li>
        <li>Form inspeksi digital untuk upload foto before after.</li>
    </ul>
    <p>Kombinasi tool ini terbukti menjaga jalur kampus kami tetap aman walau curah hujan tinggi.</p>
</section>
HTML,
            'cover_image_url' => '/uploads/blog-checklist-kampus.jpeg',
            'cover_image_alt' => 'Pekerja melakukan perawatan jalan kota',
            'hero_tag' => 'Perawatan',
            'hero_author_override' => 'Divisi Maintenance Support',
            'hero_reading_time' => '5 menit',
            'intro_quote' => 'Crack kecil yang diabaikan dua semester bisa berubah jadi lubang lima sentimeter.',
            'intro_quote_cite' => 'Divisi Maintenance Support',
            'table_of_contents' => [
                ['label' => 'Kenapa Preventive Maintenance?', 'anchor' => 'kenapa'],
                ['label' => 'Checklist 48 Jam', 'anchor' => 'checklist-48-jam'],
                ['label' => 'Tools dan Resource', 'anchor' => 'tools'],
            ],
            'timeline' => [
                ['label' => 'Jam 0-12', 'value' => 'Bersihkan permukaan dan pastikan drainase tidak tersumbat.'],
                ['label' => 'Jam 12-24', 'value' => 'Audit retak dan lakukan sealant spot.'],
                ['label' => 'Jam 24-48', 'value' => 'Fog seal, curing, dan inspeksi ulang sebelum buka akses.'],
            ],
            'stats' => [
                ['label' => 'Downtime Maksimal', 'value' => '48 jam'],
                ['label' => 'Area Tercover', 'value' => '15 blok parkir'],
                ['label' => 'Tim Lapangan', 'value' => '6 personel'],
            ],
            'checklist' => [
                'Bersihkan butiran pasir lepas tiap pagi menggunakan blower ringan.',
                'Periksa drainase bebas sampah minimal sekali seminggu.',
                'Remarking area parkir setiap semester agar marka tidak merusak lapisan.',
                'Laporkan potensi retak di atas dua milimeter dalam 24 jam.',
            ],
            'cta_heading' => 'Butuh template inspeksi versi PDF?',
            'cta_subheading' => 'Isi form WhatsApp kami dan dapatkan format checklist siap pakai lengkap dengan kolom dokumentasi foto.',
            'cta_shopee_url' => 'https://shopee.co.id',
            'cta_tokopedia_url' => 'https://tokopedia.com',
            'cta_whatsapp_url' => 'https://wa.me/6281234567890?text=Halo%20saya%20mau%20template%20checklist%20maintenance%20jalan',
            'status' => 'published',
            'published_at' => '2025-04-27 09:30:00',
            'author' => 'editor',
            'categories' => ['perawatan'],
        ],
        [
            'title' => 'Studi Kasus: Upgrade Runway Industri Dengan Slurry Seal',
            'slug' => 'studi-kasus-upgrade-runway-industri-dengan-slurry-seal',
            'summary' => 'Cerita implementasi di pabrik otomotif Jawa Timur yang sukses memangkas downtime 30 persen.',
            'content' => <<<'HTML'
<section id="konteks">
    <p>Runway logistik pabrik otomotif Jawa Timur mengalami ravelling dan bleeding setelah tiga tahun operasi. Padahal jalur ini harus siap dilewati truk 45 ton setiap dua menit.</p>
    <p>Kami diminta meng-upgrade tanpa menghentikan produksi lebih dari 60 jam.</p>
</section>
<section id="strategi">
    <h2>Strategi Eksekusi</h2>
    <p>Kami memecah pekerjaan dalam beberapa zona kecil supaya produksi pabrik tetap berjalan. Setiap zona diolah dengan prime coat kationik dan slurry seal dua layer.</p>
    <ul>
        <li>Menggunakan agregat basalt 3 milimeter agar tahan shear forklift.</li>
        <li>Memasang temporary ramp sehingga forklift bisa melewati area curing.</li>
        <li>Monitoring suhu permukaan dengan infrared gun setiap 30 menit.</li>
    </ul>
</section>
<section id="hasil">
    <h2>Hasil dan KPI</h2>
    <p>Downtime tercatat hanya 52 jam dengan peningkatan skid resistance 32 persen. Runway siap beroperasi sebelum shift Senin pagi.</p>
    <p>Client juga mendapatkan laporan harian plus rekomendasi inspeksi bulanan selama 12 bulan ke depan.</p>
</section>
<section id="pelajaran">
    <h2>Pelajaran Penting</h2>
    <p>Integrasi antara tim produksi client dan crew Aspal Emulsi memegang peranan besar. Berikut poin yang kami catat:</p>
    <ul>
        <li>Traffic management harus dipimpin satu PIC supaya keputusan cepat.</li>
        <li>Komunikasi radio dua arah wajib disiapkan untuk mengatur forklift.</li>
        <li>Backup crew malam dibutuhkan untuk mengantisipasi hujan dadakan.</li>
    </ul>
</section>
HTML,
            'cover_image_url' => '/uploads/blog-upgrade-runway.jpeg',
            'cover_image_alt' => 'Tim project review blueprint perkerasan',
            'hero_tag' => 'Project',
            'hero_author_override' => 'Project Delivery Team',
            'hero_reading_time' => '8 menit',
            'intro_quote' => 'Downtime runway industri bisa ditekan 30 persen ketika slurry seal dipadukan dengan traffic management yang disiplin.',
            'intro_quote_cite' => 'Project Delivery Team Aspal Emulsi',
            'table_of_contents' => [
                ['label' => 'Konteks Proyek', 'anchor' => 'konteks'],
                ['label' => 'Strategi Eksekusi', 'anchor' => 'strategi'],
                ['label' => 'Hasil dan KPI', 'anchor' => 'hasil'],
                ['label' => 'Pelajaran Penting', 'anchor' => 'pelajaran'],
            ],
            'timeline' => [
                ['label' => 'Hari 1', 'value' => 'Persiapan zona, milling ringan, dan prime coat.'],
                ['label' => 'Hari 2', 'value' => 'Layer slurry pertama dan curing 4 jam.'],
                ['label' => 'Hari 3', 'value' => 'Layer kedua, finishing, dan fog seal.'],
                ['label' => 'Hari 4', 'value' => 'Load test dan pembukaan bertahap.'],
            ],
            'stats' => [
                ['label' => 'Downtime Turun', 'value' => '30 persen'],
                ['label' => 'Skid Resistance', 'value' => 'Naik 32 persen'],
                ['label' => 'Area Dikerjakan', 'value' => '8.500 m2'],
                ['label' => 'Crew', 'value' => '22 personel'],
            ],
            'checklist' => [
                'Simulasikan lintasan forklift sebelum area dibuka kembali.',
                'Pastikan suhu permukaan di bawah 45C sebelum membuka traffic berat.',
                'Catat semua issue harian di log maintenance untuk evaluasi bulanan.',
            ],
            'cta_heading' => 'Siap upgrade runway industri?',
            'cta_subheading' => 'Kami bantu hitung ROI, locking jadwal produksi, sampai support commissioning di lokasi.',
            'cta_shopee_url' => 'https://shopee.co.id',
            'cta_tokopedia_url' => 'https://tokopedia.com',
            'cta_whatsapp_url' => 'https://wa.me/6281234567890?text=Halo%20saya%20mau%20diskusi%20upgrade%20runway%20industri',
            'status' => 'published',
            'published_at' => '2025-04-19 08:30:00',
            'author' => 'supervisor',
            'categories' => ['project-insight'],
        ],
    ];

    foreach ($posts as $post) {
        $postStatement->execute([
            'author_id' => $userIds[$post['author']] ?? null,
            'title' => $post['title'],
            'slug' => $post['slug'],
            'hero_tag' => $post['hero_tag'] ?? null,
            'hero_author_override' => $post['hero_author_override'] ?? null,
            'hero_reading_time' => $post['hero_reading_time'] ?? null,
            'summary' => $post['summary'],
            'content' => $post['content'],
            'cover_image_url' => $post['cover_image_url'],
            'cover_image_alt' => $post['cover_image_alt'] ?? null,
            'intro_quote' => $post['intro_quote'] ?? null,
            'intro_quote_cite' => $post['intro_quote_cite'] ?? null,
            'table_of_contents_json' => isset($post['table_of_contents']) && $post['table_of_contents'] !== [] ? json_encode($post['table_of_contents'], JSON_UNESCAPED_UNICODE) : null,
            'timeline_json' => isset($post['timeline']) && $post['timeline'] !== [] ? json_encode($post['timeline'], JSON_UNESCAPED_UNICODE) : null,
            'stat_blocks_json' => isset($post['stats']) && $post['stats'] !== [] ? json_encode($post['stats'], JSON_UNESCAPED_UNICODE) : null,
            'checklist_json' => isset($post['checklist']) && $post['checklist'] !== [] ? json_encode($post['checklist'], JSON_UNESCAPED_UNICODE) : null,
            'cta_heading' => $post['cta_heading'] ?? null,
            'cta_subheading' => $post['cta_subheading'] ?? null,
            'cta_shopee_url' => $post['cta_shopee_url'] ?? null,
            'cta_tokopedia_url' => $post['cta_tokopedia_url'] ?? null,
            'cta_whatsapp_url' => $post['cta_whatsapp_url'] ?? null,
            'status' => $post['status'],
            'published_at' => $post['published_at'],
        ]);
    }

    $fetchPostId = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = :slug LIMIT 1');

    $postIds = [];
    foreach ($posts as $post) {
        $fetchPostId->execute(['slug' => $post['slug']]);
        $postIds[$post['slug']] = (int) $fetchPostId->fetchColumn();
    }

    $pivotStatement = $pdo->prepare(
        'INSERT IGNORE INTO blog_post_categories (post_id, category_id)
         VALUES (:post_id, :category_id)'
    );

    foreach ($posts as $post) {
        $postId = $postIds[$post['slug']] ?? null;
        if ($postId === null) {
            continue;
        }

        foreach ($post['categories'] as $categorySlug) {
            $categoryId = $categoryIds[$categorySlug] ?? null;
            if ($categoryId === null) {
                continue;
            }

            $pivotStatement->execute([
                'post_id' => $postId,
                'category_id' => $categoryId,
            ]);
        }
    }

    $productStatement = $pdo->prepare(
        'INSERT INTO products (
            name,
            slug,
            short_description,
            description,
            cover_image_url,
            hero_badge,
            hero_eyebrow,
            highlights_json,
            specs_json,
            summary_json,
            documents_json,
            shopee_url,
            tokopedia_url,
            whatsapp_url,
            status,
            published_at
        )
        VALUES (
            :name,
            :slug,
            :short_description,
            :description,
            :cover_image_url,
            :hero_badge,
            :hero_eyebrow,
            :highlights_json,
            :specs_json,
            :summary_json,
            :documents_json,
            :shopee_url,
            :tokopedia_url,
            :whatsapp_url,
            :status,
            :published_at
        )
        ON DUPLICATE KEY UPDATE
            short_description = VALUES(short_description),
            description = VALUES(description),
            cover_image_url = VALUES(cover_image_url),
            hero_badge = VALUES(hero_badge),
            hero_eyebrow = VALUES(hero_eyebrow),
            highlights_json = VALUES(highlights_json),
            specs_json = VALUES(specs_json),
            summary_json = VALUES(summary_json),
            documents_json = VALUES(documents_json),
            shopee_url = VALUES(shopee_url),
            tokopedia_url = VALUES(tokopedia_url),
            whatsapp_url = VALUES(whatsapp_url),
            status = VALUES(status),
            published_at = VALUES(published_at)'
    );

    $products = [
        [
            'name' => 'Xolabit Seal Coat',
            'slug' => 'xolabit-seal-coat',
            'short_description' => 'Lapisan pelindung cold-applied yang menjaga dek beton, atap, dan permukaan beraspal tetap awet dan bebas retak mikro.',
            'description' => <<<'HTML'
<p>Xolabit Seal Coat adalah pelapis cold-applied berbasis latex-bitumen yang membentuk film elastis di atas beton atau permukaan aspal lama. Formulanya memberikan daya rekat tinggi sekaligus finishing halus yang siap top coat.</p>
<p>Dengan dua lapis aplikasi, sistem ini menutup retak mikro dan membuat dek beton kedap air. Kombinasikan dengan primer Xolabit untuk hasil maksimal di rooftop, dek parkir, maupun area pergudangan.</p>
<div>
    <h3>Benefit Teknis</h3>
    <ul>
        <li>Waterproofing elastis dengan elongasi di atas 300 persen.</li>
        <li>Resisten terhadap chemical ringan seperti oli dan bahan bakar.</li>
        <li>Mudah dibersihkan karena finishing licin tidak menahan debu.</li>
        <li>Mengurangi thermal fatigue di area dengan paparan matahari tinggi.</li>
    </ul>
</div>
HTML,
            'cover_image_url' => '/uploads/product-xolabit-seal-coat.png',
            'hero_badge' => 'Produk Premium',
            'hero_eyebrow' => 'Solusi Pelapisan Kedap Air',
            'highlights' => [
                'Daya rekat tinggi di permukaan beton dan aspal bertekstur.',
                'Ketahanan UV dan siklus panas hujan khas iklim tropis.',
                'Curing kurang dari empat jam sehingga cepat kembali operasional.',
            ],
            'specs' => [
                ['label' => 'Kemasan', 'value' => 'Pail 20 kg • Drum 200 kg'],
                ['label' => 'Coverage', 'value' => '1.2 - 1.5 kg per m2 (dua lapis)'],
                ['label' => 'Lead Time', 'value' => 'Ready stock 1-2 hari kerja'],
            ],
            'summary' => [
                ['label' => 'Jenis Produk', 'value' => 'Cold applied seal coat'],
                ['label' => 'Rekomendasi Substrat', 'value' => 'Dak beton, dek parkir, permukaan aspal lama'],
                ['label' => 'Metode Aplikasi', 'value' => 'Spray, roll, atau squeegee'],
                ['label' => 'Waktu Curing', 'value' => '3-4 jam (25C)'],
                ['label' => 'Warna Finishing', 'value' => 'Hitam doff'],
            ],
            'documents' => [
                ['title' => 'Data Sheet Xolabit Seal Coat', 'url' => 'https://aspalemulsi.com/docs/xolabit-seal-coat-data-sheet.pdf'],
                ['title' => 'Panduan Aplikasi', 'url' => 'https://aspalemulsi.com/docs/xolabit-seal-coat-application-guide.pdf'],
                ['title' => 'Sertifikat Lab', 'url' => 'https://aspalemulsi.com/docs/xolabit-seal-coat-lab-report.pdf'],
            ],
            'shopee_url' => 'https://shopee.co.id',
            'tokopedia_url' => 'https://tokopedia.com',
            'whatsapp_url' => 'https://wa.me/6281234567890?text=Halo%20saya%20mau%20order%20Xolabit%20Seal%20Coat',
            'status' => 'published',
            'published_at' => '2025-05-07 09:00:00',
        ],
        [
            'name' => 'Xolabit Bitumen',
            'slug' => 'xolabit-bitumen',
            'short_description' => 'Bitumen premium dengan adhesi tinggi untuk konstruksi jalan dan struktur yang membutuhkan daya rekat maksimal.',
            'description' => <<<'HTML'
<p>Xolabit Bitumen dirancang untuk proyek overlay dan lapis struktural yang menuntut ikatan kuat antar agregat. Formulanya stabil meski memakai agregat lokal dengan variasi kadar air.</p>
<p>Produk ini cocok untuk mixing plant maupun aplikasi lapangan karena flow point terjaga di temperatur operasional 160C.</p>
<div>
    <h3>Apa yang membuatnya berbeda?</h3>
    <ul>
        <li>Stabilitas Marshall tinggi sehingga cocok untuk jalur dengan beban berat.</li>
        <li>Modifikasi polimer membuat lapisan lebih tahan retak akibat beban berulang.</li>
        <li>Viscosity terkontrol sehingga mudah dipompa dan dicampur.</li>
    </ul>
</div>
HTML,
            'cover_image_url' => '/uploads/product-xolabit-bitumen.png',
            'hero_badge' => 'Produk Teknik',
            'hero_eyebrow' => 'Adhesi Maksimal',
            'highlights' => [
                'Adhesi tinggi pada agregat basah dan kering.',
                'Kompatibel dengan filler lokal tanpa menurunkan flow.',
                'Stabil untuk jalur hauling dan jalan industri berat.',
            ],
            'specs' => [
                ['label' => 'Kemasan', 'value' => 'Drum 200 kg • Bulk'],
                ['label' => 'Penetrasi', 'value' => '60 - 70 dmm'],
                ['label' => 'Softening Point', 'value' => '52C'],
            ],
            'summary' => [
                ['label' => 'Jenis Produk', 'value' => 'Bitumen termodifikasi'],
                ['label' => 'Aplikasi', 'value' => 'Overlay, lapis aus, binder course'],
                ['label' => 'Temperatur Pencampuran', 'value' => '150 - 165C'],
            ],
            'documents' => [
                ['title' => 'Spec Sheet Xolabit Bitumen', 'url' => 'https://aspalemulsi.com/docs/xolabit-bitumen-spec-sheet.pdf'],
            ],
            'shopee_url' => 'https://shopee.co.id',
            'tokopedia_url' => 'https://tokopedia.com',
            'whatsapp_url' => 'https://wa.me/6281234567890?text=Halo%20saya%20mau%20order%20Xolabit%20Bitumen',
            'status' => 'published',
            'published_at' => '2025-05-05 09:00:00',
        ],
        [
            'name' => 'Asphaltic Plug',
            'slug' => 'asphaltic-plug',
            'short_description' => 'Sistem sambungan ekspansi yang fleksibel dan tahan beban berat, cocok untuk jembatan dan akses industri.',
            'description' => <<<'HTML'
<p>Asphaltic Plug dikembangkan untuk sambungan ekspansi jembatan dan loading dock yang menuntut fleksibilitas tinggi. Komposisi polimer elastomerik membuatnya menahan gerakan termal tanpa retak.</p>
<p>Produk dikirim dalam bentuk block pre-packaged sehingga mudah dipanaskan ulang di site.</p>
<ul>
    <li>Menahan beban trailer berat sampai 80 ton.</li>
    <li>Elastis pada kisaran suhu -10C sampai 60C.</li>
    <li>Memiliki ketahanan abrasi tinggi untuk area berulang gesekan.</li>
</ul>
HTML,
            'cover_image_url' => '/uploads/product-cold-mix-patch-pro.png',
            'hero_badge' => 'Sambungan Ekspansi',
            'hero_eyebrow' => 'Joint Seal Flex',
            'highlights' => [
                'Fleksibel mengikuti gerakan sambungan ekspansi.',
                'Ketahanan abrasi tinggi untuk traffic berat.',
                'Instalasi cepat menggunakan block pre-packaged.',
            ],
            'specs' => [
                ['label' => 'Kemasan', 'value' => 'Block 25 kg'],
                ['label' => 'Rentang Suhu Kerja', 'value' => '-10C sampai 60C'],
                ['label' => 'Kebutuhan Material', 'value' => '8 - 10 kg per meter'],
            ],
            'summary' => [
                ['label' => 'Jenis Produk', 'value' => 'Asphaltic plug joint'],
                ['label' => 'Lokasi Ideal', 'value' => 'Jembatan, loading dock, akses industri'],
                ['label' => 'Metode Aplikasi', 'value' => 'Heating pot dan pouring'],
            ],
            'documents' => [
                ['title' => 'Manual Instalasi Asphaltic Plug', 'url' => 'https://aspalemulsi.com/docs/asphaltic-plug-installation-manual.pdf'],
            ],
            'shopee_url' => 'https://shopee.co.id',
            'tokopedia_url' => 'https://tokopedia.com',
            'whatsapp_url' => 'https://wa.me/6281234567890?text=Halo%20saya%20mau%20order%20Asphaltic%20Plug',
            'status' => 'published',
            'published_at' => '2025-05-03 09:00:00',
        ],
        [
            'name' => 'Aspal Membran',
            'slug' => 'aspal-membran',
            'short_description' => 'Membran torch-on dengan daya tahan tinggi untuk proteksi struktur beton terhadap kelembapan dan retak.',
            'description' => <<<'HTML'
<p>Aspal Membran torch-on kami menggunakan reinforcement polyester non woven sehingga tahan sobek dan stabil secara dimensional. Cocok untuk podium, basement, hingga rooftop komersial.</p>
<p>Membran disertai lapisan film anti sticking sehingga mudah dipasang di lapangan dan kompatibel dengan finishing screed ataupun topping keramik.</p>
HTML,
            'cover_image_url' => '/uploads/product-aspal-membran.png',
            'hero_badge' => 'Proteksi Struktur',
            'hero_eyebrow' => 'Torch-on Multi Layer',
            'highlights' => [
                'Reinforcement polyester non woven meningkatkan kekuatan tarik.',
                'Lapisan anti root option untuk area taman rooftop.',
                'Tahan air hingga tekanan 5 bar berdasarkan uji internal.',
            ],
            'specs' => [
                ['label' => 'Ketebalan', 'value' => '3 mm dan 4 mm'],
                ['label' => 'Lebar Gulungan', 'value' => '1 meter'],
                ['label' => 'Panjang Gulungan', 'value' => '10 meter'],
            ],
            'summary' => [
                ['label' => 'Jenis Produk', 'value' => 'Torch-on membrane'],
                ['label' => 'Aplikasi', 'value' => 'Podium, rooftop, basement'],
                ['label' => 'Metode', 'value' => 'Torch on dengan burner gas'],
            ],
            'documents' => [
                ['title' => 'Data Sheet Aspal Membran', 'url' => 'https://aspalemulsi.com/docs/aspal-membran-data-sheet.pdf'],
                ['title' => 'Panduan Torch On', 'url' => 'https://aspalemulsi.com/docs/aspal-membran-torch-on-guide.pdf'],
            ],
            'shopee_url' => 'https://shopee.co.id',
            'tokopedia_url' => 'https://tokopedia.com',
            'whatsapp_url' => 'https://wa.me/6281234567890?text=Halo%20saya%20mau%20order%20Aspal%20Membran',
            'status' => 'published',
            'published_at' => '2025-05-02 09:00:00',
        ],
        [
            'name' => 'Slurry Seal Mix',
            'slug' => 'slurry-seal-mix',
            'short_description' => 'Campuran slurry siap pakai untuk rejuvenasi permukaan jalan dengan hasil halus dan tahan lama.',
            'description' => <<<'HTML'
<p>Slurry Seal Mix dibuat dari agregat mineral terkalibrasi dan emulsi latex yang dikemas siap pakai. Dengan penambahan air minimum, tim lapangan bisa langsung mengaplikasikan tanpa perlu laboratorium kompleks.</p>
<p>Cocok untuk jalan lingkungan, area pabrik, hingga akses kampus yang membutuhkan lapis tipis dengan tekstur halus.</p>
HTML,
            'cover_image_url' => '/uploads/product-slurry-seal-mix.png',
            'hero_badge' => 'Ready Mix',
            'hero_eyebrow' => 'Rejuvenasi Permukaan',
            'highlights' => [
                'Campuran siap pakai, tinggal tambah air sesuai kebutuhan.',
                'Tekstur halus meningkatkan kenyamanan berkendara.',
                'Mengandung additive anti slip untuk jalur pedestrian.',
            ],
            'specs' => [
                ['label' => 'Kemasan', 'value' => 'Sak 25 kg'],
                ['label' => 'Kebutuhan Material', 'value' => '1 sak menutup 8 - 10 m2 @3 mm'],
                ['label' => 'Waktu Curing', 'value' => '2 - 3 jam'],
            ],
            'summary' => [
                ['label' => 'Jenis Produk', 'value' => 'Slurry cold mix'],
                ['label' => 'Penggunaan', 'value' => 'Perawatan jalan ringan dan akses fasilitas'],
                ['label' => 'Peralatan', 'value' => 'Squeegee atau spreader machine'],
            ],
            'documents' => [
                ['title' => 'Mix Design Template', 'url' => 'https://aspalemulsi.com/docs/slurry-seal-mix-design-template.xlsx'],
            ],
            'shopee_url' => 'https://shopee.co.id',
            'tokopedia_url' => 'https://tokopedia.com',
            'whatsapp_url' => 'https://wa.me/6281234567890?text=Halo%20saya%20mau%20order%20Slurry%20Seal%20Mix',
            'status' => 'published',
            'published_at' => '2025-05-01 09:00:00',
        ],
        [
            'name' => 'Cold Mix Patch Pro',
            'slug' => 'cold-mix-patch-pro',
            'short_description' => 'Campuran dingin siap pakai untuk tambal cepat lubang jalan dengan ketahanan tinggi.',
            'description' => <<<'HTML'
<p>Cold Mix Patch Pro memudahkan tim maintenance melakukan perbaikan darurat tanpa menghentikan lalu lintas. Cukup buka sak, tuang ke lubang, padatkan, dan jalur siap dilalui kembali.</p>
<ul>
    <li>Partikel agregat bergradasi rapat menjaga hasil padat stabil.</li>
    <li>Aditif elastomerik membuat patch tahan deformasi akibat beban berat.</li>
    <li>Dapat diaplikasikan di suhu rendah maupun area lembap.</li>
</ul>
HTML,
            'cover_image_url' => '/uploads/product-asphaltic-plug.png',
            'hero_badge' => 'Perbaikan Darurat',
            'hero_eyebrow' => 'Tambal Cepat Infrastruktur',
            'highlights' => [
                'Siap pakai tanpa pemanasan tambahan.',
                'Pengerasan adaptif mengikuti suhu lingkungan.',
                'Tahan beban kendaraan berat hingga 80 ton.',
            ],
            'specs' => [
                ['label' => 'Kemasan', 'value' => 'Sak 25 kg'],
                ['label' => 'Aplikasi', 'value' => 'Patch dan overlay lokal'],
                ['label' => 'Lead Time', 'value' => 'Ready stock'],
            ],
            'summary' => [
                ['label' => 'Jenis Produk', 'value' => 'Aspal cold mix'],
                ['label' => 'Aditif', 'value' => 'Polimer elastomerik'],
                ['label' => 'Kebutuhan', 'value' => '1 sak ≈ 0.4 m² @5 cm'],
            ],
            'documents' => [
                ['title' => 'Cold Mix Data Sheet', 'url' => 'https://aspalemulsi.com/docs/cold-mix-patch-pro-data-sheet.pdf'],
                ['title' => 'Manual Aplikasi Cold Mix', 'url' => 'https://aspalemulsi.com/docs/cold-mix-patch-pro-application-manual.pdf'],
            ],
            'shopee_url' => 'https://shopee.co.id',
            'tokopedia_url' => 'https://tokopedia.com',
            'whatsapp_url' => 'https://wa.me/6281234567890?text=Halo%20saya%20mau%20order%20Cold%20Mix%20Patch%20Pro',
            'status' => 'published',
            'published_at' => '2025-04-15 09:00:00',
        ],
    ];

    foreach ($products as $product) {
        $productStatement->execute([
            'name' => $product['name'],
            'slug' => $product['slug'],
            'short_description' => $product['short_description'],
            'description' => $product['description'],
            'cover_image_url' => $product['cover_image_url'],
            'hero_badge' => $product['hero_badge'] ?? null,
            'hero_eyebrow' => $product['hero_eyebrow'] ?? null,
            'highlights_json' => isset($product['highlights']) ? json_encode($product['highlights'], JSON_UNESCAPED_UNICODE) : null,
            'specs_json' => isset($product['specs']) ? json_encode($product['specs'], JSON_UNESCAPED_UNICODE) : null,
            'summary_json' => isset($product['summary']) ? json_encode($product['summary'], JSON_UNESCAPED_UNICODE) : null,
            'documents_json' => isset($product['documents']) ? json_encode($product['documents'], JSON_UNESCAPED_UNICODE) : null,
            'shopee_url' => $product['shopee_url'],
            'tokopedia_url' => $product['tokopedia_url'],
            'whatsapp_url' => $product['whatsapp_url'],
            'status' => $product['status'],
            'published_at' => $product['published_at'],
        ]);
    }

    $fetchProductId = $pdo->prepare('SELECT id FROM products WHERE slug = :slug LIMIT 1');

    $productIds = [];
    foreach ($products as $product) {
        $fetchProductId->execute(['slug' => $product['slug']]);
        $productIds[$product['slug']] = (int) $fetchProductId->fetchColumn();
    }

    $serviceStatement = $pdo->prepare(
        'INSERT INTO services (name, slug, short_description, description, cover_image_url, status, published_at)
         VALUES (:name, :slug, :short_description, :description, :cover_image_url, :status, :published_at)
         ON DUPLICATE KEY UPDATE short_description = VALUES(short_description), description = VALUES(description), cover_image_url = VALUES(cover_image_url), status = VALUES(status), published_at = VALUES(published_at)'
    );

    $services = [
        [
            'name' => 'Pengerjaan Slurry Seal',
            'slug' => 'pengerjaan-slurry-seal',
            'short_description' => 'Lapisan slurry berbasis emulsi dingin untuk menutup retak rambut, memulihkan tekstur, dan mengembalikan daya cengkeram permukaan jalan.',
            'description' => <<<'HTML'
<p>Tim bersertifikasi kami menangani slurry seal mulai dari pembersihan mekanis, crack sealing, hingga aplikasi dua lapis slurry. Formulasi emulsi disesuaikan dengan karakter agregat lokal sehingga hasilnya merata dan tahan aus.</p>
<ul>
    <li>Survey lokasi dan pengambilan sampel agregat sebelum mixing.</li>
    <li>Pengaturan traffic management agar downtime minim.</li>
    <li>Quality control onsite menggunakan pengukuran kekasaran dan skid resistance.</li>
</ul>
<p>Layanan ini cocok untuk jalan lingkungan, kawasan industri, dan fasilitas kampus yang butuh peremajaan cepat tanpa mengganti struktur dasar.</p>
HTML,
            'cover_image_url' => '/uploads/service-slurry-seal.jpeg',
            'status' => 'published',
            'published_at' => '2025-05-06 08:00:00',
        ],
        [
            'name' => 'Aplikasi Waterproofing Dak & Atap',
            'slug' => 'aplikasi-waterproofing-dak-atap',
            'short_description' => 'Perlindungan dak dan atap beton menggunakan emulsi atau membran torch-on sesuai kondisi lapangan.',
            'description' => <<<'HTML'
<p>Kami menawarkan solusi waterproofing berbasis emulsi maupun membran torch-on. Tim melakukan inspeksi kelembapan, perbaikan detail drain, hingga pengujian rendam setelah aplikasi.</p>
<ul>
    <li>Pilihan material emulsi dingin atau membran torch-on multi layer.</li>
    <li>Pekerjaan detail di area talang, expansion joint, dan penetrasi pipa.</li>
    <li>Dokumentasi harian lengkap dengan foto progress dan hasil pengujian.</li>
</ul>
HTML,
            'cover_image_url' => '/uploads/service-waterproofing.jpeg',
            'status' => 'published',
            'published_at' => '2025-05-05 08:00:00',
        ],
        [
            'name' => 'Pemasangan Membran Aspal',
            'slug' => 'pemasangan-membran-aspal',
            'short_description' => 'Pemasangan membran aspal multi-layer untuk podium, basement, dan rooftop sehingga tahan bocor lebih dari 10 tahun.',
            'description' => <<<'HTML'
<p>Tenaga ahli kami memasang membran aspal multi-layer lengkap dengan priming, torching, dan finishing protective screed. Standar kerja memastikan lapisan rapi, kuat, dan siap finishing akhir.</p>
<ul>
    <li>Preparasi permukaan dengan grinder dan vacuum industrial.</li>
    <li>Overlap minimal 10 cm dengan detail corner treatment khusus.</li>
    <li>Final inspection bersama owner sebelum penutupan pekerjaan.</li>
</ul>
HTML,
            'cover_image_url' => '/uploads/service-membran.png',
            'status' => 'published',
            'published_at' => '2025-05-04 08:00:00',
        ],
    ];

    foreach ($services as $service) {
        $serviceStatement->execute([
            'name' => $service['name'],
            'slug' => $service['slug'],
            'short_description' => $service['short_description'],
            'description' => $service['description'],
            'cover_image_url' => $service['cover_image_url'],
            'status' => $service['status'],
            'published_at' => $service['published_at'],
        ]);
    }

    $fetchServiceId = $pdo->prepare('SELECT id FROM services WHERE slug = :slug LIMIT 1');

    $serviceIds = [];
    foreach ($services as $service) {
        $fetchServiceId->execute(['slug' => $service['slug']]);
        $serviceIds[$service['slug']] = (int) $fetchServiceId->fetchColumn();
    }

    $portfolioStatement = $pdo->prepare(
        'INSERT INTO portfolio_items (title, slug, category, description, image_url, location, featured, status, showcased_at)
         VALUES (:title, :slug, :category, :description, :image_url, :location, :featured, :status, :showcased_at)
         ON DUPLICATE KEY UPDATE category = VALUES(category), description = VALUES(description), image_url = VALUES(image_url), location = VALUES(location), featured = VALUES(featured), status = VALUES(status), showcased_at = VALUES(showcased_at)'
    );

    $portfolioItems = [
        [
            'title' => 'Proyek Area Jawa Timur',
            'slug' => 'proyek-area-jawa-timur',
            'category' => 'Pengaspalan Jalan',
            'description' => 'Pengaspalan jalan raya dan jembatan CV. Safara Jaya Prakarsa dengan finishing halus dan teruji.',
            'image_url' => '/uploads/portfolio-jawa-timur.jpeg',
            'location' => 'Surabaya, Jawa Timur',
            'featured' => 1,
            'status' => 'published',
            'showcased_at' => '2025-03-22 00:00:00',
        ],
        [
            'title' => 'Waterproofing Gedung Bertingkat',
            'slug' => 'waterproofing-gedung-bertingkat',
            'category' => 'Waterproofing',
            'description' => 'Penerapan membran torch-on pada dak beton untuk proteksi jangka panjang dari kebocoran.',
            'image_url' => '/uploads/portfolio-waterproofing.jpeg',
            'location' => 'Jakarta Pusat',
            'featured' => 0,
            'status' => 'published',
            'showcased_at' => '2025-03-18 00:00:00',
        ],
        [
            'title' => 'Slurry Seal Kawasan Perumahan',
            'slug' => 'slurry-seal-kawasan-perumahan',
            'category' => 'Slurry Seal',
            'description' => 'Penyegaran permukaan jalan lingkungan dengan campuran slurry agar lebih rapi dan aman.',
            'image_url' => '/uploads/portfolio-slurry-perumahan.jpeg',
            'location' => 'Sidoarjo, Jawa Timur',
            'featured' => 0,
            'status' => 'published',
            'showcased_at' => '2025-03-12 00:00:00',
        ],
        [
            'title' => 'Perbaikan Akses Industri',
            'slug' => 'perbaikan-akses-industri',
            'category' => 'Joint Repair',
            'description' => 'Pemakaian asphaltic plug untuk sambungan ekspansi gudang logistik dengan lalu lintas berat.',
            'image_url' => '/uploads/portfolio-akses-industri.jpeg',
            'location' => 'Gresik, Jawa Timur',
            'featured' => 0,
            'status' => 'published',
            'showcased_at' => '2025-03-05 00:00:00',
        ],
        [
            'title' => 'Aspal Emulsi Kampus Negeri',
            'slug' => 'aspal-emulsi-kampus-negeri',
            'category' => 'Pemeliharaan Kampus',
            'description' => 'Pelapisan ulang akses kampus dengan emulsi dingin agar aman di segala cuaca.',
            'image_url' => '/uploads/portfolio-kampus-negeri.jpeg',
            'location' => 'Malang, Jawa Timur',
            'featured' => 0,
            'status' => 'published',
            'showcased_at' => '2025-03-01 00:00:00',
        ],
    ];

    foreach ($portfolioItems as $item) {
        $portfolioStatement->execute([
            'title' => $item['title'],
            'slug' => $item['slug'],
            'category' => $item['category'],
            'description' => $item['description'],
            'image_url' => $item['image_url'],
            'location' => $item['location'],
            'featured' => $item['featured'],
            'status' => $item['status'],
            'showcased_at' => $item['showcased_at'],
        ]);
    }

    $fetchPortfolioId = $pdo->prepare('SELECT id FROM portfolio_items WHERE slug = :slug LIMIT 1');
    $portfolioIds = [];
    foreach ($portfolioItems as $item) {
        $fetchPortfolioId->execute(['slug' => $item['slug']]);
        $portfolioIds[$item['slug']] = (int) $fetchPortfolioId->fetchColumn();
    }

    $testimonialStatement = $pdo->prepare(
        'INSERT INTO testimonials (client_name, company, role, quote, rating, avatar_url, status, published_at)
         VALUES (:client_name, :company, :role, :quote, :rating, :avatar_url, :status, :published_at)
         ON DUPLICATE KEY UPDATE company = VALUES(company), role = VALUES(role), quote = VALUES(quote), rating = VALUES(rating), avatar_url = VALUES(avatar_url), status = VALUES(status), published_at = VALUES(published_at)'
    );

    $testimonials = [
        [
            'client_name' => 'Andri Wibisono',
            'company' => 'PT Tripalindo Transmix',
            'role' => 'Facility Manager',
            'quote' => 'Durasi shutdown pabrik cuma 48 jam, hasil slurry seal nya mulus dan anti slip. Support teknisnya responsif banget.',
            'rating' => 5,
            'avatar_url' => '/uploads/testimonial-20251107184242-ef47b1d3.webp',
            'status' => 'published',
            'published_at' => '2025-03-18 00:00:00',
        ],
        [
            'client_name' => 'Rizka Nuraini',
            'company' => 'DPU Bina Marga Jatim',
            'role' => 'Procurement',
            'quote' => 'Proyek waterproofing 12.000 m2 selesai sesuai timeline, tim QC mereka detail banget ngasih laporan harian.',
            'rating' => 5,
            'avatar_url' => '/uploads/testimonial-20251107184255-46f03a40.png',
            'status' => 'published',
            'published_at' => '2025-03-10 00:00:00',
        ],
        [
            'client_name' => 'Hendra Kusuma',
            'company' => 'CV Mega Jaya Amerta',
            'role' => 'Project Lead',
            'quote' => 'Vendor paling fleksibel buat variasi emulsi. Kalau ada revisi desain, mereka langsung adjust di batch berikutnya.',
            'rating' => 4,
            'avatar_url' => null,
            'status' => 'published',
            'published_at' => '2025-03-05 00:00:00',
        ],
    ];

    foreach ($testimonials as $testimonial) {
        $testimonialStatement->execute([
            'client_name' => $testimonial['client_name'],
            'company' => $testimonial['company'],
            'role' => $testimonial['role'],
            'quote' => $testimonial['quote'],
            'rating' => $testimonial['rating'],
            'avatar_url' => $testimonial['avatar_url'],
            'status' => $testimonial['status'],
            'published_at' => $testimonial['published_at'],
        ]);
    }

    $seoStatement = $pdo->prepare(
        'INSERT INTO seo_meta (owner_type, owner_id, meta_title, meta_description, meta_keywords, og_title, og_description, og_image_url)
         VALUES (:owner_type, :owner_id, :meta_title, :meta_description, :meta_keywords, :og_title, :og_description, :og_image_url)
         ON DUPLICATE KEY UPDATE meta_title = VALUES(meta_title), meta_description = VALUES(meta_description), meta_keywords = VALUES(meta_keywords), og_title = VALUES(og_title), og_description = VALUES(og_description), og_image_url = VALUES(og_image_url)'
    );

    foreach ($categories as $category) {
        $categoryId = $categoryIds[$category['slug']] ?? null;
        if ($categoryId === null) {
            continue;
        }

        $seoStatement->execute([
            'owner_type' => 'category',
            'owner_id' => $categoryId,
            'meta_title' => $category['name'] . ' | Aspal Emulsi Indonesia',
            'meta_description' => $category['description'],
            'meta_keywords' => implode(', ', [$category['name'], 'aspal emulsi', 'infrastruktur jalan']),
            'og_title' => $category['name'],
            'og_description' => $category['description'],
            'og_image_url' => null,
        ]);
    }

    foreach ($posts as $post) {
        $postId = $postIds[$post['slug']] ?? null;
        if ($postId === null) {
            continue;
        }

        $rawSummary = strip_tags($post['summary'] ?? $post['content'] ?? '');
        $metaDescription = function_exists('mb_substr')
            ? mb_substr($rawSummary, 0, 155)
            : substr($rawSummary, 0, 155);

        $seoStatement->execute([
            'owner_type' => 'post',
            'owner_id' => $postId,
            'meta_title' => $post['title'] . ' | Aspal Emulsi',
            'meta_description' => $metaDescription,
            'meta_keywords' => implode(', ', ['aspal emulsi', 'material jalan', $post['title']]),
            'og_title' => $post['title'],
            'og_description' => strip_tags($post['summary'] ?? ''),
            'og_image_url' => $post['cover_image_url'],
        ]);
    }

    foreach ($products as $product) {
        $productId = $productIds[$product['slug']] ?? null;
        if ($productId === null) {
            continue;
        }

        $rawSummary = strip_tags($product['short_description'] ?? $product['description'] ?? '');
        $metaDescription = function_exists('mb_substr')
            ? mb_substr($rawSummary, 0, 155)
            : substr($rawSummary, 0, 155);

        $seoStatement->execute([
            'owner_type' => 'product',
            'owner_id' => $productId,
            'meta_title' => $product['name'] . ' | Aspal Emulsi',
            'meta_description' => $metaDescription,
            'meta_keywords' => implode(', ', ['produk aspal', 'aspal emulsi', $product['name']]),
            'og_title' => $product['name'],
            'og_description' => strip_tags($product['short_description'] ?? ''),
            'og_image_url' => $product['cover_image_url'],
        ]);
    }

    foreach ($services as $service) {
        $serviceId = $serviceIds[$service['slug']] ?? null;
        if ($serviceId === null) {
            continue;
        }

        $rawSummary = strip_tags($service['short_description'] ?? $service['description'] ?? '');
        $metaDescription = function_exists('mb_substr')
            ? mb_substr($rawSummary, 0, 155)
            : substr($rawSummary, 0, 155);

        $seoStatement->execute([
            'owner_type' => 'service',
            'owner_id' => $serviceId,
            'meta_title' => $service['name'] . ' | Aspal Emulsi',
            'meta_description' => $metaDescription,
            'meta_keywords' => implode(', ', ['layanan aspal', 'aspal emulsi', $service['name']]),
            'og_title' => $service['name'],
            'og_description' => strip_tags($service['short_description'] ?? ''),
            'og_image_url' => $service['cover_image_url'],
        ]);
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_reset_expires (expires_at),
            CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if ($pdo->inTransaction()) {
        $pdo->commit();
    }

    echo "Database schema & seed selesai.\n";
    echo sprintf("Roles: %d\n", count($roles));
    echo sprintf("Users: %d\n", count($users));
    echo sprintf("Categories: %d\n", count($categories));
    echo sprintf("Posts: %d\n", count($posts));
    echo sprintf("Products: %d\n", count($products));
    echo "Settings: 1\n";
    echo sprintf("Services: %d\n", count($services));
    echo sprintf("Portfolio: %d\n", count($portfolioItems));
    echo sprintf("Testimonials: %d\n", count($testimonials));
} catch (Throwable $throwable) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
    }
    echo 'Install gagal: ' . $throwable->getMessage();
}


