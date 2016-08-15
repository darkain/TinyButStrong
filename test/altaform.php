<?php

//NOTE:	THIS TEST IS ONLY AVAILABLE WHEN CALLED THROUGH ALTAFORM ITSELF
//		FOR MORE INFO, SEE: https://github.com/darkain/altaform-core

if (!class_exists('af_object_array')) return;


//INITIALIZE OBJECT
$tbxtest = new af_object_array;

$tbxtest->_clone([
	'a'	=> 1,
	'b'	=> 2.5,
	'c'	=> INF,
	'd'	=> -INF,
	'e'	=> NAN,
	'f'	=> 'text',
	'g'	=> NULL,
	'h'	=> true,
	'i'	=> false,
	'j'	=> PHP_INT_MAX,
	'k'	=> PHP_INT_MIN,
]);




$tbx->loadString('[x.a]')->field('x', $tbxtest);
tbxTest('1');




$tbx->loadString('[x.b]')->field('x', $tbxtest);
tbxTest('2.5');




$tbx->loadString('[x.c]')->field('x', $tbxtest);
tbxTest('INF');




$tbx->loadString('[x.d]')->field('x', $tbxtest);
tbxTest('-INF');




$tbx->loadString('[x.e]')->field('x', $tbxtest);
tbxTest('NAN');




$tbx->loadString('[x.f]')->field('x', $tbxtest);
tbxTest('text');




$tbx->loadString('[x.g]')->field('x', $tbxtest);
tbxTest('');




$tbx->loadString('[x.h]')->field('x', $tbxtest);
tbxTest('1');




$tbx->loadString('[x.i]')->field('x', $tbxtest);
tbxTest('');




$tbx->loadString('[x.j]')->field('x', $tbxtest);
tbxTest('9223372036854775807');




$tbx->loadString('[x.k]')->field('x', $tbxtest);
tbxTest('-9223372036854775808');
