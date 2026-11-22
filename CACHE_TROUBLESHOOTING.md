# Troubleshooting CSS Cache di Live Site

## Masalah
CSS tidak terupdate di live site meskipun sudah diubah di server.

## Solusi

### 1. Clear Browser Cache (Paling Mudah)
- **Chrome/Edge**: Tekan `Ctrl + Shift + Delete` (Windows) atau `Cmd + Shift + Delete` (Mac), pilih "Cached images and files", lalu "Clear data"
- **Firefox**: Tekan `Ctrl + Shift + Delete`, pilih "Cache", lalu "Clear Now"
- **Safari**: Tekan `Cmd + Option + E` untuk clear cache

### 2. Hard Refresh Browser
- **Windows**: Tekan `Ctrl + Shift + R` atau `Ctrl + F5`
- **Mac**: Tekan `Cmd + Shift + R`
- Ini akan memaksa browser untuk reload semua assets termasuk CSS

### 3. Clear Cache dengan Developer Tools
1. Buka Developer Tools (F12)
2. Klik kanan pada tombol refresh
3. Pilih "Empty Cache and Hard Reload"

### 4. Update File CSS di Server
Setelah update CSS via file manager, pastikan:
- File benar-benar tersimpan
- Timestamp file berubah (bisa dicek via FTP/file manager)
- Jika timestamp tidak berubah, lakukan **touch** pada file CSS

### 5. Touch File CSS (Update Timestamp)
Jika update via file manager tidak mengubah timestamp, lakukan touch manual:

**Via FTP/File Manager:**
- Buka file `styles.css`
- Simpan lagi (tanpa perubahan) untuk update timestamp

**Via SSH (jika tersedia):**
```bash
touch styles.css
touch animations.css
```

**Via PHP Script (jika diperlukan):**
Buat file `admin/touch-css.php` untuk update timestamp:
```php
<?php
// Hanya untuk admin yang sudah login
require __DIR__ . '/bootstrap.php';
if (!is_admin_logged_in()) {
    die('Unauthorized');
}

$stylesPath = __DIR__ . '/../styles.css';
$animationsPath = __DIR__ . '/../animations.css';

if (file_exists($stylesPath)) {
    touch($stylesPath);
    echo "styles.css timestamp updated<br>";
}

if (file_exists($animationsPath)) {
    touch($animationsPath);
    echo "animations.css timestamp updated<br>";
}

echo "Done! Cache busting akan bekerja dengan timestamp baru.";
```

### 6. Verifikasi Cache Busting
Setelah update CSS, cek di browser:
1. Buka Developer Tools (F12)
2. Tab "Network"
3. Reload halaman
4. Cek file `styles.css` - seharusnya ada query string `?v=timestamp`
5. Pastikan timestamp di URL sesuai dengan waktu update terakhir

### 7. Clear Server Cache (Jika Menggunakan)
Jika menggunakan cache server (OPcache, Redis, dll):
- Clear OPcache PHP
- Clear Redis cache
- Clear CDN cache (jika menggunakan CDN)

### 8. Cek .htaccess
Pastikan file `.htaccess` sudah diupdate dengan:
- Cache time untuk CSS: 1 jam (bukan 1 bulan)
- Cache-Control header: `max-age=3600, must-revalidate`

## Cara Kerja Cache Busting

1. File `app/Views/layouts/main.php` membaca timestamp file CSS menggunakan `filemtime()`
2. Timestamp ditambahkan sebagai query string `?v=timestamp` pada URL CSS
3. Browser melihat URL baru (dengan timestamp baru) dan fetch CSS baru
4. Jika file CSS diupdate, timestamp berubah, URL berubah, browser fetch baru

## Catatan Penting

- **Cache busting hanya bekerja jika timestamp file berubah**
- Jika update via file manager tidak mengubah timestamp, cache busting tidak bekerja
- Solusi: Touch file CSS setelah update untuk update timestamp
- Browser cache akan expire setelah 1 jam (sesuai setting di .htaccess)

## Masih Tidak Berfungsi?

1. Pastikan file CSS benar-benar terupdate di server
2. Pastikan timestamp file berubah setelah update
3. Clear browser cache dan hard refresh
4. Cek di incognito/private window
5. Cek apakah ada CDN atau cache server lain
6. Verifikasi query string `?v=timestamp` ada di URL CSS

