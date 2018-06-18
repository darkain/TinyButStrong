<?php


$tbx->loadString('a[x.item;placeholder]b')
	->field('x', ['item' => 0]);

tbxTest('ab');




$tbx->loadString('a[x.item;placeholder]b')
	->field('x', ['item' => 1]);

tbxTest('a1b');




$tbx->loadString('a[x.item;placeholder=0]b')
	->field('x', ['item' => 1]);

tbxTest('a1b');




$tbx->loadString('a[x.item;placeholder=1]b')
	->field('x', ['item' => 1]);

tbxTest('ab');




$tbx->loadString("a[x.item;placeholder='0']b")
	->field('x', ['item' => 1]);

tbxTest('a1b');




$tbx->loadString("a[x.item;placeholder='1']b")
	->field('x', ['item' => 1]);

tbxTest('ab');
