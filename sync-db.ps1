# sync-db.ps1
# Script to sync production database to local

$REMOTE_USER = "peroniks"
$REMOTE_HOST = "10.88.8.46"
$REMOTE_PATH = "/srv/docker/apps/energy-tracker"
$DB_NAME     = "energy_tracker"

# --- CONFIG PASSWORD ---
$REMOTE_DB_PASS = "123456788"
$LOCAL_DB_PASS  = "123456788" # Menyesuaikan dengan .env lokal Anda
# -----------------------

$LOCAL_SQL = "dump_prod.sql"

Write-Host "--- STARTING PRODUCTION SYNC ---" -ForegroundColor Cyan

# 0. Find Local MySQL (Laragon Support)
$mysqlPath = "mysql" # Default assuming it's in PATH
if (-not (Get-Command "mysql" -ErrorAction SilentlyContinue)) {
    Write-Host "Searching for Laragon MySQL..." -ForegroundColor Gray
    $laragonMysql = Get-ChildItem "C:\laragon\bin\mysql" -Filter "mysql.exe" -Recurse | Select-Object -First 1
    if ($laragonMysql) {
        $mysqlPath = $laragonMysql.FullName
        Write-Host "Found Laragon MySQL: $($laragonMysql.Directory)" -ForegroundColor Gray
    } else {
        Write-Error "MySQL not found. Please make sure Laragon is installed or mysql is in your PATH."
        exit
    }
}

# 1. Run Dump on Server using Docker Compose
Write-Host "[1/3] Creating dump on server (via Docker Compose)..." -ForegroundColor Yellow
ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && docker compose exec -T db mysqldump -u root -p$REMOTE_DB_PASS $DB_NAME > $LOCAL_SQL"

# 2. Download via SCP
Write-Host "[2/3] Downloading dump file..." -ForegroundColor Yellow
$scpSource = "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/${LOCAL_SQL}"
scp $scpSource .

# 3. Import Locally
Write-Host "[3/3] Importing to local database..." -ForegroundColor Yellow
if (Test-Path $LOCAL_SQL) {
    if ($LOCAL_DB_PASS -eq "") {
        Get-Content $LOCAL_SQL | & $mysqlPath -u root -h 127.0.0.1 $DB_NAME
    } else {
        Get-Content $LOCAL_SQL | & $mysqlPath -u root -h 127.0.0.1 "--password=$LOCAL_DB_PASS" $DB_NAME
    }

    if ($LASTEXITCODE -eq 0) {
        Write-Host "SUCCESS: Database synchronized!" -ForegroundColor Green
    } else {
        Write-Host "ERROR: Import failed. Check your local MySQL password." -ForegroundColor Red
        Write-Host "Try checking your password in Laragon -> Database (HeidiSQL)" -ForegroundColor Gray
    }
} else {
    Write-Error "Dump file not found. Download might have failed."
}

# Cleanup
Write-Host "Cleaning up temporary files..." -ForegroundColor Gray
ssh $REMOTE_USER@$REMOTE_HOST "rm $REMOTE_PATH/$LOCAL_SQL"
Remove-Item $LOCAL_SQL -ErrorAction SilentlyContinue

Write-Host "--- SYNC COMPLETE ---" -ForegroundColor Cyan
