############################
# image with
# PHP 7.4 CLI
# Composer 2.x
# Node 22.x + pnpm
# mariadb-client
############################
FROM php:7.4-cli

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV TZ=America/Santiago

# install utilities
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    gnupg \
    less \
    libzip-dev \
    mariadb-client \
    ripgrep \
    ssh \
    unzip \
    wget \
    zip \
 && docker-php-ext-install zip \
 && rm -rf /var/lib/apt/lists/*

# Time zone settings
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
 && echo "$TZ" > /etc/timezone

# Install node 22.x (active LTS) without executing remote setup scripts
# (used to generate docs/INSTALLATION.pdf with markdown-pdf)
RUN mkdir -p /etc/apt/keyrings \
 && curl --proto "=https" -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
    | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
 && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main" \
    > /etc/apt/sources.list.d/nodesource.list \
 && apt-get update \
 && apt-get install -y --no-install-recommends nodejs \
 && corepack enable \
 && corepack prepare pnpm@11.5.2 --activate \
 && rm -rf /var/lib/apt/lists/*

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && php -r "unlink('composer-setup.php');" \
 && composer --version

# Set the workspace directory as a safe directory in Git
RUN git config --global --add safe.directory /workspace

# Copy the shell settings to make the shop available
COPY .devcontainer/oc-setup.sh /usr/local/bin/oc-setup.sh
RUN chmod +x /usr/local/bin/oc-setup.sh

WORKDIR /workspace
