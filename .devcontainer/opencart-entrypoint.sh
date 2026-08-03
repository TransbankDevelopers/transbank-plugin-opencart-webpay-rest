#!/usr/bin/env bash
set -e

WEBROOT=/var/www/html

DB_HOST="${DB_HOST:-mariadb}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-opencart}"
DB_USER="${DB_USER:-opencart}"
DB_PASSWORD="${DB_PASSWORD:-opencart}"
OC_ADMIN_USER="${OC_ADMIN_USER:-admin}"
OC_ADMIN_PASSWORD="${OC_ADMIN_PASSWORD:-admin123}"
OC_ADMIN_EMAIL="${OC_ADMIN_EMAIL:-admin@example.com}"
HTTP_SERVER="${HTTP_SERVER:-http://localhost:8080/}"

if [[ ! -f "$WEBROOT/config.php" ]]; then
  echo "Copiando código fuente de OpenCart ${OPENCART_VERSION} a ${WEBROOT}..."
  cp -rn /opt/opencart-src/. "$WEBROOT/"

  echo "Esperando a que la base de datos esté disponible..."
  until mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASSWORD" --silent >/dev/null 2>&1; do
    sleep 2
  done

  echo "Instalando OpenCart via cli_install.php..."
  (
    cd "$WEBROOT/install"
    php cli_install.php install \
      --db_hostname "$DB_HOST" \
      --db_username "$DB_USER" \
      --db_password "$DB_PASSWORD" \
      --db_database "$DB_NAME" \
      --db_driver mysqli \
      --db_port "$DB_PORT" \
      --username "$OC_ADMIN_USER" \
      --password "$OC_ADMIN_PASSWORD" \
      --email "$OC_ADMIN_EMAIL" \
      --http_server "$HTTP_SERVER"
  )

  # El instalador no borra este directorio; se elimina para no dejar
  # la reinstalación expuesta, igual que en una instalación real.
  rm -rf "$WEBROOT/install"
fi

chown -R www-data:www-data "$WEBROOT"

exec "$@"
