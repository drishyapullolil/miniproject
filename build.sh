#!/bin/bash
set -eux

# Install MySQLi extension
apt-get update && apt-get install -y php-mysqli

# Start the server
php -S 0.0.0.0:10000
