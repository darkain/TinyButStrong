<?php


$tbx->loadString("[x;format=%d]")
	->field('x', 56);

tbxTest('56');




$tbx->loadString("[x;format=%05d]")
	->field('x', 56);

tbxTest('00056');




$tbx->loadString("[x;format=%b]")
	->field('x', 56);

tbxTest('111000');




$tbx->loadString("[x;format=%c]")
	->field('x', 56);

tbxTest('8');




$tbx->loadString("[x;format=%e]")
	->field('x', 56);

tbxTest('5.600000e+1');




$tbx->loadString("[x;format=%0.1e]")
	->field('x', 56);

tbxTest('5.6e+1');




$tbx->loadString("[x;format=%f]")
	->field('x', 56);

tbxTest('56.000000');




$tbx->loadString("[x;format=%0.1f]")
	->field('x', 56);

tbxTest('56.0');




$tbx->loadString("[x;format=%o]")
	->field('x', 56);

tbxTest('70');




$tbx->loadString("[x;format=%s]")
	->field('x', 56);

tbxTest('56');




$tbx->loadString("[x;format=%u]")
	->field('x', 1);

tbxTest('1');




$tbx->loadString("[x;format=%u]")
	->field('x', -1);

tbxTest('18446744073709551615');




$tbx->loadString("[x;format=%X]")
	->field('x', 220);

tbxTest('DC');




$tbx->loadString("[x;format=%x]")
	->field('x', 220);

tbxTest('dc');




$tbx->loadString("[x;format=%x]")
	->field('x', 'string');

tbxTest('0');
