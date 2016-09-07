<?php


$tbx->loadString('a[x.item]b')
	->field('x', ['item' => 1]);

tbxTest('a1b');




$tbx->loadString('a[x.item]b')
	->field('x', ['item' => 0]);

tbxTest('a0b');




$tbx->loadString('a[x.item]b')
	->field('x', ['item' => -1]);

tbxTest('a-1b');




$tbx->loadString('a[x.item]b')
	->field('x', ['item' => PHP_INT_MAX]);

tbxTest('a9223372036854775807b');




$tbx->loadString('a[x.item]b')
	->field('x', ['item' => PHP_INT_MIN]);

tbxTest('a-9223372036854775808b');




$tbx->loadString('a[x.item]b')
	->field('x', ['item' => 1.2]);

tbxTest('a1.2b');




$tbx->loadString('a[x.item]b')
	->field('x', ['item' => -1.2]);

tbxTest('a-1.2b');




$tbx->loadString('a[x.item]b')
	->field('x', ['item' => '']);

tbxTest('ab');




$tbx->loadString('a[x.item]b')
	->field('x', ['item' => '1']);

tbxTest('a1b');




$tbx->loadString('a[x.item]b')
	->field('x', ['item' => '1234567890']);

tbxTest('a1234567890b');




$tbx->loadString('a[x.item]b')
	->field('x', ['item' => true]);

tbxTest('a1b');




$tbx->loadString('a[x.item]b')
	->field('x', ['item' => false]);

tbxTest('ab');




$tbx->loadString('a[x.item;implode=,]b')
	->field('x', ['item' => [1]]);

tbxTest('a1b');




$tbx->loadString('a[x.item;implode=,]b')
	->field('x', ['item' => [0]]);

tbxTest('a0b');




$tbx->loadString('a[x.item;implode=]b')
	->field('x', ['item' => [0,1,2,3,4,5]]);

tbxTest('a012345b');




$tbx->loadString('a[x.item;implode=,]b')
	->field('x', ['item' => [0,1,2,3,4,5]]);

tbxTest('a0,1,2,3,4,5b');




$tbx->loadString('a[x.item;implode=,]b')
	->field('x', ['item' => [0,1,2,3,4,5]]);

tbxTest('a0,1,2,3,4,5b');




$tbx->loadString("a[x.item;implode=';']b")
	->field('x', ['item' => [0,1,2,3,4,5]]);

tbxTest('a0;1;2;3;4;5b');
