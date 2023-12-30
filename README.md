# up

An image uploader. Written in PHP, supports popular formats: png, jpeg, gif, webp.

Lets you upload multiple images in a row and see them all in one view. Generates embed codes and a permalink. No bullshit steps along the way.

Uploaded image is verified and re-created from its contents to provide some layer of security against exploits.

![image](https://github.com/c0m4r/up/assets/6292788/3cfa5183-c7db-44be-b9c3-e18ef564252d)

<div align="center">

![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white) ![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=for-the-badge&logo=html5&logoColor=white) ![jQuery](https://img.shields.io/badge/jquery-%230769AD.svg?style=for-the-badge&logo=jquery&logoColor=white) ![PWA](https://img.shields.io/badge/webapp-black.svg?style=for-the-badge&logo=pwa&logoColor=white)

![image](https://github.com/c0m4r/ip-info-page/assets/6292788/4bfc8fc3-fb23-4386-87e8-1e22c686aefb)

</div>

## Deps

[PHP](https://www.php.net/) | [Composer](https://getcomposer.org/) | [jQuery](https://jquery.com/) | [Twig](https://twig.symfony.com/) | [Imagecraft](https://github.com/coldume/imagecraft) | [KEYS.css by Michael Hüneburg](https://github.com/michaelhue/keyscss)

## Installation methods

### Standalone

##### Requirements

* PHP
  * Enable modules: `ctype`, `exif`, `fileinfo`, `gd`, `iconv`, `mbstring`, `openssl` and `phar`.
  * Increase the `upload_max_filesize` and  `post_max_size` to `10M`
* Nginx: increase [client_max_body_size](https://nginx.org/en/docs/http/ngx_http_core_module.html#client_max_body_size) to `10M`
* HTTP Server or PHP-FPM must have write access to the `i` and `logs`.

##### Installation

1. [Install Composer](https://getcomposer.org/download/) and update its dependencies: `php composer.phar update`.
2. Disallow access to dot files, logs and other unnecessary files in the web server configuration.
3. Edit config.php, replace `allowed_hosts` with your domain and adjust the settings to your needs.
4. Edit manifest.json and change `start_url` for PWA.

### Docker

This setup is based on [joseluisq/alpine-php-fpm](https://github.com/joseluisq/alpine-php-fpm)

```bash
git clone https://github.com/c0m4r/up.git
cd up
docker compose up -d
chown -R 82:82 i logs
```

Change uid:gid depending on your setup so the PHP-FPM have write access to the `i` and `logs`.

```bash
echo $(curl http://localhost:8080 &> /dev/null && ps -eo pid,uid,gid,command,cgroup | grep docke[r] | grep "php-fpm: pool www" | awk '{print $2":"$3}')
```

## Loki integration

As an additional security measure you might want to scan uploaded files for malware.

See: [Loki integration (experimental)](https://github.com/c0m4r/up/wiki/Loki-integration-(experimental))

## Limitations

* Animated WebP is not supported at this point
