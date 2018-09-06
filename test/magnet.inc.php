<?php


$tbx->loadString('a<elem>[x.val;magnet=elem]</elem>b')
	->field('x', ['val'=>'']);

tbxTest('ab');




$tbx->loadString('a<elem>[x.val;magnet=elem]</elem>b')
	->field('x', ['val'=>NULL]);

tbxTest('ab');




$tbx->loadString('a<elem>[x.val;magnet=elem]</elem>b')
	->field('x', ['val'=>0]);

tbxTest('a<elem>0</elem>b');




$tbx->loadString('a<elem>[x.val;magnet=elem]</elem>b')
	->field('x', ['val'=>1]);

tbxTest('a<elem>1</elem>b');




$tbx->loadString('a<elem>[x.val;magnet=elem]</elem>b')
	->field('x', ['val'=>'1']);

tbxTest('a<elem>1</elem>b');




$tbx->loadString('a<elem>[x.val;magnet=elem]</elem>b')
	->field('x', ['val'=>true]);

tbxTest('a<elem>1</elem>b');




$tbx->loadString('a<elem>[x.val;magnet=elem]</elem>b')
	->field('x', ['val'=>false]);

tbxTest('ab');




$tbx->loadString('a<elem>[x.val;magnet=elem]</elem>b')
	->field('x', ['val'=>0.0]);

tbxTest('a<elem>0</elem>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"></elem>b')
	->field('x', ['val'=>'']);

tbxTest('a<elem></elem>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"></elem>b')
	->field('x', ['val'=>NULL]);

tbxTest('a<elem></elem>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"></elem>b')
	->field('x', ['val'=>0]);

tbxTest('a<elem prop="0"></elem>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"></elem>b')
	->field('x', ['val'=>1]);

tbxTest('a<elem prop="1"></elem>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"></elem>b')
	->field('x', ['val'=>'1']);

tbxTest('a<elem prop="1"></elem>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"></elem>b')
	->field('x', ['val'=>true]);

tbxTest('a<elem prop="1"></elem>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"></elem>b')
	->field('x', ['val'=>false]);

tbxTest('a<elem></elem>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"></elem>b')
	->field('x', ['val'=>0.0]);

tbxTest('a<elem prop="0"></elem>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"/>b')
	->field('x', ['val'=>'']);

tbxTest('a<elem/>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"/>b')
	->field('x', ['val'=>NULL]);

tbxTest('a<elem/>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"/>b')
	->field('x', ['val'=>0]);

tbxTest('a<elem prop="0"/>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"/>b')
	->field('x', ['val'=>1]);

tbxTest('a<elem prop="1"/>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"/>b')
	->field('x', ['val'=>'1']);

tbxTest('a<elem prop="1"/>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"/>b')
	->field('x', ['val'=>true]);

tbxTest('a<elem prop="1"/>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"/>b')
	->field('x', ['val'=>false]);

tbxTest('a<elem/>b');




$tbx->loadString('a<elem prop="[x.val;magnet=#]"/>b')
	->field('x', ['val'=>0.0]);

tbxTest('a<elem prop="0"/>b');




$tbx->loadString('<elem prop="[x.prop;magnet=#]" attr="[x.attr;block=elem;magnet=#]">test</elem>')
	->block('x', [['attr'=>1], ['attr'=>2]]);

tbxTest('<elem attr="1">test</elem><elem attr="2">test</elem>');




$tbx->loadString('<elem prop="[x.prop;magnet=#]" attr="[x.attr;block=elem;magnet=#]">test</elem>')
	->block('x', [['prop'=>1], ['prop'=>2]]);

tbxTest('<elem prop="1">test</elem><elem prop="2">test</elem>');




$tbx->loadString('<elem prop="[x.prop;magnet=#]" attr="[x.attr;block=elem;magnet=#]">test</elem>')
	->block('x', [['attr'=>1], ['prop'=>2]]);

tbxTest('<elem attr="1">test</elem><elem prop="2">test</elem>');




$tbx->loadString('<elem prop="[x.prop;magnet=#]" attr="[x.attr;block=elem;magnet=#]">test</elem>')
	->block('x', [['prop'=>1], ['attr'=>2]]);

tbxTest('<elem prop="1">test</elem><elem attr="2">test</elem>');
