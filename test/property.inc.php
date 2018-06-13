<?php


$tbx->loadString('<elem [x;selected]/>')
	->field('x', '');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', '0');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', '00');

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', '01');

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', '1');

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', '2');

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', 0);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', 1);

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', true);

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', false);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', NULL);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', NAN);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', INF);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', -INF);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=0]/>')
	->field('x', '1');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=1]/>')
	->field('x', '1');

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected=-1]/>')
	->field('x', '1');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=true]/>')
	->field('x', '1');

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected=false]/>')
	->field('x', '1');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=0]/>')
	->field('x', '0');

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected=1]/>')
	->field('x', '0');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=-1]/>')
	->field('x', '0');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=true]/>')
	->field('x', '0');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=false]/>')
	->field('x', '0');

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected=0]/>')
	->field('x', '-1');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=1]/>')
	->field('x', '-1');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=-1]/>')
	->field('x', '-1');

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected=true]/>')
	->field('x', '-1');

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected=false]/>')
	->field('x', '-1');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=0]/>')
	->field('x', true);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=1]/>')
	->field('x', true);

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected=-1]/>')
	->field('x', true);

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected=true]/>')
	->field('x', true);

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected=false]/>')
	->field('x', true);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=0]/>')
	->field('x', false);

tbxTest('<elem selected/>');




$tbx->loadString('<elem [x;selected=1]/>')
	->field('x', false);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=-1]/>')
	->field('x', false);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=true]/>')
	->field('x', false);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=false]/>')
	->field('x', false);

tbxTest('<elem selected/>');
