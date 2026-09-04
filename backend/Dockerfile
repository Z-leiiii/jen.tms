# =========================================================
# Laravel + Vite frontend build
# =========================================================

FROM php:8.3-fpm-bookworm

# ---------------------------------------------------------
# Install system dependencies
# ---------------------------------------------------------
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
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
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

# ---------------------------------------------------------
# Install Composer
# ---------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------
# Laravel working directory
# ---------------------------------------------------------
WORKDIR /var/www/html

# ---------------------------------------------------------
# Copy Laravel project
# ---------------------------------------------------------
COPY . .

# ---------------------------------------------------------
# Install PHP dependencies
# ---------------------------------------------------------
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# ---------------------------------------------------------
# Install Node dependencies + build Vite
# ---------------------------------------------------------
RUN npm install

RUN npm run build

# ---------------------------------------------------------
# Laravel permissions
# ---------------------------------------------------------
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# ---------------------------------------------------------
# Nginx
# ---------------------------------------------------------
RUN rm -f /etc/nginx/sites-enabled/default

COPY docker/nginx.conf /etc/nginx/sites-available/default

RUN ln -sf \
    /etc/nginx/sites-available/default \
    /etc/nginx/sites-enabled/default

# ---------------------------------------------------------
# PHP-FPM
# ---------------------------------------------------------
COPY docker/www.conf \
    /usr/local/etc/php-fpm.d/www.conf

# ---------------------------------------------------------
# Supervisor
# ---------------------------------------------------------
COPY docker/supervisord.conf \
    /etc/supervisor/conf.d/supervisord.conf

# ---------------------------------------------------------
# Render port
# ---------------------------------------------------------
EXPOSE 10000

# ---------------------------------------------------------
# Start PHP-FPM + Nginx
# ---------------------------------------------------------
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]