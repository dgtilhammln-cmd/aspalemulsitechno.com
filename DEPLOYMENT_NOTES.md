# Deployment Notes - Hero Slider CSS Fix

## Masalah
CSS hero slider tidak terupdate di live site meskipun sudah di-push.

## Solusi yang Diterapkan

### 1. Cache Busting untuk CSS
- Menambahkan version query string ke CSS file berdasarkan `filemtime()`
- File CSS sekarang akan auto-update ketika file diubah
- URL CSS: `/styles.css?v=1234567890` (timestamp)

### 2. Cara Update di Live Site

#### Step 1: Pull Perubahan Terbaru di Server
```bash
cd /path/to/aspalemulsi
git pull origin master  # atau git pull origin dev
```

#### Step 2: Pastikan File CSS Sudah Terupdate
```bash
# Cek apakah file styles.css sudah terupdate
ls -lh styles.css
# Pastikan timestamp file sudah baru
```

#### Step 3: Clear Server Cache (jika ada)
- Jika menggunakan OPcache: `opcache_reset()` atau restart PHP-FPM
- Jika menggunakan Redis/Memcached: Clear cache
- Jika menggunakan CDN: Purge cache di CDN

#### Step 4: Clear Browser Cache
- **Hard Refresh**: `Ctrl + Shift + R` (Windows/Linux) atau `Cmd + Shift + R` (Mac)
- **Clear Cache**: Buka DevTools > Network > Disable cache
- **Incognito Mode**: Test di incognito/private mode

### 3. Verifikasi CSS Terupdate

#### Cek di Browser DevTools
1. Buka website di browser
2. Buka DevTools (F12)
3. Tab **Network** > Filter **CSS**
4. Reload page (Ctrl + Shift + R)
5. Klik file `styles.css`
6. Cek **Headers** > **Request URL** - pastikan ada query `?v=...`
7. Cek **Response** - pastikan CSS hero slider sudah terupdate

#### Cek CSS Hero Slider
Search di Response CSS untuk:
```css
@media (min-width: 1400px) {
    .hero-slider-style-1__content {
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
        padding: clamp(90px, 12vw, 160px) 180px clamp(160px, 15vw, 240px) 120px;
    }
}
```

### 4. Troubleshooting

#### Jika CSS Masih Tidak Terupdate:
1. **Cek File di Server**
   ```bash
   grep -A 10 "hero-slider-style-1__content" styles.css
   ```

2. **Cek Git Status**
   ```bash
   git status
   git log --oneline -5 -- styles.css
   ```

3. **Force Pull**
   ```bash
   git fetch origin
   git reset --hard origin/master
   ```

4. **Cek File Permissions**
   ```bash
   chmod 644 styles.css
   ```

5. **Cek Web Server Configuration**
   - Pastikan web server bisa serve file CSS
   - Cek .htaccess (jika ada)
   - Cek Nginx/Apache config

### 5. Perubahan yang Dilakukan

#### File: `app/Support/helpers.php`
- Menambahkan parameter `$version` ke function `asset()`
- Auto-add query string `?v=timestamp` untuk cache busting

#### File: `app/Views/layouts/main.php`
- Menggunakan `filemtime()` untuk generate version CSS
- CSS akan auto-update setiap file diubah

### 6. Commit Info
- **Commit**: `86d5965` - "Fix hero slider centering on wide desktop screens"
- **Date**: Check dengan `git log -1 86d5965`

### 7. Next Steps
1. Pull perubahan terbaru di server
2. Test di browser dengan hard refresh
3. Verifikasi CSS hero slider sudah terupdate
4. Monitor untuk memastikan tidak ada issue

## Catatan Penting
- **Cache busting** sekarang otomatis menggunakan `filemtime()`
- Setiap perubahan CSS akan auto-generate version baru
- Browser akan auto-download CSS baru karena URL berbeda
- Tidak perlu manual update version number

