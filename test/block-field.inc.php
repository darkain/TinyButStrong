<?php


$tbx->loadString('<elem>[x.val;block=elem][y;selected=[x.val]]</elem>')
	->block('x', [
		['val' => 1],
		['val' => 2],
		['val' => 3],
		['val' => 4],
	])
	->field('y', 2);

tbxTest('<elem>1</elem><elem selected>2</elem><elem>3</elem><elem>4</elem>');




$tbx->loadString('<elem>[x.val;block=elem][x.val;selected=[y]]</elem>')
	->field('y', 3);

tbxTest('<elem>[x.val;block=elem][x.val;selected=3]</elem>');




$tbx->loadString('<elem>[x.val;block=elem;selected=1]</elem>')
	->block('x', [['val' => 1]]);

tbxTest('<elem selected></elem>');



$tbx->loadString('<elem>[x.val][x.val;selected=1]</elem>')
	->block('x', [['val' => 1]]);

tbxTest('<elem selected>1</elem>');



$tbx->loadString('<elem>[x.val;selected=1][x.val;block=elem]</elem>')
	->block('x', [['val' => 1]]);

tbxTest('<elem selected>1</elem>');


/*
//TODO: THIS IS CURRENTLY BROKEN
$tbx->loadString('<elem>[x.val;block=elem][x.val;selected=1]</elem>')
	->block('x', [['val' => 1]]);

tbxTest('<elem selected>1</elem>');
*/
