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
