# Changelog
Todos los cambios notables a este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
y este proyecto adhiere a [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [1.1.1] - 2021-04-30
### Fixed
- Se arregla error que evitaba que el plugin se pudiese usar correctamente en modo producción


## [1.1.0] - 2021-04-09
### Changed
- Se utiliza el nuevo SDK de PHP 2.0-beta
- Ahora se utiliza API 1.2 del API de Transbank
- Redirección desde Transbank a Opencart ahora es por GET y no por POST.
- Esto resuelve posibles problemas de perder la sesión al volver del pago. 


## [1.0.2] - 2021-02-02
### Changed
- Configura versión de PHP 5.6.1.

## [1.0.1] - 2021-02-01
### Changed
- Corrige [error en variables](https://github.com/TransbankDevelopers/transbank-plugin-opencart-webpay-rest/issues/7) de Producción ('LIVE') e Integración ('TEST').
- Actualiza la versión de SDK PHP a la última disponible (1.10.1).

## [1.0.0] - 2020-11-04
### Added
- Release inicial
