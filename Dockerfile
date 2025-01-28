# Usa l'immagine ufficiale di PHP con Apache
FROM php:8.3.14-apache-bookworm

# Installa estensioni necessarie, inclusa pdo_mysql per il supporto a MySQL
RUN docker-php-ext-install mysqli pdo_mysql

# Installa dipendenze aggiuntive per PHPMailer
RUN apt-get update && apt-get install -y \
    libssl-dev \
    curl \
    unzip \
    && docker-php-ext-install sockets

# Installa Composer per gestire le dipendenze PHP (incluso PHPMailer)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copia il codice sorgente nel container
COPY src/ /var/www/html/

# Installa le dipendenze PHP con Composer, se è presente un file composer.json
WORKDIR /var/www/html

RUN if [ -f composer.json ]; then composer install; fi

RUN composer require phpmailer/phpmailer \
    && composer install

# Assegna i permessi appropriati
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

RUN mkdir -p /var/www/html/uploads \
    && chmod 775 /var/www/html/uploads \
    && chown www-data:www-data /var/www/html/uploads
