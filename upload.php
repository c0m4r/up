<?php

use gdenhancer\GDEnhancer;
require("lang.php");

// Global variables

$name = "ICAfYf6c7fTuyIxV2PBX"; // name of input file
$max_size = "10485760"; // max filesize
$upload_dir = "i"; // upload dir
$max_files = 2000; // max number of files stored
$files_count = count(glob("$upload_dir/*.*"));

$domeny = array
(
	$_SERVER["HTTP_HOST"],
	$_SERVER["SERVER_NAME"]
);

if(!in_array($_SERVER["SERVER_NAME"], $domeny) or !in_array($_SERVER["HTTP_HOST"], $domeny))
{
	die("Watch out, we got a badass over here.");
}
else
{
	if($files_count >= $max_files)
	{
		$callback = array("error" => $lang["koniec_miejsca"]);
	}
	elseif(isset($_FILES[$name]))
	{
		// Local variables
		
		$up = new stdClass();
		
		$up->url = $_SERVER["REQUEST_SCHEME"].'://'.$_SERVER["SERVER_NAME"].'/up/'.$upload_dir.'/'; // output file url prefix
		$up->tmp = $_FILES[$name]['tmp_name'];
		$up->name = $_FILES[$name]['name']; 
		$up->type = $_FILES[$name]['type'];
		
		// Filesize
		
		$up->size = $_FILES[$name]['size']; 
		$up->size = $up->size / (1024 * 1024);
		$up->size = number_format($up->size, 2, ',', ' ');
		
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
		
		// Image validation
		
		if(isset($_FILES[$name]['error']) and $_FILES[$name]['error'] == 1)
		{
			$callback = array("error" => $lang["plik_zbyt_duzy"]);
		}
		elseif(!in_array($up->type,$types))
		{
			$callback = array("error" => $lang["nieobslugiwany_typ"]);
		}
		elseif($_FILES[$name]['size'] > $max_size)
		{
			$callback = array("error" => $lang["plik_zbyt_duzy"]);
		}
		elseif(is_uploaded_file($up->tmp) && getimagesize($up->tmp))
		{
			// Extension
			
			if($up->type == "image/gif")
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
					include_once 'gdenhancer/GDEnhancer.php';

					$image = new GDEnhancer($up->tmp);
					$save = $image->save();

					$filetype = 'ani.gif';
				}
				else
				{
					$filetype = 'gif';
					$img = imagecreatefromgif($up->tmp);
				}
			}
			elseif(in_array($up->type,array("image/jpeg","image/pjpeg")))
			{
				$filetype = 'jpg';
				$img = imagecreatefromjpeg($up->tmp);
			}
			elseif(in_array($up->type,array("image/png","image/x-png")))
			{
				$filetype = 'png';
				$img = imagecreatefrompng($up->tmp);
				imagealphablending($img, false);
				imagesavealpha($img, true);
			}
			elseif(in_array($up->type,array("image/webp")))
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
					case "gif": imagegif($img, "$upload_dir/$up->image"); break;
					case "ani.gif": file_put_contents("$upload_dir/$up->image", $save['contents']); break;
					case "jpg": imagejpeg($img, "$upload_dir/$up->image"); break;
					case "png": imagepng($img, "$upload_dir/$up->image"); break;
					case "webp": imagewebp($img, "$upload_dir/$up->image"); break;
				}
				
				if(getimagesize("$upload_dir/$up->image"))
				{
					$callback = array("msg" => $up->url . $up->image);
					
					// Zapis do logu
					
					$ip = $_SERVER["REMOTE_ADDR"];
					$czas = date("H.i d-m-Y");
					
					$fp = fopen('logs/uploads.log', 'a');
					fwrite($fp, "IP: $ip Czas: $czas Plik: ".$up->image." Target: " . $up->url . $up->image . " Rozmiar: ".$up->size." MB\r\n");
					fclose($fp);
				}
				else
				{
					unlink("$upload_dir/$up->image");
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
