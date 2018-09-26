<?php

$template = '<meta name="[meta.name;magnet=#]" property="[meta.property;magnet=#]" content="[meta.content;block=meta;safe=nobr]" />';


$tbx->loadString($template)
	->block('meta', [['name'=>'robots', 'content'=>'noindex,nofollow']]);

tbxTest('<meta name="robots" content="noindex,nofollow" />');
