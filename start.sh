#!/bin/bash

# Add ServerName directive to Apache config
echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Start Apache normally
apache2-foreground
