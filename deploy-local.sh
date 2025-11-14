#!/bin/bash
# ========================================
# Local Development Setup Script
# ========================================
#
# ใช้สำหรับกลับมา local development
#
# Usage:
#   bash deploy-local.sh
#
# ========================================

set -e  # Exit on error

echo "🏠 Switching to Local Development..."
echo ""

# 1. Restore from backup if exists
if [ -f .env.backup ]; then
    echo "📦 Restoring .env from backup..."
    cp .env.backup .env
    echo "✅ Backup restored"
    echo ""
else
    echo "⚠️  No backup found. Using default local config..."

    # Create local .env from example
    if [ ! -f .env ]; then
        if [ -f .env.example ]; then
            cp .env.example .env
            echo "✅ Created .env from .env.example"
        else
            echo "❌ Error: No .env.example found!"
            exit 1
        fi
    fi
fi

# 2. Update to local config
echo "🔧 Applying local development settings..."

# Update APP_URL
sed -i 's|APP_URL=.*|APP_URL=http://furniture-api.test|' .env

# Update Database
sed -i 's|DB_DATABASE=.*|DB_DATABASE=furnitureapi|' .env
sed -i 's|DB_USERNAME=.*|DB_USERNAME=root|' .env
sed -i 's|DB_PASSWORD=.*|DB_PASSWORD=|' .env

# Update Session
sed -i 's|SESSION_DRIVER=.*|SESSION_DRIVER=file|' .env
sed -i 's|SESSION_DOMAIN=.*|SESSION_DOMAIN=localhost|' .env

# Update CORS
sed -i 's|CORS_ALLOWED_ORIGINS=.*|CORS_ALLOWED_ORIGINS=http://localhost:9000,http://127.0.0.1:9000|' .env

# Update Sanctum
sed -i 's|SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=localhost:9000,127.0.0.1:9000|' .env

# Update Cache
sed -i 's|CACHE_DRIVER=.*|CACHE_DRIVER=file|' .env
sed -i 's|QUEUE_CONNECTION=.*|QUEUE_CONNECTION=sync|' .env

echo "✅ Local settings applied"
echo ""

# 3. Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Caches cleared"
echo ""

echo "🎉 Local development setup complete!"
echo ""
echo "📝 Next steps:"
echo "   1. Start Laravel Herd or: php artisan serve"
echo "   2. Frontend: cd ../fui-furniture && npm run dev"
echo "   3. Access at: http://furniture-api.test"
echo ""
