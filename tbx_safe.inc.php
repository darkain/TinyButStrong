<?php


trait tbx_safe {

	function _safe(&$part, $safe) {
		$part->ConvStr				= false;

		switch (trim(strtolower($safe))) {
			case 'javascript':
				$this->_safe_default($part);
				$part->ConvProtect	= false;
			break;


			case 'js':
				$this->_safe_default($part);
				$part->ConvJS		= true;
			break;


			case 'json':
				$this->_safe_default($part);
				$part->ConvJson		= true;
				$part->ConvProtect	= false;
			break;


			//THIS IS DEPRECATED
			case 'url':
				$this->_safe_default($part);
				$part->ConvUrl		= 1;
				$part->ConvProtect	= false;
			break;


			//THIS IS DEPRECATED
			case 'urlid':
				$this->_safe_default($part);
				$part->ConvUrl		= 2;
				$part->ConvProtect	= false;
			break;


			//THIS IS DEPRECATED
			case 'raw':
				$this->_safe_default($part);
				$part->ConvUrl		= 3;
				$part->ConvProtect	= false;
			break;


			//THIS IS DEPRECATED
			case 'rawid':
				$this->_safe_default($part);
				$part->ConvUrl		= 4;
				$part->ConvProtect	= false;
			break;


			//THIS IS DEPRECATED
			case 'hex':
				$part->ConvHex		= true;
			break;


			//HANDLED BY DEFAULT OPTION ABOVE
			case 'no':
			case 'none':
			//	$part->ConvStr		= false;
			break;


			//HANDLED BY DEFAULT CASE BELOW
			//case 'yes':
			//	$part->ConvStr		= true;
			//break;


			case 'nobr':
			case 'pre':
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
		$part->ConvJS				= false;
		$part->ConvJson				= false;
		$part->ConvUrl				= false;
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
