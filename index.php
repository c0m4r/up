<?php

// Image Uploader

require("config.php");
require("lang.php");

if(!is_writable($config["upload_dir"])) die("up error: upload dir is not writeable, check permissions");
if(!is_writable($config["logs_dir"])) die("up error: logs dir is not writeable, check permissions");

header ("Content-Type: text/html; charset=utf-8");

if(is_file('vendor/autoload.php'))
    require_once 'vendor/autoload.php';
else
    die('error: composer is not installed');

$loader = new \Twig\Loader\FilesystemLoader('tpl');
$twig = new \Twig\Environment($loader);

$vars = array();

$vars["ip"] = $_SERVER["REMOTE_ADDR"]; // client IP
$vars["max_size"] = $config["max_filesize"]; // max file size

$max_files = $config["files_limit"]; // max number of stored files
$files_count = count(glob($config["upload_dir"]."/*.*"));

$vars["counter"]["files"] = $files_count;
$vars["counter"]["procent"] = round($files_count / $max_files * 100);

$vars["lang"] = $lang;
$vars["config"] = $config;

// SRI Hash generator
function sri($file) {
	$f = fopen($file, "r");
	$body = fread($f, filesize($file));
	$hash = hash('sha384', $body, true);
	return "sha384-".base64_encode($hash);
}

$vars["sri"]["stylecss"] = sri("css/style.css");
$vars["sri"]["jqueryminjs"] = sri("vendor/components/jquery/jquery.min.js");
$vars["sri"]["upjs"] = sri("js/up.js");

echo $twig->render('index.html', $vars);

?>
