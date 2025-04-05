# Use an official PHP image as a base image
FROM php:7.4-apache

# Set the working directory
WORKDIR /var/www/html

# Copy project files into the container
COPY . .

# Set file permissions for the log file and the directory
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pgsql pdo_pgsql

# Set up Apache to run in the background
RUN a2enmod rewrite

# Expose the necessary port
EXPOSE 80
# Add ServerName directive
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Start command remains the same
