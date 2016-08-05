<?php


trait tbx_safe {

	function _safe(&$part, $safe) {
		$part->ConvStr				= false;

		switch (trim(strtolower($safe))) {
			case 'js':
				$this->_safe_default($part);
				$part->ConvJS		= true;
			break;


			case 'json':
				$this->_safe_default($part);
				$part->ConvJson		= true;
				$part->ConvProtect	= false;
			break;


			case 'url':
				$this->_safe_default($part);
				$part->ConvUrl		= 1;
				$part->ConvProtect	= false;
			break;


			case 'urlid':
				$this->_safe_default($part);
				$part->ConvUrl		= 2;
				$part->ConvProtect	= false;
			break;


			case 'raw':
				$this->_safe_default($part);
				$part->ConvUrl		= 3;
				$part->ConvProtect	= false;
			break;


			case 'rawid':
				$this->_safe_default($part);
				$part->ConvUrl		= 4;
				$part->ConvProtect	= false;
			break;


			case 'hex':
				$part->ConvHex		= true;
			break;


			case 'phone':
				$part->ConvPhone	= true;
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
		$value = htmlspecialchars($value, ENT_QUOTES);
		if ($break) $value			= nl2br($value);
	}

}
