<?php

try {
	$tbx->loadString('[x.item]')
		->field('x', false);
} catch (tbxException $error) {
	tbxError($error, '[x.item] invalid data type: boolean');
}




try {
	$tbx->loadString('[x.item]')
		->field('x', 1);
} catch (tbxException $error) {
	tbxError($error, '[x.item] invalid data type: integer');
}




try {
	$tbx->loadString('[x.item]')
		->field('x', 2.3);
} catch (tbxException $error) {
	tbxError($error, '[x.item] invalid data type: double');
}




try {
	$tbx->loadString('[x.item]')
		->field('x', '1');
} catch (tbxException $error) {
	tbxError($error, '[x.item] invalid data type: string');
}




try {
	$tbx->loadString('[x.item]')
		->field('x', NULL);
} catch (tbxException $error) {
	tbxError($error, '[x.item] invalid data type: NULL');
}




try {
	$tbx->loadString('[x.item]')
		->field('x', []);
} catch (tbxException $error) {
	tbxError($error, "[x.item] key not found: Array\n(\n)");
}




try {
	$tbx->loadString('[x.item]')
		->field('x', ['item' => [0]]);
} catch (tbxException $error) {
	tbxError($error, "[x.item] no processing instructions: Array\n(\n    [0] => 0\n)");
}




try {
	$tbx->loadString('<elem>[x.item;block=elem]</elem>')
		->block('x', [[]]);
} catch (tbxException $error) {
	tbxError($error, "[x.item] key not found: Array\n(\n)");
}




try {
	$tbx->loadString('<elem>[x.item;block=broken]</elem>')
		->block('x', []);
} catch (tbxException $error) {
	tbxError($error, '[x.item] <broken> not found');
}




try {
	$tbx->loadString('<elem>[x.item;block=elem]')
		->block('x', []);
} catch (tbxException $error) {
	tbxError($error, '[x.item] <elem> not found');
}




try {
	$tbx->loadString('[onload;file=missing.tpl]');
} catch (tbxException $error) {
	tbxError($error, 'Unable to load template file: missing.tpl');
}
