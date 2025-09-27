#!/bin/bash

# --- Deploy Script for MicroNote Staging ---

echo "Starting deployment..."

# 1. Get latest code
git pull origin main # sau branch-ul tau

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 3. Run migrations
php artisan migrate --force

# 4. Clear and rebuild cache
php artisan view:clear
php artisan route:clear
php artisan config:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Setting permissions..."

# 5. Set correct ownership and permissions
# ATENTIE: Ruleaza script-ul cu un user care are drepturi sudo sau ajusteaza
sudo chown -R micronoteapp_staging:www-data .
sudo find . -type d -exec chmod 775 {} \;
sudo find . -type f -exec chmod 664 {} \;

echo "Deployment finished successfully!"
