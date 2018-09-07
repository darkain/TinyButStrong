<?php

//CONVERSION MODES
define('TBX_CONVERT_UNKNOWN',	 0);
define('TBX_CONVERT_DEFAULT',	 1);
define('TBX_CONVERT_SPECIAL',	 2);
define('TBX_CONVERT_DATE',		 3);
define('TBX_CONVERT_FORMAT',	 4);
define('TBX_CONVERT_FUNCTION',	 5);
define('TBX_CONVERT_SELECTED',	 6);
define('TBX_CONVERT_CHECKED',	 7);
define('TBX_CONVERT_DISABLED',	 8);
define('TBX_CONVERT_AUTOFOCUS',	 9);
define('TBX_CONVERT_EDITABLE',	10);
define('TBX_CONVERT_HIDDEN',	11);
define('TBX_CONVERT_REVERSED',	12);
define('TBX_CONVERT_REQUIRED',	13);
define('TBX_CONVERT_SCOPED',	14);


//DATA SOURCE TYPES
define('TBX_DS_ARRAY',			0);
define('TBX_DS_NUMBER',			1);
define('TBX_DS_TEXT',			2);
define('TBX_DS_PUDL',			3);
define('TBX_DS_ITERATOR',		4);


//SUBTYPES
define('TBX_DS_SUB0',			0);
define('TBX_DS_SUB1',			1);
define('TBX_DS_SUB2',			2);
define('TBX_DS_SUB3',			3);


//MAGNETS
define('TBX_MAGNET_NONE',		0);
define('TBX_MAGNET_ZERO',		1);
define('TBX_MAGNET_TAG',		2);
define('TBX_MAGNET_PLUS',		3);
define('TBX_MAGNET_SUFFIX',		4);
define('TBX_MAGNET_PREFIX',		5);
define('TBX_MAGNET_ATTADD',		6);
define('TBX_MAGNET_ATTR',		7);
define('TBX_MAGNET_IFEMPTY',	8);
define('TBX_MAGNET_NBSP',		9);


//CHARACTERS FOR (X)HTML TAGS
//Reference: https://infra.spec.whatwg.org/#ascii-whitespace
$TBX_WHITE_SPACE				=	["\t", "\n", "\f", "\r", ' '];
$TBX_TAG_NAME_END				=	["\t", "\n", "\f", "\r", ' ', '>'];


//HTML SPECIAL CHARS CONVERSION
define('TBX_SPECIAL_CHARS',		ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE);


//SUPPORT FOR ARRAYACCESS INTERFACE
function tbx_array($item) {
	if (is_array($item)) return true;
	if ($item instanceof pudlCollection) return false;
	return ($item instanceof ArrayAccess);
}


//SUPPORT FOR NAN AND INF, TREAT BOTH AS "EMPTY" VALUES
function tbx_empty($item) {
	if (is_float($item)) {
		if (is_nan($item)) return true;
		if (is_infinite($item)) return true;
	}
	if (is_string($item)) {
		if (!strcasecmp($item, 'false')) return true;
		if (!strcasecmp($item, 'null')) return true;
	}
	return empty($item);
}


//PHP 5.x COMPATIBILITY
if (!defined('PHP_INT_MIN')) {
	define('PHP_INT_MIN', ~PHP_INT_MAX);
}
