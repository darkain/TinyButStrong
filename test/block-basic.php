<?php

$tbx->loadString('<a href="[x.link;block=a]">[x.text]</a>')
	->block('x', [
		['link'=>1, 'text'=>2],
	]);

tbxTest('<a href="1">2</a>');




$tbx->loadString('<a href="[x.link;block=a]">[x.text]</a>')
	->block('x', [
		['link'=>1, 'text'=>2],
		['link'=>3, 'text'=>4],
	]);

tbxTest('<a href="1">2</a><a href="3">4</a>');




$tbx->loadString('<a href="[x.link;block=a]">[x.text]</a>')
	->block('x', [
		['link'=>1, 'text'=>2],
		['link'=>3, 'text'=>4],
		['link'=>5, 'text'=>6],
		['link'=>7, 'text'=>8],
		['link'=>9, 'text'=>10],
	]);

tbxTest('<a href="1">2</a><a href="3">4</a><a href="5">6</a><a href="7">8</a><a href="9">10</a>');




$tbx->loadString('<elem>[x.data;block=elem]</elem>')
	->block('x', [
		['data'=>0],
	]);

tbxTest('<elem>0</elem>');




$tbx->loadString('<elem>[x.data;block=elem]</elem>')
	->block('x', [
		['data'=>0],
		['data'=>1],
		['data'=>2],
		['data'=>3],
		['data'=>-1],
		['data'=>-2],
		['data'=>-3],
		['data'=>PHP_INT_MAX],
		['data'=>PHP_INT_MIN],
	]);

tbxTest('<elem>0</elem><elem>1</elem><elem>2</elem><elem>3</elem><elem>-1</elem><elem>-2</elem><elem>-3</elem><elem>9223372036854775807</elem><elem>-9223372036854775808</elem>');




$tbx->loadString('<elem>[x.a;noerr;block=elem;ifempty=[x.b;noerr;ifempty=[x.c]]]</elem>')
	->block('x', [
		['c'=>'zzzz', 'r'=>'', 't'=>''],
		['b'=>'yyyy', 'c'=>'zzzz', 't'=>''],
		['a'=>'xxxx', 'b'=>'yyyy', 'c'=>'zzzz'],
	]);

tbxTest('<elem>zzzz</elem><elem>yyyy</elem><elem>xxxx</elem>');




$tbx->loadString('<elem param="[x.param;block=elem;noerr;ifempty=\'[x.alternate]\']">data</elem>')
	->block('x', [
		['param'=>'a', 'alternate'=>'b'],
		['alternate'=>'c'],
	]);

tbxTest('<elem param="a">data</elem><elem param="c">data</elem>');




$tbx->loadString('<elem>[x.item;block=elem;implode]</elem>')
	->block('x', [['item' => [0]]]);

tbxTest('<elem>0</elem>');




$tbx->loadString('<elem>[x.item;block=elem;implode]</elem>')
	->block('x', [['item' => [0,1,2,3,4,5]]]);

tbxTest('<elem>012345</elem>');




$tbx->loadString('<elem>[x.item;block=elem;implode=]</elem>')
	->block('x', [['item' => [0,1,2,3,4,5]]]);

tbxTest('<elem>012345</elem>');




$tbx->loadString('<elem>[x.item;block=elem;implode=,]</elem>')
	->block('x', [['item' => [0,1,2,3,4,5]]]);

tbxTest('<elem>0,1,2,3,4,5</elem>');
