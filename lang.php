<?php

if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
} else {
    $lang = 'en';
}

switch($lang)
{
    case 'pl':
	require("lang/pl.php"); break;
    case 'ua':
        require("lang/ua.php"); break;
    default:
        require("lang/en.php"); break;
}

?>
