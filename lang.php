<?php

print_r($_SERVER['HTTP_ACCEPT_LANGUAGE']);

if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2); echo $lang;
} else {
    $lang = 'en';
}

switch($lang)
{
    case 'pl':
	require("lang/pl.php"); break;
    case 'uk':
        require("lang/ua.php"); break;
    default:
        require("lang/en.php"); break;
}

?>
