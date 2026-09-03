# DevContainer - OpenCart Webpay REST Plugin

Este devcontainer proporciona un entorno completo de desarrollo para el plugin Webpay REST de OpenCart 3.x.

## 🚀 Inicio rápido

1. Abre el proyecto en VS Code
2. Cuando se te pregunte, selecciona "Reopen in Container"
3. Espera a que se construya el contenedor (puede tomar unos minutos la primera vez)
4. Una vez listo, OpenCart estará disponible en http://localhost:8080/

## 📋 Servicios incluidos

- **OpenCart 3.0.2.0** (release oficial de [opencart/opencart](https://github.com/opencart/opencart)) con PHP 7.4.
- **Apache** para servir el contenido.
- **MariaDB 10.11** como base de datos.
- **Extensiones de VS Code** para trabajar con PHP.
- **Composer** para gestión de dependencias PHP.

OpenCart se instala automáticamente la primera vez que se levanta el contenedor (usa el `cli_install.php` que trae el propio OpenCart).

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

Todo vive en el mismo contenedor (`opencart`):

```
/workspace/                 # Código fuente de este repo (montado desde el host)
/var/www/html/              # Instalación de OpenCart (persistida en el volumen oc_data)
```

## 🔧 Configuración del módulo

OpenCart no soporta montar ni enlazar extensiones directamente desde el código fuente: el plugin se prueba empaquetándolo como `.ocmod.zip` e instalándolo desde el panel de administración.

### Desarrollo del módulo

1. Los cambios en el plugin **no se reflejan automáticamente** en la tienda — hay que volver a empaquetar e instalar el `.zip` (ver sección siguiente).
2. Los logs se guardan en `.devcontainer/logs/`.
3. Se ha incluido la carpeta `vendor` del plugin en Intelephense para tener las referencias de código del SDK de Transbank.

## 📦 Empaquetar e instalar el plugin

Desde el contenedor `opencart`:

```bash
./config.sh   # instala las dependencias del SDK de Transbank (composer)
./package.sh  # genera plugin-transbank-webpay-rest-opencart3-<version>.ocmod.zip
```

Luego, en http://localhost:8080/admin:

1. **Extensions > Installer**: sube el `.zip` generado.
2. **Extensions > Modifications**: selecciona "Transbank Webpay" y presiona "Refresh".
3. **Extensions > Extensions** (filtro "Payments"): activa "Webpay Plus".

Ver [docs/INSTALLATION.md](../docs/INSTALLATION.md) para el detalle con capturas.

## 📚 Dependencias

Las dependencias de Composer (SDK de Transbank) se instalan ejecutando `./config.sh`. Para instalar una nueva dependencia manualmente:

```bash
cd src/upload/system/library/transbank
composer require nueva-dependencia
```

## 🗄️ Base de datos

### Configuración por defecto

- Host: `mariadb`
- Puerto: `3306`
- Base de datos: `opencart`
- Usuario: `opencart`
- Contraseña: `opencart`

## 📝 Notas de desarrollo

1. **Permisos**: El usuario del contenedor `opencart` es root, por lo que tiene acceso completo. Apache y el script de instalación necesitan root para arrancar y ajustar permisos, por eso no se define un usuario no privilegiado (mismo criterio que las imágenes oficiales de Apache/WordPress).
2. **Persistencia**: Los datos de OpenCart y la base de datos **persisten** entre reinicios (volúmenes `oc_data` y `db_data`, gestionados por Docker).
3. **Refresco de código**: los cambios no se reflejan automáticamente — hay que reempaquetar e instalar el `.zip` (ver [Empaquetar e instalar el plugin](#-empaquetar-e-instalar-el-plugin)).
4. **Logs del plugin**: revisa `system/storage/logs/webpay-log.log` dentro del contenedor: `docker compose -f .devcontainer/docker-compose.yml exec opencart bash`.
5. **Reinstalar desde cero**: `docker compose -f .devcontainer/docker-compose.yml down -v` borra los volúmenes y fuerza una instalación limpia de OpenCart la próxima vez que se levante.

## Edición del devcontainer

En caso de editar el devcontainer, es importante reconstruir la imagen para que los cambios se reflejen si ya se usó anteriormente.
En algunas ocasiones VS Code detecta los cambios y sugiere reconstruir el contenedor. En caso contrario se debe hacer manualmente.

### Reconstruir el devcontainer

- Desde VS Code: abre la paleta de comandos (Ctrl/Cmd + Shift + P) → ejecuta **Dev Containers: Rebuild Container**.
- Alternativa rápida: haz clic en el icono de la esquina inferior izquierda (Remote) → "Reopen in Container" y acepta la opción de reconstruir si se muestra.
- Nota importante: la reconstrucción vuelve a crear la imagen y el contenedor; cualquier dato no persistente se perderá.
