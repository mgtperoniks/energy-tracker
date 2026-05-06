---
description: Safe deployment workflow - push code to production server without breaking database
---

# 🔒 Aturan Emas: Safe Production Deployment

// turbo-all

## Prinsip Utama
1. **JANGAN PERNAH** jalankan `migrate:fresh` atau `migrate:rollback` di production
2. **SELALU** backup database sebelum deploy jika ada migration baru
3. **HANYA** jalankan `migrate` (tanpa flag) untuk migration baru di production
4. Perubahan PHP/Blade biasa **TIDAK PERLU** migration

---

## Langkah Deployment

### STEP 1: Commit & Push dari Local (Laragon)

```powershell
cd c:\laragon\www\Energy-Tracker

# Cek perubahan
git status

# Add semua perubahan
git add -A

# Commit dengan pesan deskriptif
git commit -m "feat: deskripsi singkat perubahan"

# Push ke akun pribadi (afinifathur)
git push origin main

# Push ke akun production (mgtperoniks) untuk mentrigger deploy
git push prod main
```

### STEP 2: Backup Database di Server (WAJIB jika ada migration baru)

SSH ke server, lalu jalankan:

```bash
cd /srv/docker/apps/energy-tracker

# Backup database sebelum pull
sudo docker compose exec db mysqldump -u root -p'123456788' energy_tracker > /home/peroniks/backups/energy_tracker_$(date +%Y%m%d_%H%M%S).sql
```

### STEP 3: Pull & Update di Server

```bash
cd /srv/docker/apps/energy-tracker

# Pull kode terbaru
sudo git pull origin main

# Build & Restart (tambahkan --no-cache jika perlu)
sudo docker compose build --no-cache
sudo docker compose up -d

# Hubungkan kembali symlink storage
sudo docker compose exec app php artisan storage:link

# Clear semua cache dan re-cache
sudo docker compose exec app php artisan optimize:clear
sudo docker compose exec app php artisan optimize

# HANYA jika ada migration baru (BUKAN migrate:fresh!)
sudo docker compose exec app php artisan migrate --force
```

### STEP 4: Verifikasi

1. Buka [http://10.88.8.97/energy-tracker/public/index.php](http://10.88.8.97/energy-tracker/public/index.php)
2. Cek apakah fitur baru berfungsi
3. Pastikan data tidak hilang

---

## ⚠️ PERINTAH BERBAHAYA - JANGAN GUNAKAN DI PRODUCTION

```bash
# ❌ JANGAN! Ini menghapus SEMUA data!
php artisan migrate:fresh
php artisan migrate:fresh --seed
php artisan migrate:rollback
php artisan db:wipe
```

---

## Checklist Sebelum Deploy

- [x] Sudah test di local (Laragon)?
- [x] Ada migration baru? Jika ya, backup database dulu!
- [x] Push ke `origin` (afinifathur) dan `prod` (mgtperoniks)?
- [x] Password DB di server sudah benar?

---

## Recovery Jika Terjadi Masalah

```bash
# Restore database dari backup
sudo docker compose exec -T db mysql -u root -p'123456788' energy_tracker < /home/peroniks/backups/energy_tracker_YYYYMMDD_HHMMSS.sql

# Rollback ke commit sebelumnya
sudo git reset --hard HEAD~1
```