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

require_once 'vendor/autoload.php';
use Imagecraft\ImageBuilder;
require("config.php");
require("lang.php");

// Vars
$name = "up";
$files_count = count(array_diff(scandir($config->upload_dir), array('..', '.')));
$domeny = explode(",",$config->allowed_hosts);
$types = array("image/gif", "image/jpeg", "image/pjpeg", "image/x-png", "image/png", "image/webp");

$up = new stdClass();

$up->host = $_SERVER["HTTP_HOST"];

function callback($key, $value) {
    echo json_encode(array($key => $value));
}

if(!in_array($up->host, $domeny)) {
    callback("error", "host check error");
} elseif($files_count >= $config->files_limit) {
    callback("error", $lang["koniec_miejsca"]);
} elseif(!isset($_FILES[$name])) {
    callback("error", $lang["najpierw_wybierz_plik"]);
} else {
    // Reading protocol
    if($config->ssl) {
        $up->proto = "https";
    } else {
        $up->proto = "http";
    }

    // Strip HTTP_HOST
    $up->host = htmlspecialchars($up->host);

    // Combine the URL
    $up->url = $up->proto.'://'.$up->host.$config->webroot.$config->upload_dir.'/';

    // Temporary file location
    $up->tmp = $_FILES[$name]['tmp_name'];

    // Filesize
    $up->size = $_FILES[$name]['size'];

    // Check mime type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $up->type = $finfo->file($up->tmp);

    // Image validation
    if(isset($_FILES[$name]['error']) and $_FILES[$name]['error'] == 1) {
        callback("error", $lang["plik_zbyt_duzy"]);
    } elseif(!in_array($up->type,$types)) {
        callback("error", $lang["nieobslugiwany_typ"]);
    } elseif($_FILES[$name]['size'] > $config->max_filesize) {
        callback("error", $lang["plik_zbyt_duzy"]);
    } elseif(!is_uploaded_file($up->tmp) or !getimagesize($up->tmp)) {
        callback("error", $lang["obiekt_nie_jest_graficzny"]);
    } else {
        // Extension check
        if($up->type == "image/gif" and exif_imagetype($up->tmp) == IMAGETYPE_GIF) {
            // Detect animation
            $filecontents = file_get_contents($up->tmp);

            $str_loc=0;
            $count=0;

            while ($count < 2) {
                $where1=strpos($filecontents,"\x00\x21\xF9\x04",$str_loc);

                if ($where1 === FALSE) {
                    break;
                } else {
                    $str_loc=$where1+1;
                    $where2=strpos($filecontents,"\x00\x2C",$str_loc);

                    if ($where2 === FALSE) {
                        break;
                    } else {
                        if ($where1+8 == $where2) {
                            $count++;
                        }

                        $str_loc=$where2+1;
                    }
                }
            }

            if ($count > 1) {
                $options = ['engine' => 'php_gd', 'locale' => 'en'];
                $builder = new ImageBuilder($options);

                $image = $builder
                    ->addBackgroundLayer()
                        ->contents($filecontents)
                        ->done()
                    ->save()
                ;

                $filetype = 'ani.gif';
            } else {
                $filetype = 'gif';
                $img = imagecreatefromgif($up->tmp);
            }
        } elseif(in_array($up->type,array("image/jpeg","image/pjpeg")) and exif_imagetype($up->tmp) == IMAGETYPE_JPEG) {
            $filetype = 'jpg';
            $img = imagecreatefromjpeg($up->tmp);
        } elseif(in_array($up->type,array("image/png","image/x-png")) and exif_imagetype($up->tmp) == IMAGETYPE_PNG) {
            $filetype = 'png';
            $img = imagecreatefrompng($up->tmp);
            imagealphablending($img, false);
            imagesavealpha($img, true);
        } elseif(in_array($up->type,array("image/webp")) and exif_imagetype($up->tmp) == IMAGETYPE_WEBP) {
            $filetype = 'webp';
            $img = imagecreatefromwebp($up->tmp);
        } else {
            $filetype = false;
        }

        // Filetype validation
        if(!$filetype) {
            callback("error", $lang["nieobslugiwany_typ"]);
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];

            // For reverse proxy
            if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                if(filter_var($_SERVER["HTTP_X_FORWARDED_FOR"], FILTER_VALIDATE_IP)) {
                    $ip = $ip . "(" .$_SERVER["HTTP_X_FORWARDED_FOR"]. ")";
                }
            }

            $czas = date("Y-m-d H:i:s");

            // paranoya scan (experimental)
            if(function_exists("socket_create") and $config->paranoya) {
                $socket = socket_create(AF_INET, SOCK_STREAM, 0);
                $result = socket_connect($socket, $config->paranoya_host, $config->paranoya_port);

                if($result) {
                    $message = $up->tmp;
                    socket_write($socket, $message, strlen($message));
                    $paranoya = socket_read ($socket, 1024);
                } else {
                    $paranoya = false;
                }

                socket_close($socket);

                if($paranoya and preg_match("/detected/", $paranoya)) {
                    $fp = fopen('logs/malware.log', 'a');
                    fwrite($fp, "[$czas] $ip\r\n");
                    fclose($fp);

                    callback("error", "malware detected");
                    die();
                }
            }

            $bytes = openssl_random_pseudo_bytes(16, $strong);
            $up->image = bin2hex($bytes) . "." . $filetype;

            switch($filetype) {
                case "gif": imagegif($img, "$config->upload_dir/$up->image"); break;
                case "ani.gif": file_put_contents("$config->upload_dir/$up->image", $image->getContents()); break;
                case "jpg": imagejpeg($img, "$config->upload_dir/$up->image"); break;
                case "png": imagepng($img, "$config->upload_dir/$up->image"); break;
                case "webp": imagewebp($img, "$config->upload_dir/$up->image"); break;
            }

            if(getimagesize("$config->upload_dir/$up->image")) {
                callback("msg", $up->url.$up->image);

                // Zapis do logu
                $fp = fopen('logs/uploads.log', 'a');
                fwrite($fp, "[$czas] $ip ".$up->url.$up->image." ".$up->size."\r\n");
                fclose($fp);
            } else {
                unlink("$config->upload_dir/$up->image");
                callback("error", $lang["nieprawidlowy_format"]);
            }
        }
    }
}

?>
