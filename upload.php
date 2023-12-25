<?php

// up - an image uploader
// https://github.com/c0m4r/up

require_once 'vendor/autoload.php';
use Imagecraft\ImageBuilder;
require("config.php");
require("lang.php");

// Global variables

$name 		= "up";
$files_count 	= count(glob("$config->upload_dir/*.*"));
$domeny 	= explode(",",$config->allowed_hosts);

if(!in_array($_SERVER["HTTP_HOST"], $domeny))
{
	echo json_encode(array("error" => "host check error"));
}
elseif(!$_SERVER["REQUEST_URI"] == '/' and !preg_match('/^[A-Za-z0-9_\/$/',$_SERVER["REQUEST_URI"]))
{
	echo json_encode(array("error" => "bad request"));
}
else
{
	if($files_count >= $config->files_limit)
	{
		$callback = array("error" => $lang["koniec_miejsca"]);
	}
	elseif(isset($_FILES[$name]))
	{
		// Local variables
		
		$up = new stdClass();

		// Reading protocol

		if(isset($_SERVER["HTTP_X_FORWARDED_PROTO"]))
		{
			$up->proto = $_SERVER["HTTP_X_FORWARDED_PROTO"];
		}
		else
		{
			$up->proto = $_SERVER["REQUEST_SCHEME"];
		}

		// Validate protocol
		
		if(!in_array($up->proto, array("http", "https")))
		{
			$up->proto = "https";
		}

		// Validate HTTP_HOST and REQUEST_URI

		$up->host = htmlspecialchars($_SERVER["HTTP_HOST"]);
		$up->uri = htmlspecialchars($_SERVER["REQUEST_URI"]);

		// Combine the URL
		
		$up->url = $up->proto.'://'.$up->host.preg_replace("/upload\.php/", "", $up->uri).$config->upload_dir.'/'; // output file url prefix

		// Temporary file location
		
		$up->tmp = $_FILES[$name]['tmp_name'];
		
		// Filesize
		
		$up->size = $_FILES[$name]['size'];
		
		// Supported file types
		
		$types = array
		(
			"image/gif",
			"image/jpeg",
			"image/pjpeg",
			"image/x-png",
			"image/png",
			"image/webp"
		);

		// Check mime type

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$up->type = $finfo->file($up->tmp);
		
		// Image validation
		
		if(isset($_FILES[$name]['error']) and $_FILES[$name]['error'] == 1)
		{
			$callback = array("error" => $lang["plik_zbyt_duzy"]);
		}
		elseif(!in_array($up->type,$types))
		{
			$callback = array("error" => $lang["nieobslugiwany_typ"]);
		}
		elseif($_FILES[$name]['size'] > $config->max_filesize)
		{
			$callback = array("error" => $lang["plik_zbyt_duzy"]);
		}
		elseif(is_uploaded_file($up->tmp) && getimagesize($up->tmp))
		{
			// Extension
			
			if($up->type == "image/gif" and exif_imagetype($up->tmp) == IMAGETYPE_GIF)
			{
				$filecontents = file_get_contents($up->tmp);

				$str_loc=0;
				$count=0;

				while ($count < 2) # There is no point in continuing after we find a 2nd frame
				{
					$where1=strpos($filecontents,"\x00\x21\xF9\x04",$str_loc);

					if ($where1 === FALSE)
					{
						break;
					}
					else
					{
						$str_loc=$where1+1;
						$where2=strpos($filecontents,"\x00\x2C",$str_loc);

						if ($where2 === FALSE)
						{
							break;
						}
						else
						{
							if ($where1+8 == $where2)
							{
								$count++;
							}
							
							$str_loc=$where2+1;
						}
					}
				}

				if ($count > 1)
				{
					$options = ['engine' => 'php_gd', 'locale' => 'en'];
					$builder = new ImageBuilder($options);

					$image = $builder
					    ->addBackgroundLayer()
					        ->contents($filecontents)
					        ->done()
					    ->save()
					;

					$filetype = 'ani.gif';
				}
				else
				{
					$filetype = 'gif';
					$img = imagecreatefromgif($up->tmp);
				}
			}
			elseif(in_array($up->type,array("image/jpeg","image/pjpeg")) and exif_imagetype($up->tmp) == IMAGETYPE_JPEG)
			{
				$filetype = 'jpg';
				$img = imagecreatefromjpeg($up->tmp);
			}
			elseif(in_array($up->type,array("image/png","image/x-png")) and exif_imagetype($up->tmp) == IMAGETYPE_PNG)
			{
				$filetype = 'png';
				$img = imagecreatefrompng($up->tmp);
				imagealphablending($img, false);
				imagesavealpha($img, true);
			}
			elseif(in_array($up->type,array("image/webp")) and exif_imagetype($up->tmp) == IMAGETYPE_WEBP)
			{
				$filetype = 'webp';
				$img = imagecreatefromwebp($up->tmp);
			}
			else
			{
				$filetype = false;
			}
			
			// Filetype validation
			
			if($filetype)
			{
				$bytes = openssl_random_pseudo_bytes(16, $strong);
				$up->image = bin2hex($bytes) . "." . $filetype;
				
				switch($filetype)
				{
					case "gif": imagegif($img, "$config->upload_dir/$up->image"); break;
					case "ani.gif": file_put_contents("$config->upload_dir/$up->image", $image->getContents()); break;
					case "jpg": imagejpeg($img, "$config->upload_dir/$up->image"); break;
					case "png": imagepng($img, "$config->upload_dir/$up->image"); break;
					case "webp": imagewebp($img, "$config->upload_dir/$up->image"); break;
				}
				
				if(getimagesize("$config->upload_dir/$up->image"))
				{
					$callback = array("msg" => $up->url . $up->image);
					
					// Zapis do logu
					
					$ip = $_SERVER["REMOTE_ADDR"];

					// For reverse proxy

					if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
					{
						if(filter_var($_SERVER["HTTP_X_FORWARDED_FOR"], FILTER_VALIDATE_IP))
						{
							$ip = $ip . "(" .$_SERVER["HTTP_X_FORWARDED_FOR"]. ")";
						}
					}
					
					$czas = date("Y-m-d H:i:s");
					
					$fp = fopen('logs/uploads.log', 'a');
     					fwrite($fp, "[$czas] $ip ".$up->url.$up->image." ".$up->size."\r\n");
					fclose($fp);
				}
				else
				{
					unlink("$config->upload_dir/$up->image");
					$callback = array("error" => $lang["nieprawidlowy_format"]);
				}
			}
			else
			{
				$callback = array("error" => $lang["nieobslugiwany_typ"]);
			}
		}
		else
		{
			$callback = array("error" => $lang["obiekt_nie_jest_graficzny"]);
		}
	}
	else
	{
		$callback = array("error" => $lang["najpierw_wybierz_plik"]);
	}

	echo json_encode($callback);
}

?>
