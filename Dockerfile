# Use official PHP image with Apache
FROM php:8.2-apache

# Enable Apache rewrite module (optional but common)
RUN a2enmod rewrite

# Copy the current directory (your PHP code) into Apache's root
COPY . /var/www/html/

# Set permissions (optional)
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (Apache listens here)
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
