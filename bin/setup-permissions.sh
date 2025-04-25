#!/bin/bash

# This script sets up the necessary directories and permissions for the application

# Create var directory if it doesn't exist
mkdir -p var/cache var/log

# Set permissions
chmod -R 777 var/cache var/log

# Create specific log files
touch var/log/exchange_rates.log
chmod 666 var/log/exchange_rates.log

echo "Permissions set up successfully!"
echo "The following directories are now writable:"
echo "- var/cache"
echo "- var/log"
echo ""
echo "The exchange rates log file has been created at var/log/exchange_rates.log"
