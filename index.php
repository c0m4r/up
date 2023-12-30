<?php

// up - an image uploader
// https://github.com/c0m4r/up

require_once 'config.php';
require_once 'lang.php';

// Validation
if(!empty($_GET) or !empty($_POST)) {
    header('HTTP/1.0 405 Method Not Allowed'); exit("405 Method Not Allowed\n");
} elseif(!is_writable($config->upload_dir)) {
    header('HTTP/1.0 503 Service Unavailable'); die("error: upload dir is not writeable\n");
} elseif(!is_writable($config->logs_dir)) {
    header('HTTP/1.0 503 Service Unavailable'); die("error: logs dir is not writeable\n");
} elseif(!is_file('vendor/autoload.php')) {
    header('HTTP/1.0 503 Service Unavailable'); die("error: composer not found\n");
}

// Encoding
header ("Content-Type: text/html; charset=utf-8");

// CSP header
if($config->csp) {
    $nonce = bin2hex(openssl_random_pseudo_bytes(32));
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce'; base-uri 'self';");
}

// Composer
require_once 'vendor/autoload.php';

// Twig loader
$loader = new \Twig\Loader\FilesystemLoader('tpl');
$twig = new \Twig\Environment($loader);

// Count percent of used space
$files_count = count(array_diff(scandir($config->upload_dir), array('..', '.')));
$space_used_percent = round($files_count / $config->files_limit * 100);

// SRI Hash generator
function sri($file) {
    $handle = fopen($file, "r");
    $body = fread($handle, filesize($file));
    $hash = hash('sha384', $body, true);
    return "sha384-".base64_encode($hash);
}

// Loki scan
if(is_file("Loki/loki.py") and is_file("Loki/scan.sh") and function_exists("shell_exec")) {
    $loki = true;
} else {
    $loki = false;
}

// Print site
echo $twig->render('index.html', array
(
    "config" => $config,
    "lang" => $lang,
    "sri" => array
    (
        "stylecss" => sri("css/style.min.css"),
        "jqueryminjs" => sri("vendor/components/jquery/jquery.min.js"),
        "upjs" => sri("js/up.min.js")
    ),
    "space_used_percent" => $space_used_percent,
    "loki" => $loki
));

?>
