# Fix Divergent Branches di Server

## Masalah
Saat `git pull origin master` di server, muncul error:
```
hint: You have divergent branches and need to specify how to reconcile them.
fatal: Need to specify how to specify how to reconcile divergent branches.
```

## Penyebab
Branch di server dan branch di remote (GitHub) memiliki commit yang berbeda. Ini bisa terjadi jika:
- Ada perubahan langsung di server (tanpa melalui git)
- Ada perubahan di local yang di-push
- Ada perubahan di server yang di-commit langsung

## Solusi

### Opsi 1: Merge (Recommended untuk Production)
Ini akan menggabungkan perubahan dari remote dengan perubahan di server.

```bash
# Set merge strategy
git config pull.rebase false

# Pull dengan merge
git pull origin master --no-rebase

# Jika ada conflict, resolve conflict lalu:
git add .
git commit -m "Merge remote changes"
```

### Opsi 2: Reset Hard ke Remote (Force Sync)
**WARNING**: Ini akan menghapus semua perubahan lokal di server yang belum di-commit atau di-push.

```bash
# Fetch latest changes
git fetch origin

# Reset hard ke remote master (HAPUS PERUBAHAN LOKAL)
git reset --hard origin/master

# Force update (jika perlu)
git clean -fd
```

### Opsi 3: Rebase (Untuk Development)
Ini akan menempatkan commit lokal setelah commit remote.

```bash
# Set rebase strategy
git config pull.rebase true

# Pull dengan rebase
git pull origin master --rebase

# Jika ada conflict, resolve conflict lalu:
git add .
git rebase --continue
```

## Langkah-Langkah yang Direkomendasikan untuk Production

### Step 1: Backup Perubahan Lokal (jika ada yang penting)
```bash
# Cek perubahan lokal
git status

# Cek commit lokal
git log --oneline -5

# Backup perubahan jika perlu
cp -r . ../backup-$(date +%Y%m%d-%H%M%S)
```

### Step 2: Fetch Latest Changes
```bash
# Fetch tanpa merge
git fetch origin
```

### Step 3: Cek Perbedaan
```bash
# Cek perbedaan antara local dan remote
git log HEAD..origin/master --oneline
git log origin/master..HEAD --oneline
```

### Step 4: Pilih Strategi

#### Jika Tidak Ada Perubahan Penting di Server → Reset Hard
```bash
# Reset hard ke remote (HAPUS PERUBAHAN LOKAL)
git reset --hard origin/master
```

#### Jika Ada Perubahan Penting di Server → Merge
```bash
# Merge dengan remote
git pull origin master --no-rebase

# Jika ada conflict, resolve conflict
git status
# Edit file yang conflict
git add .
git commit -m "Merge remote changes"
```

## Script Otomatis untuk Webhook

Update `webhook-deploy.php` untuk handle divergent branches:

```php
// Step 1: Fetch latest changes
exec('git fetch origin 2>&1', $fetchOutput, $fetchReturn);

// Step 2: Check if branches are divergent
exec('git rev-list --left-right --count HEAD...origin/master 2>&1', $divergentOutput, $divergentReturn);
$divergentCount = trim(implode("\n", $divergentOutput));

// Step 3: If divergent, reset hard to remote (for production)
if ($divergentCount !== '0	0') {
    $log("WARNING: Branches are divergent. Resetting to remote...");
    exec('git reset --hard origin/master 2>&1', $resetOutput, $resetReturn);
    $log("Git reset output: " . implode("\n", $resetOutput));
} else {
    // Step 4: Normal pull
    exec('git pull origin master 2>&1', $output, $returnVar);
    $log("Git pull output: " . implode("\n", $output));
}
```

## Setelah Fix

### Verifikasi
```bash
# Cek status
git status

# Cek latest commit
git log --oneline -1

# Cek file CSS
ls -lh styles.css
```

### Test Deployment
```bash
# Test webhook script
php webhook-deploy.php

# Atau akses via browser
# https://yourdomain.com/webhook-deploy.php
```

### Verify CSS
```bash
# Cek hash CSS
php -r "require 'app/Support/helpers.php'; echo asset_version('styles.css');"

# Atau akses via browser
# https://yourdomain.com/verify-css-deployment.php
```

## Catatan Penting

1. **Reset Hard**: Akan menghapus semua perubahan lokal yang belum di-commit
2. **Merge**: Akan menggabungkan perubahan, tapi mungkin ada conflict
3. **Rebase**: Akan menempatkan commit lokal setelah commit remote
4. **Backup**: Selalu backup sebelum reset hard di production
5. **Webhook**: Update webhook script untuk handle divergent branches

## Rekomendasi untuk Production Server

Untuk production server, **reset hard ke remote** adalah pilihan terbaik karena:
- Memastikan server selalu sync dengan remote
- Menghindari conflict
- Memastikan deployment konsisten

Tapi pastikan:
- Tidak ada perubahan penting yang hanya ada di server
- Semua perubahan sudah di-commit dan di-push ke remote
- Backup dibuat sebelum reset

