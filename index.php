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

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';

/** @var UpConfig $config */
/** @var array<string, string> $lang */

// Validation
if ($_GET !== [] || $_POST !== []) {
    http_response_code(405);
    exit("405 Method Not Allowed\n");
} elseif (!is_writable($config->upload_dir)) {
    http_response_code(503);
    exit("error: upload dir is not writeable\n");
} elseif (!is_writable($config->logs_dir)) {
    http_response_code(503);
    exit("error: logs dir is not writeable\n");
} elseif (!is_file(__DIR__ . '/vendor/autoload.php')) {
    http_response_code(503);
    exit("error: composer not found\n");
}

// Encoding
header('Content-Type: text/html; charset=utf-8');

// CSP header
if ($config->csp) {
    $nonce = bin2hex(random_bytes(32));
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce'; base-uri 'self';");
}

// Composer
require_once __DIR__ . '/vendor/autoload.php';

// Twig loader
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/tpl');
$twig = new \Twig\Environment($loader);

// Count percent of used space
$files = scandir($config->upload_dir);
$files_count = $files === false ? 0 : count(array_diff($files, ['..', '.']));
$space_used_percent = round($files_count / $config->files_limit * 100);

// SRI Hash generator
function sri(string $file): string
{
    $hash = hash_file('sha384', $file, true);

    if ($hash === false) {
        throw new RuntimeException("Unable to read $file");
    }

    return 'sha384-' . base64_encode($hash);
}

// paranoya scan
$paranoya = $config->paranoya && function_exists('socket_create');

// Print site
echo $twig->render('index.html', [
    'config' => $config,
    'lang' => $lang,
    'sri' => [
        'stylecss' => sri(__DIR__ . '/css/style.min.css'),
        'jqueryminjs' => sri(__DIR__ . '/vendor/components/jquery/jquery.min.js'),
        'upjs' => sri(__DIR__ . '/js/up.min.js'),
    ],
    'space_used_percent' => $space_used_percent,
    'paranoya' => $paranoya,
]);
