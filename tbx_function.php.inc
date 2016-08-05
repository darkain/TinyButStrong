<?php


trait tbx_function {

	function tbxfunction($value, $function) {
		$list = explode(',', strtolower($function));

		foreach ($list as $item) switch (trim($item)) {

			case 'addslashes':
				$value = addslashes($value);
			break;


			case 'bin2hex':
			case 'hex':
				$value = bin2hex($value);
			break;


			case 'chr':
				$value = chr($value);
			break;


			case 'chunk_split':
				$value = chunk_split($value);
			break;


			case 'crc32':
				$value = hash('crc32',$value);
			break;


			case 'crc32b':
				$value = hash('crc32b',$value);
			break;


			case 'hebrev':
				$value = hebrev($value);
			break;


			case 'hebrevc':
				$value = hebrevc($value);
			break;


			case 'hex2bin':
			case 'bin':
				$value = hex2bin($value);
			break;


			case 'html_entity_decode':
				$value = html_entity_decode($value);
			break;


			case 'htmlentities':
				$value = htmlentities($value);
			break;


			case 'htmlspecialchars':
			case 'html':
				$value = htmlspecialchars($value);
			break;


			case 'lcfirst':
				$value = lcfirst($value);
			break;


			case 'ltrim':
				$value = ltrim($value);
			break;


			case 'md2':
				$value = hash('md2',$value);
			break;


			case 'md4':
				$value = hash('md4',$value);
			break;


			case 'md5':
				$value = md5($value);
			break;


			case 'metaphone':
				$value = metaphone($value);
			break;


			case 'nl2br':
				$value = nl2br($value);
			break;


			case 'number_format':
				$value = number_format($value);
			break;


			case 'ord':
				$value = ord($value);
			break;


			case 'rawid':
				$value = strtolower(rawurlencode(rtrim(substr($CurrVal, 0, 20))));
			break;


			case 'rawurlencode':
				$value = rawurlencode($value);
			break;


			case 'rtrim':
				$value = rtrim($value);
			break;


			case 'sha1':
				$value = sha1($value);
			break;


			case 'sha256':
				$value = hash('sha256',$value);
			break;


			case 'sha384':
				$value = hash('sha384',$value);
			break;


			case 'sha512':
				$value = hash('sha512',$value);
			break;


			case 'soundex':
				$value = soundex($value);
			break;


			case 'strip_tags':
				$value = strip_tags($value);
			break;


			case 'stripcslashes':
				$value = stripcslashes($value);
			break;


			case 'stripslashes':
				$value = stripslashes($value);
			break;


			case 'strlen':
				$value = strlen($value);
			break;


			case 'strrev':
				$value = strrev($value);
			break;


			case 'strtolower':
			case 'lower':
				$value = strtolower($value);
			break;


			case 'strtoupper':
			case 'upper':
				$value = strtoupper($value);
			break;


			case 'title':
				$value = str_replace(
					[' A ', ' An ', ' At ', ' In ', ' With ', ' The ', ' And ',
					' But ', ' Or ', ' Nor ', ' For ', ' So ', ' Yet ', ' To '],
					[' a ', ' an ', ' at ', ' in ', ' with ', ' the ', ' and ',
					' but ', ' or ', ' nor ', ' for ', ' so ', ' yet ', ' to '],
					ucwords(strtolower($value))
				);
			break;


			case 'trim':
				$value = trim($value);
			break;


			case 'ucfirst':
				$value = ucfirst($value);
			break;


			case 'ucwords':
				$value = ucwords($value);
			break;


			case 'unhex':
				$value = hex2bin($value);
			break;


			case 'urlencode':
			case 'url':
				$value = urlencode($value);
			break;


			case 'urlid':
				$value = strtolower(urlencode(rtrim(substr($CurrVal, 0, 20))));
			break;


			case 'uudecode':
				$value = convert_uudecode($value);
			break;


			case 'uuencode':
				$value = convert_uuencode($value);
			break;


			case 'wordwrap':
				$value = wordwrap($value);
			break;


			default: $this->_customFormat($value, $function);
		}

		return $value;
	}
}
