<?php


trait tbx_safe {

	function _safe(&$part, $safe) {
		$this->_safe_default($part);

		switch (trim(strtolower($safe))) {
			case 'javascript':
				$part->ConvProtect	= false;
			break;


			case 'js':
				$part->ConvJS		= true;
			break;


			case 'json':
				$part->ConvJson		= true;
				$part->ConvProtect	= false;
			break;


			//HANDLED BY DEFAULT OPTION IN _safe_default
			case 'no':
			case 'none':
			break;


			//HANDLED BY 'DEFAULT' CASE BELOW
			//case 'yes':
			//	$part->ConvStr		= true;
			//break;


			case 'nobr':
			case 'pre':
			case 'css':
			case 'textarea':
				$part->break		= false;
			//Intentionally falling through case


			default:
				$part->ConvStr		= true;
		}
	}




	function _safe_default(&$part) {
		if ($part->mode === TBX_CONVERT_SPECIAL) return;
		$part->mode					= TBX_CONVERT_SPECIAL;
		$part->ConvStr				= false;
		$part->ConvJS				= false;
		$part->ConvJson				= false;
	}




	// Escape HTML special characters
	function _htmlsafe(&$value, $break=true) {
		$value = htmlspecialchars($value, TBX_SPECIAL_CHARS);
		if ($break) $value			= nl2br($value);
	}




	// Convert a value to a string
	static function _string($value) {
		if ($value instanceof DateTime) return $value->format('c');
		return @(string)$value;
	}





	// Convert a value to a string and trim it
	static function _trim($value) {
		return trim(self::_string($value));
	}




	// PROTECT TBX LOCATORS
	function _protect($string) {
		return str_replace(
			['[',		']'],
			['&#91;',	'&#93;'],
			$string
		);
	}

}
