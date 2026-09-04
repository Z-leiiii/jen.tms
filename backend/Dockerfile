# =========================================================
# Laravel 12 + PHP 8.3 + Nginx + PHP-FPM + Vite
# Render deployment
# =========================================================

FROM php:8.3-fpm-bookworm

# =========================================================
# SYSTEM DEPENDENCIES
# =========================================================

RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git \
    curl \
    unzip \
    nodejs \
    npm \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql \
        mbstring \
        bcmath \
        exif \
        pcntl \
        gd \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# =========================================================
# COMPOSER
# =========================================================

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# =========================================================
# APPLICATION DIRECTORY
# =========================================================

WORKDIR /var/www/html

# =========================================================
# COPY LARAVEL APPLICATION
# =========================================================

COPY . .

# =========================================================
# INSTALL PHP DEPENDENCIES
# =========================================================

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# =========================================================
# INSTALL NODE DEPENDENCIES
# =========================================================

RUN npm install

# =========================================================
# BUILD LARAVEL VITE ASSETS
# =========================================================
# This creates:
# public/build/manifest.json
# and the compiled CSS/JS assets Laravel needs.
# =========================================================

RUN npm run build

# =========================================================
# LARAVEL DIRECTORIES / PERMISSIONS
# =========================================================

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

RUN chmod -R 775 \
    storage \
    bootstrap/cache

# =========================================================
# NGINX CONFIGURATION
# =========================================================

RUN rm -f /etc/nginx/sites-enabled/default

COPY docker/nginx.conf \
    /etc/nginx/sites-available/default

RUN ln -sf \
    /etc/nginx/sites-available/default \
    /etc/nginx/sites-enabled/default

# =========================================================
# PHP-FPM CONFIGURATION
# =========================================================

COPY docker/www.conf \
    /usr/local/etc/php-fpm.d/www.conf

# =========================================================
# SUPERVISOR CONFIGURATION
# =========================================================

COPY docker/supervisord.conf \
    /etc/supervisor/conf.d/supervisord.conf

# =========================================================
# RENDER PORT
# =========================================================

EXPOSE 10000

# =========================================================
# START PHP-FPM + NGINX
# =========================================================

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
