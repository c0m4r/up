# up

![made with php](https://img.shields.io/badge/made%20with-php-%23777BB4?logo=php&logoColor=ffffff)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Test](https://github.com/c0m4r/up/workflows/PHPMD/badge.svg)](https://github.com/c0m4r/up/actions)
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

##### Quick setup

```
git clone https://github.com/c0m4r/up.git
cd up
wget https://getcomposer.org/download/2.7.9/composer.phar
echo "b6de5e65c199d80ba11897fbe1364e063e858d483f6a81a176c4d60f2b1d6347 composer.phar" | sha256sum -c || rm composer.phar
php composer.phar update
```

### Docker

This setup is based on [joseluisq/alpine-php-fpm](https://github.com/joseluisq/alpine-php-fpm)

```bash
git clone https://github.com/c0m4r/up.git
cd up
wget https://getcomposer.org/download/2.7.9/composer.phar
echo "b6de5e65c199d80ba11897fbe1364e063e858d483f6a81a176c4d60f2b1d6347 composer.phar" | sha256sum -c || rm composer.phar
docker compose up -d
docker compose exec php-fpm /bin/sh -c "cd /usr/share/nginx/html && php composer.phar update"
chown -R 82:82 i logs
```

Change uid:gid depending on your setup so the PHP-FPM have write access to the `i` and `logs`.

```bash
echo $(curl http://localhost:8080 &> /dev/null && ps -eo pid,uid,gid,command,cgroup | grep docke[r] | grep "php-fpm: pool www" | awk '{print $2":"$3}')
```

## paranoya integration

As an additional security measure you might want to scan uploaded files for malware.

See: [paranoya integration (experimental)](https://github.com/c0m4r/up/wiki/paranoya-integration-(experimental))

## Limitations

* Animated WebP is not supported at this point

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
