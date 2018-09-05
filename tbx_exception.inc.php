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
