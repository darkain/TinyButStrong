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



//PREP THE DIRECTORY
$parent	= dirname(dirname(__DIR__));
$dir	= substr(__DIR__, strlen($parent)-strlen(__DIR__)+1);
$list	= scandir(__DIR__);
shuffle($list);



//RUN ALL UNIT TESTS
foreach ($list as $item) {
	if (strtolower(substr($item, -8)) !== '.inc.php') continue;
	echo "\033[97m" . "Testing:\t";
	echo "\033[36m" . $dir . '/';
	echo "\033[96m" . $item . "\033[0m\n";
	require_once(__DIR__ . '/' . $item);
}
