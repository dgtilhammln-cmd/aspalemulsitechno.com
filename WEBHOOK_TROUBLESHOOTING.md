# Webhook Troubleshooting - CSS Tidak Terupdate

## Masalah
Webhook sudah jalan dan update, tapi CSS tidak terupdate di live site.

## Kemungkinan Penyebab

### 1. Webhook Tidak Melakukan Git Pull dengan Benar
- Webhook mungkin tidak menjalankan `git pull` dengan benar
- Webhook mungkin tidak memiliki permission untuk git pull
- Webhook mungkin pull ke branch yang salah

### 2. File CSS Tidak Benar-Benar Terupdate di Server
- File CSS di server tidak di-replace setelah git pull
- File permissions atau ownership issue
- File CSS mungkin di-ignore oleh git atau webhook

### 3. Cache Server Tidak Di-Clear
- OPcache masih cache file CSS lama
- File cache tidak di-clear
- CDN cache tidak di-purge

### 4. File Content Hash Tidak Berubah
- Meskipun menggunakan hash content, file CSS di server mungkin tidak benar-benar terupdate
- Hash tetap sama karena file tidak berubah

## Solusi

### Step 1: Verifikasi Webhook
1. Cek webhook URL di repository settings
2. Cek webhook logs untuk melihat apakah webhook dipanggil
3. Cek apakah webhook menjalankan script dengan benar

### Step 2: Verifikasi File CSS di Server
```bash
# SSH ke server
cd /path/to/aspalemulsi

# Cek apakah file CSS ada
ls -lh styles.css

# Cek git status
git status

# Cek apakah file CSS sudah terupdate
git log --oneline -5 -- styles.css

# Cek hash file CSS
md5sum styles.css

# Bandingkan dengan hash di local
# (dari local, jalankan: md5sum styles.css)
```

### Step 3: Manual Pull di Server
```bash
# SSH ke server
cd /path/to/aspalemulsi

# Pull perubahan terbaru
git pull origin master

# Atau force pull
git fetch origin
git reset --hard origin/master

# Clear OPcache (jika menggunakan PHP OPcache)
php -r "opcache_reset();"

# Touch CSS files untuk update timestamp
touch styles.css animations.css
```

### Step 4: Verifikasi File Content Hash
1. Buka website di browser
2. Buka DevTools (F12) > Network
3. Reload page
4. Cek file `styles.css` - lihat URL, seharusnya ada `?v=hash`
5. Bandingkan hash di URL dengan hash file di server

### Step 5: Test Webhook Script
1. Akses webhook script langsung: `https://yourdomain.com/webhook-deploy.php`
2. Cek log file: `deploy.log`
3. Verifikasi apakah script menjalankan git pull dengan benar

## Debugging Webhook

### 1. Cek Webhook Log
```bash
# Cek log file webhook
tail -f deploy.log

# Atau cek webhook response
curl https://yourdomain.com/webhook-deploy.php
```

### 2. Test Git Pull Manual
```bash
# SSH ke server
cd /path/to/aspalemulsi

# Test git pull
git pull origin master

# Cek output
echo $?

# Cek apakah file CSS terupdate
ls -lh styles.css
git log --oneline -1 -- styles.css
```

### 3. Verifikasi File Permissions
```bash
# Cek file permissions
ls -la styles.css

# Pastikan file bisa di-read oleh web server
chmod 644 styles.css

# Pastikan directory bisa di-write oleh webhook
chmod 755 .
```

### 4. Test Hash Function
```php
<?php
// Test hash function
require 'app/Support/helpers.php';

$cssPath = __DIR__ . '/styles.css';
$hash = asset_version($cssPath);
echo "CSS Hash: $hash\n";
```

## Solusi Webhook Script

### Update Webhook Script
File: `webhook-deploy.php`

1. **Pastikan git pull berjalan dengan benar**
   ```php
   exec('git pull origin master 2>&1', $output, $returnVar);
   ```

2. **Clear OPcache setelah pull**
   ```php
   if (function_exists('opcache_reset')) {
       opcache_reset();
   }
   ```

3. **Touch CSS files**
   ```php
   touch(__DIR__ . '/styles.css');
   touch(__DIR__ . '/animations.css');
   ```

4. **Verify file update**
   ```php
   $cssHash = asset_version(__DIR__ . '/styles.css');
   $log("CSS Hash: $cssHash");
   ```

## Checklist Troubleshooting

- [ ] Webhook dipanggil dengan benar
- [ ] Git pull berjalan dengan benar
- [ ] File CSS terupdate di server
- [ ] File permissions benar
- [ ] OPcache di-clear
- [ ] File content hash berubah
- [ ] Browser cache di-clear
- [ ] CDN cache di-purge (jika menggunakan CDN)

## Langkah-Langkah Manual

Jika webhook tidak bekerja, lakukan manual:

1. **SSH ke server**
   ```bash
   ssh user@server
   ```

2. **Pull perubahan**
   ```bash
   cd /path/to/aspalemulsi
   git pull origin master
   ```

3. **Clear cache**
   ```bash
   # Clear OPcache
   php -r "opcache_reset();"
   
   # Atau restart PHP-FPM
   sudo service php-fpm restart
   ```

4. **Verifikasi**
   ```bash
   # Cek file CSS
   ls -lh styles.css
   
   # Cek hash
   md5sum styles.css
   ```

5. **Test di browser**
   - Buka website
   - Hard refresh (Ctrl + Shift + R)
   - Cek DevTools > Network > CSS file
   - Verifikasi hash di URL

## Catatan Penting

1. **Hash Content vs Timestamp**
   - Sekarang menggunakan hash content untuk cache busting
   - Hash akan berubah jika file content berubah
   - Tidak tergantung pada file timestamp

2. **Webhook Security**
   - Pastikan webhook script aman
   - Gunakan secret token untuk autentikasi
   - Jangan expose webhook URL secara publik

3. **File Permissions**
   - Pastikan webhook memiliki permission untuk git pull
   - Pastikan file CSS bisa di-read oleh web server
   - Pastikan directory bisa di-write oleh webhook

4. **Cache Clearing**
   - Clear OPcache setelah git pull
   - Clear browser cache setelah update
   - Clear CDN cache jika menggunakan CDN

