#!/bin/sh
set -e

# Function to wait for the database
wait_for_db() {
    echo "Waiting for database connection..."
    while ! php artisan db:monitor; do
        echo "Database not ready. Retrying in 5 seconds..."
        sleep 5
    done
    echo "Database connection established."
}

# Wait for the database to be ready
wait_for_db

# Run migrations
php artisan migrate --force

# Run seeders
php artisan db:seed --force

# Start PHP-FPM
exec php-fpm