############################
# image with
# OpenCart 3.0.2.0 (official release from github.com/opencart/opencart)
# PHP 7.4 + Apache
############################
FROM php:7.4-apache

ENV OPENCART_VERSION=3.0.2.0

RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    mariadb-client \
    unzip \
    wget \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" curl gd mysqli pdo_mysql zip \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# PHP settings needed by OpenCart / the Webpay plugin (uploads, execution time)
COPY .devcontainer/php-opencart.ini /usr/local/etc/php/conf.d/zz-opencart.ini

# Download the official OpenCart release into a template directory.
# The entrypoint copies it into the (persisted) webroot volume on first boot,
# the same way the official "wordpress" image seeds /var/www/html.
RUN wget -q "https://github.com/opencart/opencart/releases/download/${OPENCART_VERSION}/${OPENCART_VERSION}-OpenCart.zip" -O /tmp/opencart.zip \
 && unzip -q /tmp/opencart.zip "upload/*" -d /tmp/opencart \
 && mkdir -p /opt/opencart-src \
 && cp -r /tmp/opencart/upload/. /opt/opencart-src/ \
 && rm -rf /tmp/opencart /tmp/opencart.zip

COPY .devcontainer/opencart-entrypoint.sh /usr/local/bin/opencart-entrypoint.sh
RUN chmod +x /usr/local/bin/opencart-entrypoint.sh

ENTRYPOINT ["opencart-entrypoint.sh"]
CMD ["apache2-foreground"]
