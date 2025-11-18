# ================================================================
# Image PHP 8.2 + Apache avec extensions pré-installées + HTTPS
# ================================================================
FROM php:8.2-apache

# Extensions PHP + modules Apache
RUN docker-php-ext-install pdo pdo_mysql mysqli \
 && a2enmod rewrite ssl headers

# Autoriser .htaccess partout
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Certificat self-signed (valable 10 ans)
RUN openssl req -x509 -nodes -days 3650 \
    -newkey rsa:2048 \
    -keyout /etc/ssl/private/borne.key \
    -out    /etc/ssl/certs/borne.crt \
    -subj "/C=FR/ST=HDF/L=Lille/O=BorneInteract/CN=localhost" 2>/dev/null

# VirtualHost HTTPS
RUN echo '<VirtualHost *:443>\n\
    DocumentRoot /var/www/html\n\
    SSLEngine on\n\
    SSLCertificateFile    /etc/ssl/certs/borne.crt\n\
    SSLCertificateKeyFile /etc/ssl/private/borne.key\n\
    Header always set Strict-Transport-Security "max-age=31536000"\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/borne-ssl.conf \
 && a2ensite borne-ssl

# Port 80 : servir directement (Tailscale gère HTTPS en amont)
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    RewriteEngine On\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
