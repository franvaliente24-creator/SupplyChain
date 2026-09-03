FROM php:8.2-apache

# Enable mysqli and other commonly-needed PHP extensions
RUN docker-php-ext-install mysqli

# Enable Apache's rewrite module (needed if you use .htaccess for clean URLs)
RUN a2enmod rewrite

# Copy your entire project into Apache's web root
COPY . /var/www/html/

# Allow .htaccess overrides (only matters if you have one — harmless if you don't)
RUN echo '<Directory /var/www/html/>\n\
    AllowOverride All\n\
</Directory>' >> /etc/apache2/apache2.conf

# Apache listens on port 80 by default — matches what you set in HostForge
EXPOSE 80