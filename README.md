# up

An image uploader. Written in PHP, supports popular formats: png, jpeg, gif, webp.

Lets you upload multiple images in a row and see them all in one view. Generates embed codes and a permalink. No bullshit steps along the way.

![image](https://github.com/c0m4r/up/assets/6292788/3cfa5183-c7db-44be-b9c3-e18ef564252d)

## Deps

* [PHP](https://www.php.net/)
* [Composer](https://getcomposer.org/)
* [jQuery](https://jquery.com/)
* [Twig](https://twig.symfony.com/)
* [GD Enhancer by Coldume](https://github.com/coldume/gd-enhancer)
* [KEYS.css by Michael Hüneburg](https://github.com/michaelhue/keyscss)

## License

* Image Uploader (up) - [MIT](https://opensource.org/license/mit/)
* GD Enhancer - [GNU GPL v3](https://opensource.org/license/gpl-3-0/)
* KEYS.css - [MIT](https://opensource.org/license/mit/)

## Installation

1. PHP: Enable modules: `ctype`, `gd`, `iconv`, `mbstring`, `openssl` and `phar`.

```
# example for alpine linux
apk add php83-ctype php83-gd php83-iconv php83-mbstring php83-openssl php83-phar
```

2. [Install Composer](https://getcomposer.org/download/) and update its dependencies:

```bash
php composer.phar update
```

3. HTTP Server or PHP-FPM must have write access to the `i` and `logs`.

```bash
chown nginx:nginx i logs
```

4. Nginx: change [client_max_body_size](https://nginx.org/en/docs/http/ngx_http_core_module.html#client_max_body_size)

```
client_max_body_size 10M;
```

5. PHP: Set the `upload_max_filesize` and  `post_max_size`

```
upload_max_filesize = 10M
post_max_size = 10M
```

6. Disallow access to dot files, logs in the web server configuration.

```
    # example for nginx

    # Deny access to dot files by default
    location ~ /\. {
        log_not_found off;
        deny all;
    }

    location ~ (composer|docker-compose|vendor|logs|CHANGELOG|LICENSE) {
      	log_not_found off;
        deny all;
    }
```

7. Edit config.php and adjust the settings to your needs.

## Docker

Check `docker-composer.yml` and `.docker/nginx` configs and adjust to your needs.

This setup is based on [joseluisq/alpine-php-fpm](https://github.com/joseluisq/alpine-php-fpm)

```bash
git clone https://github.com/c0m4r/up.git
cd up
docker compose up -d
docker compose exec php-fpm sh -c "cd /usr/share/nginx/html && wget https://getcomposer.org/download/latest-stable/composer.phar && php composer.phar update"
chown -R 82:82 i logs
```

Change uid:gid depending on your setup so the PHP-FPM have write access to the `i` and `logs`.

By default the web server (nginx) listens on 8080.

## Known issues

* This code was not intended for public use, I did it for myself so if you want to use it you better know what you're doing as securing and fixing it is on your side
* GD Enhancer won't work with modern PHP so you'll not be able to upload animated GIF images until it's replaced with something else.
* Animated WebP is not supported at this point
