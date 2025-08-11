FROM php:8.3-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    curl \
    zip \
    unzip \
    git \
    oniguruma-dev \
    libzip-dev \
    && docker-php-ext-install \
    zip \
    mbstring

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application code
COPY . .

# Create cache directory
RUN mkdir -p var/cache && chmod -R 775 var/

# Install Transformers models
RUN php bin/console transformers:download

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public/"]