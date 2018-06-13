<?php


$tbx->loadString('-[x]+')
	->field('x', false);

tbxTest('-+');




$tbx->loadString('+[x]-')
	->field('x', null);

tbxTest('+-');




$tbx->loadString('/[x]*')
	->field('x', '');

tbxTest('/*');




$tbx->loadString('[x]')
	->field('x', 1);

tbxTest('1');




$tbx->loadString('a[x]b')
	->field('x', 1);

tbxTest('a1b');




$tbx->loadString('a[x]b')
	->field('x', 0);

tbxTest('a0b');




$tbx->loadString('a[x]b')
	->field('x', -1);

tbxTest('a-1b');




$tbx->loadString('a[x]b')
	->field('x', PHP_INT_MAX);

tbxTest('a'.PHP_INT_MAX.'b');




$tbx->loadString('a[x]b')
	->field('x', PHP_INT_MIN);

tbxTest('a'.PHP_INT_MIN.'b');




$tbx->loadString('a[x]b')
	->field('x', 1.2);

tbxTest('a1.2b');




$tbx->loadString('a[x]b')
	->field('x', -1.2);

tbxTest('a-1.2b');




$tbx->loadString('a[x]b')
	->field('x', '');

tbxTest('ab');




$tbx->loadString('a[x]b')
	->field('x', '1');

tbxTest('a1b');




$tbx->loadString('a[x]b')
	->field('x', '1234567890');

tbxTest('a1234567890b');




$tbx->loadString('a[x]b')
	->field('x', true);

tbxTest('a1b');




$tbx->loadString('a[x]b')
	->field('x', false);

tbxTest('ab');
