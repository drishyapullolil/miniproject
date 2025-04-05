FROM php:8.1-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql zip

# Create app directory
WORKDIR /app

# Copy application files
COPY . /app

# Install Composer if needed
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install dependencies if you have a composer.json
# RUN composer install --no-interaction --no-plugins --no-scripts

# Make port 10000 available
EXPOSE 10000

# Start PHP server
CMD php -S 0.0.0.0:10000 -t .
