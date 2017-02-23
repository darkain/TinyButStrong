<?php


class tbx_plugin_test implements tbx_plugin {
	public function tbx_render($array) {
		return implode('=', $array);
	}
}

$tbxplugin = new tbx_plugin_test;

$tbx->loadString('-[x.a.bba.x1.y2.3z]-')
	->field('x', $tbxplugin);

tbxTest('-a=bba=x1=y2=3z-');
