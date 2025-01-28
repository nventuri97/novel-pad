# Use the official PHP image with Apache
FROM php:8.3.14-apache-bookworm

# Install necessary extensions, including pdo_mysql for MySQL support
RUN docker-php-ext-install mysqli pdo_mysql

# Install additional dependencies for PHPMailer
RUN apt-get update && apt-get install -y \
    libssl-dev \
    curl \
    unzip \
    && docker-php-ext-install sockets

# Copy the source code into the container
COPY src/ /var/www/html/

# Install PHP dependencies with Composer if a composer.json file is present
WORKDIR /var/www/html

# Assign appropriate permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create the uploads directory with correct permissions
RUN mkdir -p /var/www/html/uploads \
    && chmod 775 /var/www/html/uploads \
    && chown www-data:www-data /var/www/html/uploads

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHPMailer via Composer
RUN composer require phpmailer/phpmailer
