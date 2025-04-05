# Use the official PHP image with Apache
FROM php:7.4-apache

# Install necessary PHP extensions (for PostgreSQL, for example)
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pgsql pdo_pgsql

# Enable Apache mod_rewrite (for URL rewriting)
RUN a2enmod rewrite

# Copy custom Apache configuration file to fix the ServerName warning
COPY custom-apache.conf /etc/apache2/sites-available/000-default.conf

# Ensure the custom Apache configuration is enabled
RUN a2ensite 000-default.conf

# Expose port 80 for HTTP traffic
EXPOSE 80

# Copy the project files into the container
COPY . /var/www/html/

# Set the working directory to the project folder
WORKDIR /var/www/html

# Set environment variables or other necessary configurations if required
# Example: ENV VAR_NAME=value

# Run Apache in the foreground
CMD ["apache2-foreground"]
