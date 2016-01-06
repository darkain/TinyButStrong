<?php

$tbx->loadString("[x;date=Y-m-d]")
	->field('x', 449020800);

tbxTest('1984-03-25');




$tbx->loadString("[x;date=Ymd]")
	->field('x', 449020800);

tbxTest('19840325');




$tbx->loadString("[x;date=Y m d]")
	->field('x', 449020800);

tbxTest('1984 03 25');




$tbx->loadString("[x;date=r]")
	->field('x', 449020800);

tbxTest('Sun, 25 Mar 1984 00:00:00 +0000');




$tbx->loadString("[x;date=r]")
	->field('x', 449031701);

tbxTest('Sun, 25 Mar 1984 03:01:41 +0000');




$tbx->loadString("[x;date=r]")
	->field('x', '449031701');

tbxTest('Sun, 25 Mar 1984 03:01:41 +0000');




$tbx->loadString("[x;date=r]")
	->field('x', '1984-03-25');

tbxTest('Sun, 25 Mar 1984 00:00:00 +0000');




$tbx->loadString("[x;date=r]")
	->field('x', 'now');

tbxTest(date('r'));




$tbx->loadString("[onload..now;date=r]");

tbxTest(date('r'));
