# Multi-stage build to compile frontend assets
FROM node:22-alpine AS frontend

WORKDIR /app

# Copy all files
COPY . .

# Install dependencies and build assets
RUN npm install
RUN npm run build --workspace=resources/js/v2

# Final stage
FROM php:8.4-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    libicu-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath intl pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Copy built frontend assets from the frontend stage
COPY --from=frontend /app/public/build /var/www/html/public/build

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database /var/www/html/public/build

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable rewrite module
RUN a2enmod rewrite

# Create SQLite database file if it doesn't exist
RUN touch /var/www/html/database/database.sqlite
RUN chown www-data:www-data /var/www/html/database/database.sqlite

# Entrypoint script to run migrations
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
