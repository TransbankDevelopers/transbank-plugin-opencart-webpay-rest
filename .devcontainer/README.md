# DevContainer - OpenCart Webpay REST Plugin

Este devcontainer proporciona un entorno completo de desarrollo para el plugin Webpay REST de OpenCart 3.x.

## 🚀 Inicio rápido

1. Abre el proyecto en VS Code
2. Cuando se te pregunte, selecciona "Reopen in Container"
3. Espera a que se construya el contenedor (puede tomar unos minutos la primera vez)
4. Una vez listo, OpenCart estará disponible en http://localhost:8080/

## 📋 Servicios incluidos

- **OpenCart 3.0.2.0** (release oficial de [opencart/opencart](https://github.com/opencart/opencart)) con PHP 7.4 + Apache. Se instala automáticamente la primera vez que se levanta el contenedor `opencart` (usa el `cli_install.php` que trae el propio OpenCart).
- **MariaDB 10.11** como base de datos.
- **Contenedor de trabajo** (`workspace`) con PHP 7.4, Composer y Node 22.x (con pnpm vía corepack) para editar y empaquetar el plugin.
- **Extensiones de VS Code** para trabajar con PHP.

## 🔗 URLs de acceso

| Servicio      | Acceso                        | Credenciales           |
| ------------- | ------------------------------ | ----------------------|
| OpenCart      | http://localhost:8080/              | -                      |
| Admin Panel   | http://localhost:8080/admin         | admin / admin123       |
| Base de datos | VS Code SQLTools/MySQL Client  | opencart / opencart    |

## 🛠️ Herramientas de desarrollo

### Administración de base de datos con VS Code

El devcontainer incluye una extensión para trabajar con la base de datos:

#### SQLTools

- **Acceso**: Ctrl/Cmd + Shift + P → "SQLTools: Connect"
- **Conexión preconfigurada**: `OpenCart Mysql`
- **Funcionalidades**: Explorar tablas, ejecutar queries, exportar datos

### Estructura del proyecto en el contenedor

```
/workspace/                 # Código fuente de este repo (montado desde el host, contenedor "workspace")
/var/www/html/              # Instalación de OpenCart (contenedor "opencart", persistida en el volumen oc_data)
```

## 📦 Empaquetar e instalar el plugin

A diferencia de WordPress/WooCommerce, OpenCart no soporta symlinks de plugins: los cambios se prueban empaquetando
el plugin como `.ocmod.zip` e instalándolo desde el panel de administración.

Desde el contenedor `workspace`:

```bash
./config.sh   # instala las dependencias del SDK de Transbank (composer)
./package.sh  # genera plugin-transbank-webpay-rest-opencart3-<version>.ocmod.zip
```

Luego, en http://localhost:8080/admin:

1. **Extensions > Installer**: sube el `.zip` generado.
2. **Extensions > Modifications**: selecciona "Transbank Webpay" y presiona "Refresh".
3. **Extensions > Extensions** (filtro "Payments"): activa "Webpay Plus".

Ver [docs/INSTALLATION.md](../docs/INSTALLATION.md) para el detalle con capturas.

## 🗄️ Base de datos

### Configuración por defecto

- Host: `mariadb`
- Puerto: `3306`
- Base de datos: `opencart`
- Usuario: `opencart`
- Contraseña: `opencart`

## 📝 Notas de desarrollo

1. **Permisos**: El usuario del contenedor `workspace` es root, por lo que tiene acceso completo.
2. **Persistencia**: Los datos de OpenCart y la base de datos **persisten** entre reinicios (volúmenes `oc_data` y `db_data`, gestionados por Docker).
3. **Logs del plugin**: revisa `system/storage/logs/webpay-log.log` dentro del contenedor `opencart`: `docker compose -f .devcontainer/docker-compose.yml exec opencart bash`.
4. **Reinstalar desde cero**: `docker compose -f .devcontainer/docker-compose.yml down -v` borra los volúmenes y fuerza una instalación limpia de OpenCart la próxima vez que se levante.

## Edición del devcontainer

En caso de editar el devcontainer, es importante reconstruir la imagen para que los cambios se reflejen si ya se usó anteriormente.
En algunas ocasiones VS Code detecta los cambios y sugiere reconstruir el contenedor. En caso contrario se debe hacer manualmente.

### Reconstruir el devcontainer

- Desde VS Code: abre la paleta de comandos (Ctrl/Cmd + Shift + P) → ejecuta **Dev Containers: Rebuild Container**.
- Alternativa rápida: haz clic en el icono de la esquina inferior izquierda (Remote) → "Reopen in Container" y acepta la opción de reconstruir si se muestra.
- Nota importante: la reconstrucción vuelve a crear la imagen y el contenedor; cualquier dato no persistente se perderá.
