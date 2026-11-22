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
    git commit -m "Deploy: update terbaru aspalemulsitechno.com"
} else {
    Write-Host "Tidak ada perubahan baru untuk di-commit." -ForegroundColor Gray
}

# ── STEP 2: Push ke GitHub ───────────────────────────────
Write-Host ""
Write-Host "[2/3] Push ke GitHub..." -ForegroundColor Yellow
git push origin main
if ($LASTEXITCODE -ne 0) {
    # Fallback ke master jika branch aktif bernama master
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

# PENTING: Pakai @'...'@ (single-quote) agar PowerShell TIDAK expand variabel
# Semua $ di sini adalah milik bash, bukan PowerShell
$bashScript = @'
#!/bin/bash
DEPLOY_DIR="/home/u664715641/domains/aspalemulsitechno.com/public_html"
REPO_URL="https://github.com/dgtilhammln-cmd/aspalemulsitechno.com.git"

echo "=== Deploying to target: $DEPLOY_DIR ==="
if [ -d "$DEPLOY_DIR/.git" ]; then
    echo "--- [UPDATE] Repo ada, menarik update dari GitHub..."
    cd "$DEPLOY_DIR"
    git remote set-url origin "$REPO_URL" 2>/dev/null || true
    git fetch origin main
    git reset --hard origin/main
elif [ -d "$DEPLOY_DIR" ]; then
    echo "--- Setup repo di $DEPLOY_DIR..."
    cd "$DEPLOY_DIR"
    git init
    git branch -M main
    git remote add origin "$REPO_URL" 2>/dev/null || git remote set-url origin "$REPO_URL"
    git fetch origin main
    git reset --hard origin/main
else
    echo "--- Membuat direktori $DEPLOY_DIR..."
    mkdir -p "$DEPLOY_DIR"
    cd "$DEPLOY_DIR"
    git clone -b main "$REPO_URL" .
fi

if [ -d "$DEPLOY_DIR" ]; then
    cd "$DEPLOY_DIR"
    
    # Update timestamp CSS file agar cache busting browser bekerja
    [ -f "$DEPLOY_DIR/styles.css" ] && touch "$DEPLOY_DIR/styles.css"
    [ -f "$DEPLOY_DIR/animations.css" ] && touch "$DEPLOY_DIR/animations.css"
    
    # Clear PHP OPcache jika ada
    php -r "if(function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache cleared\n'; }" 2>/dev/null || true
    
    echo ""
    echo "--- Status Git Server: ---"
    git status -s
    git log -1 --oneline
fi

echo ""
echo "--- DEPLOY SELESAI! ---"
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
