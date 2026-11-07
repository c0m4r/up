<?php

// up - an image uploader
// https://github.com/c0m4r/up
// 
// MIT License
// 
// Copyright (c) 2023 c0m4r
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in all
// copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE.

declare(strict_types=1);

use Up\AnimatedGif;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';

/** @var UpConfig $config */
/** @var array<string, string> $lang */

header('Content-Type: application/json; charset=utf-8');

/**
 * Sends the JSON reply expected by js/up.js and ends the request.
 */
function respond(string $key, string $value): never
{
    exit(json_encode([$key => $value], JSON_THROW_ON_ERROR));
}

/**
 * Runs a GD encoder that writes to the output buffer and returns its output.
 *
 * @param callable(): bool $encoder
 */
function capture(callable $encoder): ?string
{
    ob_start();
    $written = $encoder();
    $output = ob_get_clean();

    return $written && is_string($output) && $output !== '' ? $output : null;
}

/**
 * Decodes the uploaded image and builds a new one from its pixel data, which
 * drops metadata and anything smuggled outside of the image data.
 *
 * @return array{string, string}|null file extension and image data, or null if the image could not be re-created
 */
function recreate(string $path, string $mime): ?array
{
    $image_type = exif_imagetype($path);

    if ($mime === 'image/gif' && $image_type === IMAGETYPE_GIF) {
        $contents = file_get_contents($path);

        try {
            $gif = AnimatedGif::parse($contents === false ? '' : $contents);

            if ($gif->isAnimated()) {
                return ['ani.gif', $gif->reencode()];
            }
        } catch (UnexpectedValueException) {
            return null;
        }

        $image = imagecreatefromgif($path);
        $output = $image === false ? null : capture(static fn (): bool => imagegif($image));
        $extension = 'gif';
    } elseif (in_array($mime, ['image/jpeg', 'image/pjpeg'], true) && $image_type === IMAGETYPE_JPEG) {
        $image = imagecreatefromjpeg($path);
        $output = $image === false ? null : capture(static fn (): bool => imagejpeg($image));
        $extension = 'jpg';
    } elseif (in_array($mime, ['image/png', 'image/x-png'], true) && $image_type === IMAGETYPE_PNG) {
        $image = imagecreatefrompng($path);

        if ($image !== false) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $output = $image === false ? null : capture(static fn (): bool => imagepng($image));
        $extension = 'png';
    } elseif ($mime === 'image/webp' && $image_type === IMAGETYPE_WEBP) {
        $image = imagecreatefromwebp($path);
        $output = $image === false ? null : capture(static fn (): bool => imagewebp($image));
        $extension = 'webp';
    } else {
        return null;
    }

    return $output === null ? null : [$extension, $output];
}

/**
 * Asks the paranoya daemon to scan a file, fails open if it is unreachable.
 */
function paranoya_detects_malware(string $host, int $port, string $path): bool
{
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

    if ($socket === false) {
        return false;
    }

    $verdict = false;

    if (socket_connect($socket, $host, $port) && socket_write($socket, $path) !== false) {
        $verdict = socket_read($socket, 1024);
    }

    socket_close($socket);

    return is_string($verdict) && str_contains($verdict, 'detected');
}

function log_line(string $file, string $line): void
{
    file_put_contents($file, "$line\r\n", FILE_APPEND | LOCK_EX);
}

// Vars
$name = 'up';
$types = ['image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png', 'image/webp'];
$host = $_SERVER['HTTP_HOST'] ?? '';
$allowed_hosts = array_map(trim(...), explode(',', $config->allowed_hosts));
$files = scandir($config->upload_dir);
// An unreadable upload dir counts as full, so uploads are refused
$files_count = $files === false ? PHP_INT_MAX : count(array_diff($files, ['..', '.']));
$file = $_FILES[$name] ?? null;

if (!is_string($host) || !in_array($host, $allowed_hosts, true)) {
    respond('error', 'host check error');
} elseif ($files_count >= $config->files_limit) {
    respond('error', $lang['koniec_miejsca']);
} elseif (
    !is_array($file)
    || !is_int($file['error'] ?? null)
    || !is_string($file['tmp_name'] ?? null)
    || !is_int($file['size'] ?? null)
    || $file['error'] === UPLOAD_ERR_NO_FILE
) {
    respond('error', $lang['najpierw_wybierz_plik']);
}

$tmp = $file['tmp_name'];

// Image validation
if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $file['size'] > $config->max_filesize) {
    respond('error', $lang['plik_zbyt_duzy']);
} elseif ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
    respond('error', $lang['blad_podczas_przesylania']);
}

// Check mime type
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);

if ($mime === false || !in_array($mime, $types, true)) {
    respond('error', $lang['nieobslugiwany_typ']);
} elseif (getimagesize($tmp) === false) {
    respond('error', $lang['obiekt_nie_jest_graficzny']);
}

$recreated = recreate($tmp, $mime);

if ($recreated === null) {
    respond('error', $lang['nieprawidlowy_format']);
}

[$filetype, $image] = $recreated;

if (getimagesizefromstring($image) === false) {
    respond('error', $lang['nieprawidlowy_format']);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '-';
$ip = is_string($ip) ? $ip : '-';
$forwarded_for = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;

// For reverse proxy
if (is_string($forwarded_for) && filter_var($forwarded_for, FILTER_VALIDATE_IP) !== false) {
    $ip .= "($forwarded_for)";
}

$czas = date('Y-m-d H:i:s');

// paranoya scan (experimental)
if ($config->paranoya && function_exists('socket_create')
    && paranoya_detects_malware($config->paranoya_host, $config->paranoya_port, $tmp)) {
    log_line("$config->logs_dir/malware.log", "[$czas] $ip");
    respond('error', 'malware detected');
}

$proto = $config->ssl ? 'https' : 'http';
$url = $proto . '://' . htmlspecialchars($host) . $config->webroot . $config->upload_dir . '/';
$filename = bin2hex(random_bytes(16)) . '.' . $filetype;

if (file_put_contents("$config->upload_dir/$filename", $image, LOCK_EX) === false) {
    respond('error', $lang['blad_podczas_przesylania']);
}

// Zapis do logu
log_line("$config->logs_dir/uploads.log", "[$czas] $ip $url$filename {$file['size']}");

respond('msg', $url . $filename);
