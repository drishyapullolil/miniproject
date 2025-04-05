FROM php:7.4-apache

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pgsql pdo_pgsql

# Enable mod_rewrite (optional, if needed for your app)
RUN a2enmod rewrite

# Copy your project files into the container
COPY . /var/www/html/

# Expose the necessary port
EXPOSE 80
