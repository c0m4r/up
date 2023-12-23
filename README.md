# up

An image uploader. Written in PHP, supports popular formats: png, jpeg, gif, webp.

Lets you upload multiple images in a row and see them all in one view. Generates embed codes and a permalink. No bullshit steps along the way.

Uploaded image is verified and re-created from its contents to provide some layer of security against exploits.

![image](https://github.com/c0m4r/up/assets/6292788/3cfa5183-c7db-44be-b9c3-e18ef564252d)

## Deps

* [PHP](https://www.php.net/)
* [Composer](https://getcomposer.org/)
* [jQuery](https://jquery.com/)
* [Twig](https://twig.symfony.com/)
* [Imagecraft by Coldume](https://github.com/coldume/imagecraft)
* [KEYS.css by Michael Hüneburg](https://github.com/michaelhue/keyscss)

## License

* Image Uploader (up) - [MIT](https://opensource.org/license/mit/)
* KEYS.css - [MIT](https://opensource.org/license/mit/)

## Installation methods

### Standalone

Requirements:

* PHP
  * Enable modules: `ctype`, `gd`, `iconv`, `mbstring`, `openssl` and `phar`.
  * Increase the `upload_max_filesize` and  `post_max_size` to `10M`
* Nginx: increase [client_max_body_size](https://nginx.org/en/docs/http/ngx_http_core_module.html#client_max_body_size) to `10M`
* HTTP Server or PHP-FPM must have write access to the `i` and `logs`.

Installation:

1. [Install Composer](https://getcomposer.org/download/) and update its dependencies: `php composer.phar update`.
2. Disallow access to dot files, logs and other unnecessary files in the web server configuration.
3. Edit config.php and adjust the settings to your needs.

### Docker

Check `docker-composer.yml` and `.docker/nginx` configs and adjust to your needs.

This setup is based on [joseluisq/alpine-php-fpm](https://github.com/joseluisq/alpine-php-fpm)

```bash
git clone https://github.com/c0m4r/up.git
cd up
#mv .docker/docker-compose.ipv6.yml docker-compose.yml # for IPv6-only
docker compose up -d
docker compose exec server sh -c "cd /usr/share/nginx/html && curl -o composer.phar https://getcomposer.org/download/latest-stable/composer.phar"
docker compose exec php-fpm sh -c "cd /usr/share/nginx/html && php composer.phar update"
chown -R 82:82 i logs
```

Change uid:gid depending on your setup so the PHP-FPM have write access to the `i` and `logs`.

```bash
echo chown -R $(curl http://localhost:8080 &> /dev/null && ps -eo pid,uid,gid,command,cgroup | grep docke[r] | grep "php-fpm: pool www" | awk '{print $2":"$3}') i logs
```

By default the web server (nginx) listens on 8080.

## Limitations

* This code was not intended for public use, I did it for myself so if you want to use it you better know what you're doing as securing and fixing it is on your side
* Animated WebP is not supported at this point
