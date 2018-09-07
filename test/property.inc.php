<?php


$tbx->loadString('<elem [x;selected]/>')
	->field('x', '');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', '0');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', '00');

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', '01');

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', '1');

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', '2');

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', 0);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', 1);

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected]/>')
	->field('x', true);

tbxTest('<elem selected />');




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

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected=-1]/>')
	->field('x', '1');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=true]/>')
	->field('x', '1');

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected=false]/>')
	->field('x', '1');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=0]/>')
	->field('x', '0');

tbxTest('<elem selected />');




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

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected=0]/>')
	->field('x', '-1');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=1]/>')
	->field('x', '-1');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=-1]/>')
	->field('x', '-1');

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected=true]/>')
	->field('x', '-1');

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected=false]/>')
	->field('x', '-1');

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=0]/>')
	->field('x', true);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=1]/>')
	->field('x', true);

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected=-1]/>')
	->field('x', true);

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected=true]/>')
	->field('x', true);

tbxTest('<elem selected />');




$tbx->loadString('<elem [x;selected=false]/>')
	->field('x', true);

tbxTest('<elem />');




$tbx->loadString('<elem [x;selected=0]/>')
	->field('x', false);

tbxTest('<elem selected />');




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

tbxTest('<elem selected />');




$tbx->loadString('<elem>[x;selected=1]</elem>')
	->field('x', 1);

tbxTest('<elem selected></elem>');




$tbx->loadString('<elem />[x;selected=1]')
	->field('x', 1);

tbxTest('<elem selected />');




$tbx->loadString('<elem />[x;checked=1]')
	->field('x', 1);

tbxTest('<elem checked />');




$tbx->loadString('<elem />[x;disabled=1]')
	->field('x', 1);

tbxTest('<elem disabled />');




$tbx->loadString('<elem />[x;autofocus=1]')
	->field('x', 1);

tbxTest('<elem autofocus />');




$tbx->loadString('<elem />[x;contenteditable=1]')
	->field('x', 1);

tbxTest('<elem contenteditable />');




$tbx->loadString('<elem />[x;editable=1]')
	->field('x', 1);

tbxTest('<elem contenteditable />');




$tbx->loadString('<elem />[x;hidden=1]')
	->field('x', 1);

tbxTest('<elem hidden />');




$tbx->loadString('<elem />[x;reversed=1]')
	->field('x', 1);

tbxTest('<elem reversed />');




$tbx->loadString('<elem />[x;required=1]')
	->field('x', 1);

tbxTest('<elem required />');




$tbx->loadString('<elem />[x;scoped=1]')
	->field('x', 1);

tbxTest('<elem scoped />');
