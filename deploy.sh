#!/bin/bash
set -e # Exit immediately if a command exits with a non-zero status.

echo "--- Starting MicroNote Deployment ---"

# --- Pre-flight Check ---
if [ "$(id -u)" = "0" ]; then
   echo "❌ This script should NOT be run as root. Run it as your application user (e.g., micronoteapp_staging)."
   exit 1
fi

# --- Code Update ---
echo "➡️ Pulling latest code from main branch..."
git pull origin main

# --- Backend Dependencies ---
echo "➡️ Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# --- Frontend Dependencies (Clean Install) ---
echo "➡️ Performing clean install of NPM packages..."
rm -rf node_modules package-lock.json
npm install

echo "➡️ Building frontend assets..."
npm run build

# --- Application Setup ---
echo "➡️ Running database migrations..."
php artisan migrate --force

echo "➡️ Clearing application caches..."
php artisan view:clear
php artisan route:clear
php artisan config:clear
php artisan cache:clear

# --- Set Permissions ---
# This is the ONLY part that needs elevated privileges.
# It ensures the web server (www-data) can read the files and write to storage/logs.
echo "➡️ Setting final permissions..."
sudo chown -R $(whoami):www-data .
sudo find . -type f -exec chmod 664 {} \;
sudo find . -type d -exec chmod 775 {} \;
sudo chmod -R ug+rwx storage bootstrap/cache

echo "✅ Deployment finished successfully!"
