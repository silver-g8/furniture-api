# Backend Deployment Guide

คู่มือการ Deploy Backend API ไป Production และการกลับมา Local Development

---

## 📁 ไฟล์ Environment

```
furniture-api/
├── .env                      # ใช้งานจริง (ไม่ commit)
├── .env.production          # Production config (commit ได้) ⭐ NEW
├── .env.example             # Template (commit ได้)
├── .env.backup              # Backup อัตโนมัติ (ไม่ commit)
├── deploy-production.sh     # Script deploy production ⭐ NEW
└── deploy-local.sh          # Script กลับ local ⭐ NEW
```

---

## 🚀 Deployment to Production

### วิธีที่ 1: ใช้ Script (แนะนำ)

```bash
cd C:/Users/silve/Herd/furniture-api

# Run deployment script
bash deploy-production.sh
```

Script จะทำอะไรบ้าง:
1. ✅ Backup `.env` ปัจจุบัน → `.env.backup`
2. ✅ Copy `.env.production` → `.env`
3. ✅ Clear all caches
4. ✅ Optimize for production (config, route, view cache)
5. ✅ (Optional) Run migrations

### วิธีที่ 2: Manual

```bash
cd C:/Users/silve/Herd/furniture-api

# 1. Backup
cp .env .env.backup

# 2. Copy production config
cp .env.production .env

# 3. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 4. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Run migrations (if needed)
php artisan migrate --force
```

---

## 🏠 กลับมา Local Development

### วิธีที่ 1: ใช้ Script (แนะนำ)

```bash
cd C:/Users/silve/Herd/furniture-api

# Run local setup script
bash deploy-local.sh
```

Script จะทำอะไรบ้าง:
1. ✅ Restore `.env` จาก `.env.backup`
2. ✅ Update settings เป็น local config
3. ✅ Clear all caches

### วิธีที่ 2: Manual

```bash
cd C:/Users/silve/Herd/furniture-api

# 1. Restore from backup
cp .env.backup .env

# 2. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## 📊 เปรียบเทียบ Config

### Local Development (.env)

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://furniture-api.test

DB_CONNECTION=mariadb
DB_DATABASE=furnitureapi
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
SESSION_DOMAIN=localhost
SESSION_SAME_SITE=lax
SESSION_SECURE_COOKIE=false

CACHE_DRIVER=file
QUEUE_CONNECTION=sync

CORS_ALLOWED_ORIGINS=http://localhost:9000,http://127.0.0.1:9000
SANCTUM_STATEFUL_DOMAINS=localhost:9000,127.0.0.1:9000
```

### Production (.env.production)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://imageapi.sg8net.com

DB_CONNECTION=mysql
DB_DATABASE=u660038086_imageapi
DB_USERNAME=u660038086_hongsilver
DB_PASSWORD=Pk0956744491

SESSION_DRIVER=database
SESSION_DOMAIN=.sg8net.com
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

CORS_ALLOWED_ORIGINS=https://image.sg8net.com
SANCTUM_STATEFUL_DOMAINS=image.sg8net.com
```

---

## 🔄 Workflow ที่แนะนำ

### สำหรับ Development

```bash
# 1. เริ่มต้น - ใช้ local config
bash deploy-local.sh

# 2. พัฒนาโปรแกรม
# ... code code code ...

# 3. Test locally
php artisan serve
# หรือใช้ Laravel Herd

# 4. Commit code
git add .
git commit -m "Add new feature"
git push
```

### สำหรับ Deployment

```bash
# 1. เตรียม production build
bash deploy-production.sh

# 2. Test production config locally (optional)
php artisan serve

# 3. Upload to server
# - ใช้ FTP/SFTP
# - หรือ git pull บน server

# 4. บน server:
ssh user@server
cd /path/to/backend
cp .env.production .env
php artisan config:cache
php artisan route:cache
php artisan migrate --force
```

---

## ⚠️ สิ่งสำคัญ

### DO ✅

- ✅ ใช้ script เพื่อสลับ environment
- ✅ Backup `.env` ก่อน deploy เสมอ
- ✅ Commit `.env.production` เข้า git
- ✅ Clear cache ทุกครั้งที่เปลี่ยน config
- ✅ Test ก่อน deploy production

### DON'T ❌

- ❌ Commit `.env` เข้า git
- ❌ Commit `.env.backup` เข้า git
- ❌ ใช้ production config ใน local
- ❌ ใช้ local config ใน production
- ❌ Skip cache clearing

---

## 🛠️ Troubleshooting

### ปัญหา: Deploy แล้วยัง error

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### ปัญหา: Database connection error

ตรวจสอบ:
1. `.env` มี config database ถูกต้อง
2. Database server รันอยู่
3. Username/Password ถูกต้อง
4. Database มีอยู่จริง

### ปัญหา: Session error

```bash
# Clear session
php artisan session:clear

# ตรวจสอบ SESSION_DRIVER
# Local: file
# Production: database
```

---

## 📚 เอกสารที่เกี่ยวข้อง

- [CLAUDE.md](CLAUDE.md) - Backend architecture
- [README.md](README.md) - Project overview
- [../../project/roiet/ENVIRONMENT_GUIDE.md](../../project/roiet/ENVIRONMENT_GUIDE.md) - Full environment guide
- [../../project/roiet/ERROR_500_FIX_SUMMARY.md](../../project/roiet/ERROR_500_FIX_SUMMARY.md) - Error fixes

---

## 🎯 Quick Commands

```bash
# Deploy to production
bash deploy-production.sh

# Back to local
bash deploy-local.sh

# Check current config
php artisan config:show

# View current environment
php artisan env

# Clear everything
php artisan optimize:clear
```

---

## 📝 Checklist สำหรับ Production Deployment

### Pre-Deployment

- [ ] Test locally ทุก feature
- [ ] Run tests: `php artisan test`
- [ ] Update `.env.production` ถ้ามีการเปลี่ยนแปลง
- [ ] Backup database production
- [ ] Commit และ push code

### Deployment

- [ ] Run `bash deploy-production.sh`
- [ ] Upload files ไป server
- [ ] Set permissions: `chmod -R 755 storage bootstrap/cache`
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Test API endpoints

### Post-Deployment

- [ ] Test authentication flow
- [ ] Test CRUD operations
- [ ] Check logs: `tail -f storage/logs/laravel.log`
- [ ] Monitor server resources

---

**สรุป:** ใช้ script เพื่อสลับระหว่าง local และ production ได้ง่ายและปลอดภัย! 🚀
