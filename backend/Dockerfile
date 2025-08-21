# Use PHP 8.2 with FPM as the base image
FROM php:8.2-fpm

# Set environment variable to install packages without user interaction
ENV DEBIAN_FRONTEND=noninteractive

# Install essential system dependencies and PHP extension dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    libwebp-dev \
    pkg-config \
    iputils-ping

# Configure and install the gd extension
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Install other required PHP extensions
RUN docker-php-ext-install -j$(nproc) pdo pdo_mysql mbstring exif pcntl bcmath zip

# Install redis extension via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Get the latest Composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www

# Copy the application source code into the container
# The 'vendor' directory will be created by the composer install command
COPY . .

# Install Composer dependencies
RUN composer install --no-interaction --no-plugins --no-scripts --optimize-autoloader


# Copy the new entrypoint script into the container
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

# Make the entrypoint script executable
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set the entrypoint script to be executed when the container starts
ENTRYPOINT ["entrypoint.sh"]

# Set the default command to run (this will be passed to the entrypoint)
# This now uses the recommended exec form for graceful shutdowns
CMD ["php-fpm"]

# Expose port 9000 for PHP-FPM
EXPOSE 9000
