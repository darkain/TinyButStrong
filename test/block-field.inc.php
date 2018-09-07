<?php

$tbx->loadString(
	"<el v=\"[item.prop;block=el;p1]\">" .
	"[item.prop;selected='[parent1.prop]'][item.prop]</el>" .
	"<ex v=\"[item.prop;block=ex;p1]\">" .
	"[item.prop;selected='[parent2.prop]'][item.prop]</ex>"
);

$tbx->field('parent1', ['prop' => '2']);
$tbx->field('parent2', ['prop' => '4']);
$tbx->block('item', [
	['prop' => '1'],
	['prop' => '2'],
	['prop' => '3'],
	['prop' => '4'],
	['prop' => '5'],
	['prop' => '6'],
]);


tbxTest('<el v="1">1</el><el v="2" selected>2</el><el v="3">3</el><el v="4">4</el><el v="5">5</el><el v="6">6</el><ex v="1">1</ex><ex v="2">2</ex><ex v="3">3</ex><ex v="4" selected>4</ex><ex v="5">5</ex><ex v="6">6</ex>');





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



$tbx->loadString('<elem>[x.val;block=elem][x.val;selected=1]</elem>')
	->block('x', [['val' => 1]]);

tbxTest('<elem selected>1</elem>');




$tbx->loadString('<elem>[x.val;block=elem][x.val;selected=2]</elem>')
	->block('x', [
		['val' => 1],
		['val' => 2],
		['val' => 3],
		['val' => 4],
	]);

tbxTest('<elem>1</elem><elem selected>2</elem><elem>3</elem><elem>4</elem>');
