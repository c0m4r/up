<?php

if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
} else {
    $lang = 'en';
}

switch($lang)
{
    case 'fr':
	require("lang/fr.php"); break;
    case 'ja':
        require("lang/ja.php"); break;
    case 'ko':
        require("lang/ko.php"); break;
    case 'pl':
        require("lang/pl.php"); break;
    case 'uk':
        require("lang/uk.php"); break;
    default:
        require("lang/en.php"); break;
}

?>
