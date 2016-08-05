<?php


$tbx->loadString('<elem prop="[x.prop;magnet=#;noerr]" attr="[x.attr;block=elem]">test</elem>')
	->block('x', [['attr'=>1], ['attr'=>2]]);

tbxTest('<elem attr="1">test</elem><elem attr="2">test</elem>');
