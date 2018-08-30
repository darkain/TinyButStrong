<?php


////////////////////////////////////////////////////////////////////////////////
//CUSTOM EXCEPTION CLASS FOR TBX
////////////////////////////////////////////////////////////////////////////////
class tbxException extends Exception {}




////////////////////////////////////////////////////////////////////////////////
//CUSTOM EXCEPTION CLASS FOR TBX LOCATORS
////////////////////////////////////////////////////////////////////////////////
class tbxLocException extends tbxException {
	public function __construct($locator, $value, $message=false) {
		if (!empty($message)) $message .= ': ';
		parent::__construct(
			'[' . $locator->FullName . '] ' . $message .
			(is_object($value)
				? get_class($value)
				: (is_string($value)
					? $value
					: trim(print_r($value, true))
				)
			)
		);
	}
}




////////////////////////////////////////////////////////////////////////////////
// VERIFY FILE OWNERSHIP AND GROUP MATCHES
////////////////////////////////////////////////////////////////////////////////
function tbx_file_owner($path) {
	static $owner	= NULL;
	static $group	= NULL;

	if ($owner === NULL  ||  $group === NULL) {
		$paths		= get_included_files();
		$path		= $paths[count($paths)-2];
		$owner		= fileowner($path);
		$group		= filegroup($path);
	}

	if (@fileowner($path) !== $owner  ||  @filegroup($path) !== $group)  {
		throw new Exception(
			"File ownerships do not match: " . $path
		);
		return false;
	}

	return $path;
}




////////////////////////////////////////////////////////////////////////////////
// VALIDATE MAIN ALTAFORM BOOSTRAP FILE AND THIS FILE OWNERSHIP
////////////////////////////////////////////////////////////////////////////////
tbx_file_owner(NULL);
tbx_file_owner(__FILE__);