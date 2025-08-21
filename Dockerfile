# Use official PHP CLI image
FROM php:8.2-cli

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Copy your project files into container
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Expose Render port
EXPOSE 10000

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
