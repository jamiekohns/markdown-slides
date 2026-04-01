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
    git \
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

# Prepare Laravel env
RUN if [ -f .env.example ]; then cp .env.example .env; else touch .env; fi && \
    sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env && \
    sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env && \
    sed -i 's#^APP_URL=.*#APP_URL=http://localhost#' .env && \
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env || echo "DB_CONNECTION=sqlite" >> .env && \
    sed -i 's#^DB_DATABASE=.*#DB_DATABASE=/var/www/html/database/database.sqlite#' .env || echo "DB_DATABASE=/var/www/html/database/database.sqlite" >> .env

# Create SQLite DB and writable dirs
RUN mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs && \
    rm -f database/database.sqlite && \
    touch database/database.sqlite && \
    chown -R www-data:www-data ${APP_DIR} && \
    mkdir -p bootstrap/cache && chown www-data:www-data bootstrap/cache && \
    chmod -R ug+rwX storage bootstrap/cache database

# Frontend build is optional because this repo already ships compiled assets in public/build.
# Set BUILD_FRONTEND=true at build time to compile assets in the image.
ARG BUILD_FRONTEND=false
ARG NODE_BUILD_MAX_OLD_SPACE=1536
RUN if [ "${BUILD_FRONTEND}" = "true" ]; then \
        npm ci --include=optional --no-audit --no-fund && \
        NODE_OPTIONS="--max-old-space-size=${NODE_BUILD_MAX_OLD_SPACE}" npm run build; \
    else \
        echo "Skipping frontend build; using committed public/build assets."; \
    fi

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader
RUN npm install
RUN npm run build
RUN php artisan migrate --force
RUN php artisan key:generate
RUN php artisan tinker --execute="\App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password')]);"
RUN git config --global --add safe.directory /var/www/html


# Nginx config
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]