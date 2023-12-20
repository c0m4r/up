# up

An image uploader. Written in PHP, supports popular formats: png, jpeg, gif, webp.

Lets you upload multiple images in a row and see them all in one view. Generates embed codes and a permalink. No bullshit steps along the way.

![image](https://github.com/c0m4r/up/assets/6292788/87c11a21-3d22-433f-b88d-1bf661555b28)

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

Example for the Alpine Linux:

```
apk add php83-ctype php83-gd php83-iconv php83-mbstring php83-openssl php83-phar
```

2. [Install Composer](https://getcomposer.org/download/) and update its dependencies:

```
php composer.phar update
```

3. HTTP Server or PHP-FPM must have write access to the `i` and `logs`.

Example for nginx:

```
chown nginx: i logs
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

6. Edit config.php and adjust the settings to your needs.

## Known issues

* GD Enhancer won't work with modern PHP so you'll not be able to upload animated GIF images until it's replaced with something else.
* Animated WebP is not supported at this point
