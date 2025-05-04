FROM php:8.2-apache

# Install mysqli and enable it
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# (Optional) Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy your PHP files into the web root
COPY . /var/www/html/

# Set permissions (optional)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
