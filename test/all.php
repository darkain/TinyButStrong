<?php

if (empty($tbx)) {
	if (empty($af)) {
		date_default_timezone_set('GMT');
		$tbx = new tbx;
	} else {
		$tbx = $af;
	}
}


function tbxTest($expected) {
	global $tbx;
	if (is_string($expected)	&&	$expected === (string)$tbx) return;
	if (is_bool($expected)		&&	$expected) return;
	$trace = debug_backtrace()[0];
	echo "ERROR: FAILED!!\n\n";
	echo "FILE: $trace[file]\n";
	echo "LINE: $trace[line]\n\n";
	if (php_sapi_name() === 'cli') {
		echo "EXPECTED:\n'" . (is_bool($expected) ? 'TRUE' : $expected) . "'\n\n";
		echo "TEMPLATE:\n'" . ((string)$tbx) . "'\n\n";
	} else {
		echo "EXPECTED:\n'" . htmlspecialchars(is_bool($expected) ? 'TRUE' : $expected) . "'\n\n";
		echo "TEMPLATE:\n'" . htmlspecialchars((string)$tbx) . "'\n\n";
	}
	exit(1);
}


function tbxError($exception, $expected) {
	if ($exception->getMessage() === $expected) return;
	$trace = debug_backtrace()[0];
	echo "ERROR: FAILED!!\n\n";
	echo "FILE: $trace[file]\n";
	echo "LINE: $trace[line]\n\n";
	echo "EXPECTED:\n";
	echo "'" . $expected . "'\n\n";
	echo "ERROR:\n";
	echo "'" . $exception->getMessage() . "'\n\n";
	exit(1);
}




require('field-item.php');
require('field-array.php');
require('block-basic.php');
require('date.php');
require('format.php');
require('convert.php');
require('magnet.php');
require('pudl.php');
require('errors.php');
require('property.php');
require('plugin.php');
