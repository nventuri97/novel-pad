# Usa l'immagine ufficiale di PHP con Apache
FROM php:8.3.14-apache-bookworm

# Installa estensioni necessarie, inclusa `pdo_mysql` per il supporto a MySQL
RUN docker-php-ext-install mysqli pdo_mysql

# Copia il codice sorgente nel container
COPY src/ /var/www/html/

# Assegna i permessi appropriati
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

RUN chmod 775 /var/www/html/uploads/
RUN chown www-data:www-data /var/www/html/uploads/