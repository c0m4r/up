<?php

// Lang

$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

if(isset($_GET["lang"]))
{
	$lang = "en";
}

switch($lang)
{
	case 'pl':
	
		require("lang/pl.php"); break;
		
	default:
		
		require("lang/en.php"); break;
}

// qńec
