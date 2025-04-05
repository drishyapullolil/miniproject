# Use the official PHP image with Apache
FROM php:7.4-apache

# Install necessary PHP extensions (for PostgreSQL, for example)
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pgsql pdo_pgsql

# Enable Apache mod_rewrite (for URL rewriting)
RUN a2enmod rewrite

# Copy the custom Apache config to resolve the ServerName issue
COPY custom-apache.conf /etc/apache2/conf-available/custom-apache.conf

# Enable the custom configuration
RUN a2enconf custom-apache

# Expose port 80 for HTTP traffic
EXPOSE 80

# Copy the project files into the container
COPY . /var/www/html/

# Set the working directory to the project folder
WORKDIR /var/www/html

# Run Apache in the foreground
CMD ["apache2-foreground"]
