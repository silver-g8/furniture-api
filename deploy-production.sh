#!/bin/bash
# ========================================
# Production Deployment Script
# ========================================
#
# ใช้สำหรับ deploy Backend ไป production
#
# Usage:
#   bash deploy-production.sh
#
# ========================================

set -e  # Exit on error

echo "🚀 Starting Production Deployment..."
echo ""

# 1. Backup current .env
if [ -f .env ]; then
    echo "📦 Backing up current .env to .env.backup..."
    cp .env .env.backup
    echo "✅ Backup created"
else
    echo "⚠️  No existing .env found"
fi
echo ""

# 2. Copy production config
if [ ! -f .env.production ]; then
    echo "❌ Error: .env.production not found!"
    echo "Please create .env.production first."
    exit 1
fi

echo "📋 Copying .env.production to .env..."
cp .env.production .env
echo "✅ Production config applied"
echo ""

# 3. Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Caches cleared"
echo ""

# 4. Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Optimization complete"
echo ""

# 5. Run migrations (optional - uncomment if needed)
# echo "🗄️  Running migrations..."
# php artisan migrate --force
# echo "✅ Migrations complete"
# echo ""

echo "🎉 Production deployment complete!"
echo ""
echo "📝 Next steps:"
echo "   1. Upload files to server"
echo "   2. Set proper permissions (storage/, bootstrap/cache/)"
echo "   3. Verify .env settings on server"
echo "   4. Test API endpoints"
echo ""
