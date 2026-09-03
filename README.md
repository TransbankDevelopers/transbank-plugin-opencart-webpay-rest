# Transbank Opencart 3.x Webpay Plugin

## Descripción

Este plugin de Opencart 3.x implementa 
el [SDK PHP de Transbank](https://github.com/TransbankDevelopers/transbank-sdk-php) 

## Dependencias

* transbank/transbank-sdk (composer)
* fpdf


## Instalación del plugin para un comercio

El manual de instalación para el usuario final se encuentra disponible [acá](docs/INSTALLATION.md) o 
en PDF [acá](https://github.com/TransbankDevelopers/transbank-plugin-opencart-webpay/blob/master/docs/INSTALLATION.pdf)


## Nota  
- La versión del sdk de php se encuentra en el archivo `config.sh` y 
en `src/upload/system/library/transbank/composer.json`

**NOTA:** La versión del sdk de php se encuentra en el script config.sh

## Preparar el proyecto para bajar dependencias

    ./config.sh

## Crear una versión del plugin empaquetado 

    ./package.sh

## Desarrollo

Para apoyar el levantamiento rápido de un ambiente de desarrollo, hemos creado un [Dev Container](https://containers.dev/)
que levanta OpenCart 3.x + MariaDB junto a un contenedor de trabajo con PHP, Composer y Node.

Para usarlo, abre el proyecto en VS Code y selecciona "Reopen in Container". Ver el detalle en [.devcontainer/README.md](.devcontainer/README.md).

### Actualizar el PDF de instalación
Instalar `markdown-pdf` con `npm i -g markdown-pdf` y luego para generar el archivo `INSTALLATION.pdf` sea debe ejecutar: 
```
cd docs/
markdown-pdf INSTALLATION.md
```

## Generar una nueva versión

Para generar una nueva versión, se debe crear un PR (con un título "Prepare release X.Y.Z" con los valores que correspondan para `X`, `Y` y `Z`). Se debe seguir el estándar semver para determinar si se incrementa el valor de `X` (si hay cambios no retrocompatibles), `Y` (para mejoras retrocompatibles) o `Z` (si sólo hubo correcciones a bugs).

En ese PR deben incluirse los siguientes cambios:

1. Modificar el archivo `CHANGELOG.md` para incluir una nueva entrada (al comienzo) para `X.Y.Z` que explique en español los cambios.

Luego de obtener aprobación del pull request, debes mezclar a master e inmediatamente generar un release en GitHub con el tag `X.Y.Z`. En la descripción del release debes poner lo mismo que agregaste al changelog.

Con eso, el workflow de GitHub Actions [`release.yml`](.github/workflows/release.yml) generará automáticamente el paquete del plugin y lo adjuntará al Release de GitHub como archivo `.zip`.

## Estándares generales

- Para los commits nos basamos en las siguientes normas: https://github.com/angular/angular.js/blob/master/DEVELOPERS.md#commits👀
- Todas las mezclas a master se hacen mediante Pull Request ⬇️
- Usamos inglés para los mensajes de commit 💬
- Se pueden usar tokens como WIP en el subject de un commit separando el token con ':', por ejemplo -> 'WIP: this is a useful commit message'
- Para los nombres de ramas también usamos inglés
- Se asume que una rama de feature no mezclada, es un feature no terminado ⚠️
- El nombre de las ramas va en minúscula 🔤
- El nombre de la rama se separa con '-' y las ramas comienzan con alguno de los short lead tokens definidos a continuación, por ejemplo -> 'feat/tokens-configuration' 🌿
  
### **Short lead tokens**

`WIP` = En progreso

`feat` = Nuevos features

`fix` = Corrección de un bug

`docs` = Cambios solo de documentación

`style` = Cambios que no afectan el significado del código (espaciado, formateo de código, comillas faltantes, etc)

`refactor` = Un cambio en el código que no arregla un bug ni agrega una funcionalidad

`perf` = Cambio que mejora el rendimiento

`test` = Agregar test faltantes o los corrige

`chore` = Cambios en el build o herramientas auxiliares y librerías


## Reglas

1️⃣ -  Si no se añaden test en el pull request, se debe añadir un video o gif mostrando el cambio realizado y demostrando que la rama no rompe nada.

2️⃣ -  El pr debe tener 2 o mas aprobaciones para hacer el merge

3️⃣ - si un commit revierte  un commit anterior debera comenzar con "revert:" seguido con texto del commit anterior

## Pull Request

### Asunto ✉️

- Debe comenzar con el short lead token definido para la rama, seguido de ':' y una breve descripción del cambio
- Usar imperativos en tiempo presente: "change" no "changed" ni "changes"
- No usar mayúscula en el inicio
- No usar punto . al final

### Descripción 📃

Igual que en el asunto, usar imperativo y en tiempo presente. Debe incluir una mayor explicación de lo que se hizo en el pull request. Si no se añaden test en el pull request, se debe añadir un video o gif mostrando el cambio realizado y demostrando que la rama no rompe nada.
