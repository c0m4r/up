<?php

// Image Uploader

header ("Content-Type: text/html; charset=utf-8");

require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('tpl');
$twig = new \Twig\Environment($loader);

$vars = array();

$vars["ip"] = $_SERVER["REMOTE_ADDR"]; // client IP
$vars["max_size"] = "10485760"; // max file size

$max_files = 2000; // max number of stored files
$files_count = count(glob("i/*.*"));

$vars["counter"]["files"] = $files_count;
$vars["counter"]["procent"] = round($files_count / $max_files * 100);

// Lang

require("lang.php");

$vars["lang"] = $lang;

echo $twig->render('index.html', $vars);

?>
