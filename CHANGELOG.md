# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [5.0.0] - 2026-11-07

### Added

- Dockerfile building the app on the official PHP 8.5.12 FPM and nginx images, pinned by digest
- hardened docker compose setup: non-root PHP-FPM, read-only filesystems, dropped capabilities, named volumes
- composer dependency policy: blocks versions with advisories, malware and abandoned packages, 7-day release cooldown
- CI workflow: composer validate/audit, PHP lint, PHPStan (max level, strict and deprecation rules), Docker smoke test, zizmor and dependency review
- Dependabot for composer, Docker and GitHub Actions with a 7-day cooldown

### Changed

- [breaking] requires PHP 8.5.12 or newer
- [breaking] docker compose builds images instead of bind-mounting the source tree, IPv6 and paranoya setups are now override files
- [breaking] PHP-FPM listens on port 9000
- animated GIFs are re-encoded with GD by the bundled Up\AnimatedGif class
- updated twig/twig to 3.30 and jQuery to 4.0
- mbstring and ctype polyfills replaced by the native extensions
- README installation instructions no longer run `composer update` and verify Composer before running it

### Fixed

- fatal error in upload.php when an upload failed with an error other than "file too large"
- logs ignoring the `logs_dir` setting
- upload.php responses now use the application/json content type
- robots.txt being denied by the nginx configuration
- uploads close to the size limit being rejected by nginx

### Removed

- [breaking] coldume/imagecraft and its abandoned Symfony 2.8 dependencies, one of them with an unfixable XXE advisory
- PHPMD workflow

### Security

- the paranoya setup shares uploads through a dedicated volume instead of the host's /tmp

## [4.0.1] - 2024-01-18

### Fixed

- handling lang item for malware scan
- manifest icon paths
- displayed file size limit not matching configuration

## [4.0.0] - 2024-01-17

- [breaking] replaced external IOC and YARA scanner with paranoya

## [3.0.1] - 2024-01-14

### Changed

- updated .docker/docker-compose-with-loki.yml
- default allowed_hosts in config.php
- removed unused code from js/up.js

## [3.0.0] - 2023-12-30

### Added

- pwa manifest optimizations
- added webroot and ssl options in config.php
- minified css and js

### Changed

- code refactoring 

### Fixed

- css contrast ratio fixes
- mobile optimization

### Removed

- removed obsolete code

## [2.5.0] - 2023-12-30

### Added

- PWA and HTML5 optimization
- added manifest.json for PWA compatibility
- added additional icons
- added accept attribute with supported image types

### Changed

- CSP header now has base-uri 'self'
- minified nginx configuration

## [2.4.1] - 2023-12-28

### Changed

- Loki IOC/Yara Scanner integration (experimental) switched to Loki-daemonized

## [2.4.0] - 2023-12-27

### Added

- Loki IOC/Yara Scanner integration (experimental)

## [2.3.0] - 2023-12-25

### Added

- added French language: fr (français)
- added Japanese language: ja (日本語)
- added Korean language: ko (한국어)
- added Ukrainian language: ua (українська)

### Changed

- change ipv6 docker example addresses

## [2.2.0] - 2023-12-25

### Added

- added csp header with nonce
- added config options for sri and csp

### Changed

- code refactoring
- added PHP_EXPOSE_PHP=Off in docker compose configs
- bg image converted to webp
- improved menu

### Removed

- removed obsolete headers from docker nginx config

## [2.1.4] - 2023-12-24

### Added

- added missing composer.lock

## [2.1.3] - 2023-12-24

### Added

- added github meta tags
- added security policy

### Security

- added js escaping html chars from file name and size

## [2.1.2] - 2023-12-23

### Added

- favicon added
- added missing input label

## [2.1.1] - 2023-12-23

### Changed

- increased files_limit config option
- removed outstanding inline js
- reworked readme
- updated composer.json with required php modules

## [2.1.0] - 2023-12-23

### Added

- Subresource Integrity (SRI) implementation

### Changed

- improved nginx config regex
- inline js moved to js/up.js

### Removed

- random input id/name
- input_filename from config.php and other places

## [2.0.2] - 2023-12-22

### Security

- New config option: "allowed_hosts" comma-separated list of allowed HTTP_HOST
- Added HTTP headers validation for URL generation

## [2.0.1] - 2023-12-22

### Added

- Composer presence validation
- Meta tags: viewpoint and description
- Added filtered HTTP_X_FORWARDED_FOR to be logged if behind reverse proxy

### Changed

- Moved any inline CSS to the styles.css file

### Security

- Removed vulnerable method of mime type checking
- Added two step mime type validation using finfo and exif_imagetype

## [2.0.0] - 2023-12-21

### Added

- Docker IPv6 configuration
- Imagecraft dependency

### Changed

- GDEnhancer has been replaced with Imagecraft
- New log format: [Y-m-d H:i:s] ip url size

### Fixed

- Animated GIF support
- HTTP_ACCEPT_LANGUAGE validation

### Removed

- GDEnhancer dependency

## [1.2.0] - 2023-12-20

### Added

- Docker configuration

### Fixed

- Image URL generation method

## [1.1.0] - 2023-12-20

### Added

- WebP support
- config file
- write permission validation

## [1.0.0] - 2023-12-19

### Added

- Initial release with briefly refactored code written in 2013
