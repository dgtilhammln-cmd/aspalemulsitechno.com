# ASPAL EMULSI TECHNO.COM - Landing Page

Landing page profesional untuk perusahaan aspal emulsi dengan desain modern, animasi smooth, dan SEO-optimized.

## 🚀 Fitur

- ✅ **Hero Carousel Slider** - 3 slides dengan auto-play & fade transition
- ✅ **Responsive Design** - Tampil sempurna di semua device
- ✅ **Scroll Animations** - Smooth scroll + AOS (Animate On Scroll)
- ✅ **Staggered Overlay Images** - 3 images dengan layout diagonal yang animatif
- ✅ **SEO Optimized** - Meta tags lengkap untuk ranking Google
- ✅ **Fast Loading** - CDN untuk semua library
- ✅ **Modern UI/UX** - Gradient orange/yellow, card-based layout
- ✅ **Social Integration** - WhatsApp, Instagram, TikTok links
- ✅ **E-commerce Ready** - Link ke Shopee & Tokopedia
- ✅ **Floating WhatsApp Button** - Sticky CTA button

## 📦 Teknologi

- **HTML5** - Semantic markup
- **CSS3** - Custom properties, gradients, animations
- **Bootstrap 5.3.2** - Grid system & components
- **Font Awesome 6.5.1** - Icons
- **Google Fonts (Poppins)** - Typography
- **AOS Library** - Scroll animations
- **Vanilla JavaScript** - No jQuery, pure JS

## 🎨 Customize

### 1. Ganti Contact Info

Edit `index.html`, cari dan ganti:

```html
<!-- WhatsApp -->
https://wa.me/6281234567890  → Ganti dengan nomor WA kamu

<!-- Instagram -->
https://instagram.com/aspalemulsi → Ganti dengan handle Instagram kamu

<!-- TikTok -->
https://tiktok.com/@aspalemulsi → Ganti dengan handle TikTok kamu
```

### 2. Ganti Logo

Dua cara:

**Cara 1: Pakai logo text (sudah ada)**
```css
/* Di styles.css, edit class .logo-icon */
.logo-icon {
    /* Ganti "AE" di HTML dengan inisial kamu */
}
```

**Cara 2: Pakai logo image**
```html
<!-- Ganti di index.html -->
<div class="logo-icon">AE</div>
<!-- Jadi: -->
<img src="logo.png" alt="Logo" style="height: 50px;">
```

### 3. Ganti Gambar

Semua gambar saat ini pakai Unsplash placeholder. Ganti dengan gambar kamu:

**Hero Carousel (3 slides):**
```html
<!-- Slide 1 - Main Image -->
<img src="https://images.unsplash.com/photo-...">
<!-- Ganti jadi: -->
<img src="images/hero-slide-1-main.jpg">

<!-- Overlay Images (3 images per slide) -->
<img src="images/hero-slide-1-overlay-1.jpg">
<img src="images/hero-slide-1-overlay-2.jpg">
<img src="images/hero-slide-1-overlay-3.jpg">

<!-- Professional Portrait -->
<img src="images/hero-slide-1-portrait.jpg">
```

**Rekomendasi ukuran gambar:**
- Main Image: 1200x550px (landscape)
- Overlay Images: 300x200px (small rectangles)
- Professional Portrait: 500x550px (portrait/square)
- Service/Product Cards: 400x250px

**Folder structure recommended:**
```
project/
├── index.html
├── styles.css
├── script.js
├── images/
│   ├── logo.png
│   ├── hero/
│   │   ├── slide-1-main.jpg
│   │   ├── slide-1-overlay-1.jpg
│   │   ├── slide-1-portrait.jpg
│   │   └── ...
│   ├── services/
│   │   ├── service-1.jpg
│   │   └── ...
│   └── products/
│       ├── product-1.jpg
│       └── ...
└── README.md
```

### 4. Edit Konten Text

Semua text ada di `index.html`. Cari section yang mau diubah:

- **Services**: Cari `<!-- Service 1 -->` sampai `<!-- Service 6 -->`
- **Products**: Cari `<!-- Product 1 -->` sampai `<!-- Product 4 -->`
- **Clients**: Edit di `<ul class="client-list">`
- **About**: Edit di `.about-description`

### 5. Ubah Warna (Optional)

Edit `styles.css` di bagian `:root`:

```css
:root {
    --primary-orange: #FF8C00;  /* Warna utama orange */
    --primary-yellow: #FFD700;  /* Warna accent yellow */
    --black-bg: #000000;         /* Background hitam */
    --white-text: #FFFFFF;       /* Text putih */
    --gray-text: #CCCCCC;        /* Text abu-abu */
}
```

### 6. Customize Carousel

**Ubah kecepatan slide:**
```html
<!-- Di index.html, cari: -->
<div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
<!-- Ganti "5000" (5 detik) ke nilai lain, misal "7000" untuk 7 detik -->
```

**Tambah/kurangi slide:**
1. Copy-paste salah satu `<div class="carousel-item">` di index.html
2. Ganti gambar-gambarnya
3. Update indicators:
```html
<!-- Tambah button indicator baru: -->
<button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
```

**Matikan auto-play:**
```html
<!-- Hapus atau ubah: -->
data-bs-ride="carousel"
<!-- Jadi: -->
data-bs-ride="false"
```

### 7. SEO Meta Tags

Edit `index.html` di bagian `<head>`:

```html
<title>Judul Website Kamu</title>
<meta name="description" content="Deskripsi singkat...">
<meta name="keywords" content="keyword1, keyword2, keyword3">
```

## 🌐 Deploy

### Option 1: Netlify (Recommended)
1. Upload semua file ke GitHub
2. Connect ke Netlify
3. Deploy otomatis!

### Option 2: Vercel
1. Upload ke GitHub
2. Import project di Vercel
3. Done!

### Option 3: Hosting Tradisional (cPanel, dll)
1. Upload semua file via FTP
2. Pastikan `index.html` di root folder
3. Done!

## 📱 Testing

- ✅ Desktop (Chrome, Firefox, Safari, Edge)
- ✅ Mobile (iOS Safari, Chrome Android)
- ✅ Tablet (iPad, Android tablets)

## 🔧 Maintenance

### Update Content
Edit langsung di `index.html` - no build process needed!

### Update Styles
Edit `styles.css` - changes apply instantly

### Update Animations
Edit `script.js` - refresh browser to see changes

## 📞 Support

Butuh bantuan? Kontak:
- Email: support@aspalemulsi.com
- WhatsApp: 0812-3456-7890

## 📄 License

©2025 HVM Digital ID. All rights reserved.

---

**Pro Tips:**
1. Compress images sebelum upload (gunakan TinyPNG)
2. Test di Google PageSpeed Insights
3. Setup Google Analytics untuk tracking
4. Daftar di Google Search Console
5. Buat sitemap.xml untuk SEO

**Struktur File:**
```
index.html     → Main HTML file
styles.css     → All styling
script.js      → Animations & interactions
README.md      → Documentation (this file)
```

Selamat menggunakan! 🚀

