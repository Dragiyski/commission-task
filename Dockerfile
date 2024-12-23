# Use an official PHP runtime as a parent image
# FROM php:cli
FROM composer:latest

RUN apk add --no-cache \
    curl

# Install any needed packages specified in composer.json
# RUN curl -sS https://getcomposer.org/installer | php && \
#     mv composer.phar /usr/local/bin/composer

# This does not work, yet, but it can be debugged and made to work.
# RUN extensions=$(composer show -p | grep ext- | cut -d ' ' -f1 | sed 's/ext-//g') && \
#     docker-php-ext-install $extensions

RUN docker-php-ext-install bcmath

# Set the working directory
WORKDIR /usr/src/app

# Copy the current directory contents into the container at /usr/src/app
COPY . /usr/src/app

RUN composer install

# Make main.php executable
RUN chmod +x src/main.php

# Run src/main.php when the container launches
ENTRYPOINT ["php", "src/main.php"]
