FROM php:8.4-fpm

ENV DEBIAN_FRONTEND=noninteractive
ENV APP_DIR=/var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1

# System packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    curl \
    unzip \
    zip \
    sqlite3 \
    libsqlite3-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    ca-certificates \
    gnupg \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install \
      pdo \
      pdo_sqlite \
      bcmath \
      intl \
      mbstring \
      zip \
      exif \
      pcntl \
      gd

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node 20
RUN mkdir -p /etc/apt/keyrings && \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg && \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" > /etc/apt/sources.list.d/nodesource.list && \
    apt-get update && apt-get install -y --no-install-recommends nodejs && \
    rm -rf /var/lib/apt/lists/*

# Copy app into image (build context should be project root when building)
COPY . ${APP_DIR}

WORKDIR ${APP_DIR}

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Prepare Laravel env
RUN cp .env.example .env && \
    php artisan key:generate && \
    sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env && \
    sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env && \
    sed -i 's#^APP_URL=.*#APP_URL=http://localhost#' .env && \
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env || echo "DB_CONNECTION=sqlite" >> .env && \
    sed -i 's#^DB_DATABASE=.*#DB_DATABASE=/var/www/html/database/database.sqlite#' .env || echo "DB_DATABASE=/var/www/html/database/database.sqlite" >> .env

# Create SQLite DB and writable dirs
RUN mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs && \
    touch database/database.sqlite && \
    chown -R www-data:www-data ${APP_DIR} && \
    chmod -R ug+rwX storage bootstrap/cache database

# Install JS deps and force stable Vite 5 toolchain for Monaco compatibility
RUN rm -rf node_modules package-lock.json && \
    npm install && \
    npm install -D vite@^5 laravel-vite-plugin@^1 && \
    npm run build

# Nginx config
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]