############################
# image with
# OpenCart 3.0.2.0 (official release from github.com/opencart/opencart)
# PHP 7.4 + Apache
# Composer 2.x
# Node 22.x + pnpm
# mariadb-client
############################
FROM php:7.4-apache

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV TZ=America/Santiago
ENV OPENCART_VERSION=3.0.2.0

# Install utilities + PHP extensions needed by OpenCart / the Webpay plugin,
# Node 22.x (active LTS, used to generate docs/INSTALLATION.pdf with
# markdown-pdf) without executing remote setup scripts, and Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    gnupg \
    less \
    libcurl4-openssl-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    mariadb-client \
    ripgrep \
    ssh \
    unzip \
    wget \
    zip \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" curl gd mysqli pdo_mysql zip \
 && a2enmod rewrite \
 && ln -snf /usr/share/zoneinfo/"$TZ" /etc/localtime \
 && echo "$TZ" > /etc/timezone \
 && mkdir -p /etc/apt/keyrings \
 && curl --proto "=https" -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
    | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
 && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main" \
    > /etc/apt/sources.list.d/nodesource.list \
 && apt-get update \
 && apt-get install -y --no-install-recommends nodejs \
 && corepack enable \
 && corepack prepare pnpm@11.5.2 --activate \
 && rm -rf /var/lib/apt/lists/* \
 && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && EXPECTED_SIGNATURE="$(curl -fsSL https://composer.github.io/installer.sig)" \
 && ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")" \
 && if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then \
        echo "ERROR: Invalid Composer installer signature" >&2; \
        rm composer-setup.php; \
        exit 1; \
    fi \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && php -r "unlink('composer-setup.php');" \
 && composer --version

# PHP settings needed by OpenCart / the Webpay plugin (uploads, execution time)
COPY .devcontainer/php-opencart.ini /usr/local/etc/php/conf.d/zz-opencart.ini

# Download the official OpenCart release into a template directory.
# The entrypoint copies it into the (persisted) webroot volume on first boot,
# the same way the official "wordpress" image seeds /var/www/html.
ADD https://github.com/opencart/opencart/releases/download/${OPENCART_VERSION}/${OPENCART_VERSION}-OpenCart.zip /tmp/opencart.zip

# Extract it, then set the workspace directory as a safe directory in Git
RUN unzip -q /tmp/opencart.zip "upload/*" -d /tmp/opencart \
 && mkdir -p /opt/opencart-src \
 && cp -r /tmp/opencart/upload/. /opt/opencart-src/ \
 && rm -rf /tmp/opencart /tmp/opencart.zip \
 && git config --global --add safe.directory /workspace

# Copy the shell settings / entrypoint to make the shop available
COPY .devcontainer/opencart-entrypoint.sh /usr/local/bin/opencart-entrypoint.sh
COPY .devcontainer/oc-setup.sh /usr/local/bin/oc-setup.sh
RUN chmod +x /usr/local/bin/opencart-entrypoint.sh /usr/local/bin/oc-setup.sh

WORKDIR /workspace

ENTRYPOINT ["opencart-entrypoint.sh"]
CMD ["apache2-foreground"]
