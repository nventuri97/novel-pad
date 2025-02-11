# Use the official PHP image with Apache
FROM php:8.3.14-apache-bookworm

# Install necessary extensions
RUN docker-php-ext-install mysqli pdo_mysql

# Install required dependencies
RUN apt-get update && apt-get install -y \
    libssl-dev \
    curl \
    unzip \
    apache2-utils \
    && docker-php-ext-install sockets

# Enable SSL and necessary Apache modules
RUN a2enmod ssl rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy Composer files first (for caching)
COPY src/composer.json src/composer.lock /var/www/html/

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer 
    # && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Create uploads directory
RUN mkdir -p /var/www/private/uploads \
    && chown -R www-data:www-data /var/www/private \
    && chmod -R 755 /var/www/private

# Copy application source code
COPY src/ /var/www/html/

# Assign appropriate permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copy Apache SSL configuration
COPY init/apache-ssl.conf /etc/apache2/sites-available/000-default.conf

# Create SSL certificate (for development)
RUN mkdir -p /etc/apache2/ssl
COPY certs/server.crt /etc/apache2/ssl/apache.crt
COPY certs/server.key /etc/apache2/ssl/apache.key

# Enable the SSL site configuration
RUN a2ensite 000-default.conf

# Expose HTTP and HTTPS ports
EXPOSE 80 443

CMD ["bash", "-c", "composer install && apache2-foreground"]
