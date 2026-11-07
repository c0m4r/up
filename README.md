# up

![made with php](https://img.shields.io/badge/made%20with-php-%23777BB4?logo=php&logoColor=ffffff)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![CI](https://github.com/c0m4r/up/actions/workflows/ci.yml/badge.svg)](https://github.com/c0m4r/up/actions/workflows/ci.yml)
[![CodeFactor](https://www.codefactor.io/repository/github/c0m4r/up/badge)](https://www.codefactor.io/repository/github/c0m4r/up)

An image uploader. Written in PHP, supports popular formats: png, jpeg, gif, webp.

Lets you upload multiple images in a row and see them all in one view. Generates a permalink and lets you copy it right away. No bullshit steps along the way.

Uploaded image is verified and re-created from its contents to provide some layer of security against exploits.

<div align="center">

![image](https://github.com/c0m4r/up/assets/6292788/b05081b1-73ff-4777-b43f-b56b87d86497)

![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white) ![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=for-the-badge&logo=html5&logoColor=white) ![jQuery](https://img.shields.io/badge/jquery-%230769AD.svg?style=for-the-badge&logo=jquery&logoColor=white) ![PWA](https://img.shields.io/badge/webapp-black.svg?style=for-the-badge&logo=pwa&logoColor=white)

![image](https://github.com/c0m4r/ip-info-page/assets/6292788/4bfc8fc3-fb23-4386-87e8-1e22c686aefb)

</div>

## Deps

[PHP](https://www.php.net/) 8.5 | [Composer](https://getcomposer.org/) | [jQuery](https://jquery.com/) | [Twig](https://twig.symfony.com/) | [KEYS.css by Michael Hüneburg](https://github.com/michaelhue/keyscss)

## Installation methods

For production, check out a [release tag](https://github.com/c0m4r/up/releases) instead of `main` (`git checkout <tag>`).

### Docker (recommended)

```bash
git clone https://github.com/c0m4r/up.git
cd up
# Edit config.php first: set allowed_hosts to your domain (include the port if it is not 80/443)
docker compose up -d --build
```

The uploader listens on port `8080`; put it behind a TLS-terminating reverse proxy (`ssl` in config.php controls whether generated links use `https`).

What you get:

* Images are built locally from the official [`php`](https://hub.docker.com/_/php) (8.5.12, FPM, Alpine) and [`nginx`](https://hub.docker.com/_/nginx) images, pinned by digest.
* Composer dependencies are installed at build time from `composer.lock`, with plugins and scripts disabled. Nothing needs to be downloaded or run on the host.
* PHP-FPM runs as `www-data`, both containers have a read-only root filesystem, drop all unneeded capabilities and set `no-new-privileges`.
* Uploads and logs live in the `uploads` and `logs` named volumes, e.g. `docker compose exec php-fpm tail logs/uploads.log`.

To update, pull the new version and rebuild: `git pull && docker compose up -d --build`.

Variants are available as override files:

```bash
# IPv6 network
docker compose -f docker-compose.yml -f .docker/docker-compose.ipv6.yml up -d --build
# paranoya malware scanner, see the paranoya integration section below
docker compose -f docker-compose.yml -f .docker/docker-compose-with-paranoya.yml up -d --build
```

### Standalone

##### Requirements

* PHP 8.5.12 or newer
  * Required extensions: `ctype`, `exif`, `fileinfo`, `gd` (with JPEG, PNG and WebP support) and `mbstring`.
  * Optional: `sockets` for the paranoya integration.
  * Running `composer.phar` needs `openssl` and `phar` as well.
  * Set `upload_max_filesize = 10M` and `post_max_size = 11M` (see [.docker/php/up.ini](.docker/php/up.ini)).
* Nginx: set [client_max_body_size](https://nginx.org/en/docs/http/ngx_http_core_module.html#client_max_body_size) to `11M`. [.docker/nginx](.docker/nginx) contains a configuration that only exposes the public files.
* The web server or PHP-FPM user must have write access to `i` and `logs`.

##### Installation

```bash
git clone https://github.com/c0m4r/up.git
cd up
# Download Composer and verify it against the checksum pinned here before running it
curl -fsSLO https://getcomposer.org/download/2.11.1/composer.phar
echo "6f1b891e1d4927d62740276c540fb490e47689e19e102c49e8da849794882f8c  composer.phar" | sha256sum --check --strict || rm -f composer.phar
# Install exactly what composer.lock pins: no dev tools, no plugins, no scripts
php composer.phar install --no-dev --no-plugins --no-scripts --classmap-authoritative
php composer.phar audit --locked
```

Then:

1. Disallow access to dot files, logs and other non-public files in the web server configuration (see [.docker/nginx](.docker/nginx)).
2. Edit config.php, replace `allowed_hosts` with your domain and adjust the settings to your needs.
3. Edit manifest.json and change `start_url` for PWA.

Always use `composer install` to deploy. `composer update` ignores `composer.lock` and resolves the newest available versions, which is a job for development, not for a server.

## Supply chain security

* **Dependencies:** only Twig and jQuery (plus one tiny Symfony package Twig needs). Uploaded images are re-encoded with GD, without third-party image libraries.
* **Composer:** `composer.lock` is the single source of truth. [Plugins are disabled](https://getcomposer.org/doc/06-config.md#allow-plugins) and the [dependency policy](https://getcomposer.org/doc/06-config.md#policy) refuses versions with security advisories, versions flagged as malware and abandoned packages. A 7-day [cooldown](https://getcomposer.org/doc/06-config.md#cooldown) keeps freshly published (possibly compromised) releases out of `composer update`.
* **Containers:** base images are pinned by tag and digest. Builds never fetch unpinned code.
* **CI:** GitHub Actions are pinned to commit SHAs, run with a read-only token and do not persist credentials. Workflows are audited with [zizmor](https://docs.zizmor.sh/), and pull requests that add vulnerable dependencies fail the [dependency review](https://github.com/actions/dependency-review-action). `composer audit` runs on every push and weekly.
* **Updates:** Dependabot proposes Composer, Docker and GitHub Actions updates after the same 7-day cooldown. Security updates are not delayed.

## Development

The CI checks run in the same PHP image as production:

```bash
docker build --target dev -t up:dev .
docker run --rm up:dev composer validate --strict
docker run --rm up:dev composer audit --locked
docker run --rm up:dev vendor/bin/phpstan analyse
```

[PHPStan](https://phpstan.org/) runs at the maximum level with the strict and deprecation rules.

To update dependencies, run `php composer.phar update` and commit `composer.lock`. If a security fix is still inside the cooldown period, update just that package with `php composer.phar update --no-cooldown vendor/package`.

When bumping Composer, update the version and checksum above (from `https://getcomposer.org/download/<version>/composer.phar.sha256sum`). Dependabot keeps the `composer` image in the Dockerfile current, but not this README.

## paranoya integration

As an additional security measure you might want to scan uploaded files for malware.

See: [paranoya integration (experimental)](https://github.com/c0m4r/up/wiki/paranoya-integration-(experimental))

With Docker, clone [paranoya](https://github.com/c0m4r/paranoya) into the project directory, set `"paranoya" => true` and `"paranoya_host" => 'paranoya'` in config.php and start the stack with `.docker/docker-compose-with-paranoya.yml` (see above). PHP and the scanner share a dedicated volume for uploaded files instead of the host's `/tmp`.

## Limitations

* Animated WebP is not supported at this point
* Animated GIFs are limited to 1000 frames

## License

> MIT License
> 
> Copyright (c) 2023 c0m4r
> 
> Permission is hereby granted, free of charge, to any person obtaining a copy
> of this software and associated documentation files (the "Software"), to deal
> in the Software without restriction, including without limitation the rights
> to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
> copies of the Software, and to permit persons to whom the Software is
> furnished to do so, subject to the following conditions:
> 
> The above copyright notice and this permission notice shall be included in all
> copies or substantial portions of the Software.
> 
> THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
> IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
> FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
> AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
> LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
> OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
> SOFTWARE.

## Funding

If you found this software helpful, please consider [making donation](https://en.wosp.org.pl/fundacja/jak-wspierac-wosp/wesprzyj-online) to a charity on my behalf. Thank you.
