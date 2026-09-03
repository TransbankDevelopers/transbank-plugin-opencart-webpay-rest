#!/usr/bin/env bash
set -e

cd /workspace

echo "Instalando dependencias del SDK de Transbank..."
chmod +x config.sh package.sh
./config.sh

cat <<'EOF'

========================================

  Entorno de desarrollo listo.

  Web server: http://localhost:8080/
  Admin:      http://localhost:8080/admin
    user: admin
    password: admin123

  Para generar el paquete instalable del plugin:
    ./package.sh

  Luego instala el .zip generado desde:
    Admin > Extensions > Installer

========================================
EOF
