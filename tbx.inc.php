<?php
/*******************************************************************************
*	TinyButStrong - Template Engine for Pro and Beginners                      *
*	------------------------                                                   *
*	Version		: 3.10.0-beta-2015-10-01 for PHP 5                             *
*	Date		: 2015-10-01                                                   *
*	Web site	: http://www.tinybutstrong.com                                 *
*	GitHub		: https://github.com/Skrol29/tinybutstrong                     *
*	Author		: http://www.tinybutstrong.com/onlyyou.html                    *
********************************************************************************
*	This library is free software.                                             *
*	You can redistribute and modify it even for commercial usage,              *
*	but you must accept and respect the LPGL License version 3.                *
********************************************************************************
*	TinyButXtreme - Heavily modified fork of TinyButStrong                     *
*	This TBX library is --NOT-- backwards compatible with the original TBS     *
*	------------------------                                                   *
*	Version		: 10.0.7 for PHP 5.4+ and HHVM 3.6+                            *
*	Date		: 2017-02-23                                                   *
*	GitHub		: https://github.com/darkain/TinyButXtreme                     *
*	Author		: Darkain Multimedia                                           *
\******************************************************************************/


if (!function_exists('is_owner')) {
	/** @suppress PhanRedefineFunction */
	function is_owner($path) { return $path; }
}


require_once(is_owner(__DIR__.'/tbx_exception.inc.php'));



// Check PHP version
if (!version_compare(PHP_VERSION, '5.4.0', '>=')) {
	throw new tbxException(
		'PHP 5.4+ or HHVM required, but version '.PHP_VERSION.' detected'
	);
}



require_once(is_owner(__DIR__.'/tbx_class.inc.php'));
