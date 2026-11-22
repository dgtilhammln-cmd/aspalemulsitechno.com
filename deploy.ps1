# ============================================================
# DEPLOY SCRIPT — aspalemulsitechno.com
# Cara pakai: ./deploy.ps1 atau ./deploy
# ============================================================

$SSH_HOST = "46.202.186.86"
$SSH_PORT = "65002"
$SSH_USER = "u664715641"

Write-Host ""
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host "  DEPLOY - aspalemulsitechno.com" -ForegroundColor Cyan
Write-Host "==============================================" -ForegroundColor Cyan
Write-Host ""

# Pastikan eksekusi git berada di folder repository (public_html)
$originalLocation = Get-Location
if (Test-Path "$PSScriptRoot\public_html\.git") {
    Set-Location "$PSScriptRoot\public_html"
} elseif (Test-Path ".git") {
    # Sudah berada di dalam repo
} elseif (Test-Path "public_html\.git") {
    Set-Location "public_html"
}

# ── STEP 1: Commit lokal ──────────────────────────────────
Write-Host "[1/3] Commit perubahan lokal..." -ForegroundColor Yellow
git add .
$status = git status --porcelain
if ($status) {
    git commit -m "Deploy: update logo and cache busting"
} else {
    Write-Host "Tidak ada perubahan baru untuk di-commit." -ForegroundColor Gray
}

# ── STEP 2: Push ke GitHub ───────────────────────────────
Write-Host ""
Write-Host "[2/3] Push ke GitHub..." -ForegroundColor Yellow
git push origin main
if ($LASTEXITCODE -ne 0) {
    git push origin master
    if ($LASTEXITCODE -ne 0) {
        Write-Host "[ERROR] Gagal push ke GitHub!" -ForegroundColor Red
        Set-Location $originalLocation
        pause
        exit 1
    }
}
Write-Host "Push berhasil!" -ForegroundColor Green

# Kembalikan lokasi folder
Set-Location $originalLocation

# ── STEP 3: Deploy ke Hosting ────────────────────────────
Write-Host ""
Write-Host "[3/3] Deploy ke Hosting..." -ForegroundColor Yellow

$bashScript = @'
#!/bin/bash

REPO_URL="https://github.com/dgtilhammln-cmd/aspalemulsitechno.com.git"

deploy_target() {
    TARGET="$1"
    echo ""
    echo "=================================================="
    echo "=== Deploying to target: $TARGET ==="
    echo "=================================================="
    
    if [ ! -d "$TARGET" ]; then
        echo "Directori $TARGET tidak ditemukan, skip."
        return 0
    fi

    cd "$TARGET"

    # Pastikan git diinisialisasi
    if [ ! -d "$TARGET/.git" ]; then
        echo "--- Inisialisasi git di $TARGET..."
        git init
    fi

    # Set remote URL
    git remote remove origin 2>/dev/null || true
    git remote add origin "$REPO_URL"

    echo "--- Menarik commit terbaru dari GitHub..."
    export GIT_TERMINAL_PROMPT=0
    git fetch origin main

    echo "--- Memaksa timpa working tree (force checkout & reset)..."
    rm -f .git/index.lock 2>/dev/null || true
    
    # PENTING: Gunakan -f (force) agar tidak diblokir oleh file lama/untracked
    git checkout -f -B main origin/main
    git reset --hard origin/main

    # Touch file CSS agar cache browser ter-refresh
    [ -f "$TARGET/styles.css" ] && touch "$TARGET/styles.css"
    [ -f "$TARGET/animations.css" ] && touch "$TARGET/animations.css"
    [ -f "$TARGET/admin/admin.css" ] && touch "$TARGET/admin/admin.css"

    # Reset OPcache PHP agar script PHP langsung menggunakan file terbaru
    php -r "if(function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache cleared\n'; }" 2>/dev/null || true

    echo ""
    echo "--- Verifikasi File Terkini di Server: ---"
    ls -la "$TARGET/admin/partials/sidebar.php" "$TARGET/admin/admin.css"
    echo ""
    echo "--- Commit Aktif: ---"
    git log -1 --oneline
    echo "=== Berhasil deploy ke: $TARGET ==="
}

# 1. Target UTAMA (Primary Domain Hostinger): /home/u664715641/public_html
deploy_target "/home/u664715641/public_html"

# 2. Target ALTERNATIF (jika addon domain): /home/u664715641/domains/aspalemulsitechno.com/public_html
if [ -d "/home/u664715641/domains/aspalemulsitechno.com/public_html" ]; then
    deploy_target "/home/u664715641/domains/aspalemulsitechno.com/public_html"
fi

echo ""
echo "--- SEMUA PROSES DEPLOY SELESAI DENGAN SUKSES! ---"
'@

# Tulis script ke file temp lalu pipe ke SSH bash -s
$tmpScript = "$env:TEMP\deploy_aspalemulsitechno.sh"
[System.IO.File]::WriteAllText($tmpScript, $bashScript, [System.Text.Encoding]::UTF8)

Get-Content $tmpScript -Raw | & ssh -p $SSH_PORT -o ConnectTimeout=30 -o ServerAliveInterval=20 -o ServerAliveCountMax=6 -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "bash -s"

Remove-Item $tmpScript -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "==============================================" -ForegroundColor Green
Write-Host "  Deploy Berhasil!" -ForegroundColor Green
Write-Host "  https://aspalemulsitechno.com" -ForegroundColor Green
Write-Host "==============================================" -ForegroundColor Green
