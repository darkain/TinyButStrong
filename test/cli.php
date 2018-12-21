<?php


//ALL WARNINGS AS EXCEPTIONS
error_reporting(E_ALL);
set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});




//TEST ALL THE THINGS
chdir(__DIR__);
require_once(__DIR__.'/../tbx.inc.php');

require(__DIR__.'/all.php');

echo "ALL GOOD!!\n";
