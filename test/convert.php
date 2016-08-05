<?php

$tbx->loadString('[x;convert=hex]')
	->field('x', 'test');

tbxTest('74657374');




$tbx->loadString('[x;convert=bin2hex]')
	->field('x', 'test');

tbxTest('74657374');




$tbx->loadString('[x;convert=unhex]')
	->field('x', '74657374');

tbxTest('test');




$tbx->loadString('[x;convert=hex2bin]')
	->field('x', '74657374');

tbxTest('test');




$tbx->loadString('[x;convert=addslashes]')
	->field('x', 'te"st');

tbxTest('te\\"st');
///




$tbx->loadString('[x;convert=chr]')
	->field('x', '65');

tbxTest('A');




$tbx->loadString('[x;convert=chunk_split]')
	->field('x', 'this is a very long string that is about to be broken apart into two different lines');

tbxTest("this is a very long string that is about to be broken apart into two differe\r\nnt lines\r\n");




$tbx->loadString('[x;convert=crc32]')
	->field('x', 'testing');

tbxTest('3908262406');




$tbx->loadString('[x;convert=html_entity_decode]')
	->field('x', '&copy;');

tbxTest('©');




$tbx->loadString('[x;convert=htmlentities]')
	->field('x', '&');

tbxTest('&amp;');




$tbx->loadString('[x;convert=lcfirst]')
	->field('x', 'TESTING SOME WORDS');

tbxTest('tESTING SOME WORDS');




$tbx->loadString('[x;convert=lower]')
	->field('x', 'TESTING SOME WORDS');

tbxTest('testing some words');




$tbx->loadString('[x;convert=ltrim]')
	->field('x', '  text  with  space  ');

tbxTest('text  with  space  ');




$tbx->loadString('[x;convert=rtrim]')
	->field('x', '  text  with  space  ');

tbxTest('  text  with  space');




$tbx->loadString('[x;convert=trim]')
	->field('x', '  text  with  space  ');

tbxTest('text  with  space');




$tbx->loadString('[x;convert=md5]')
	->field('x', 'testing');

tbxTest('ae2b1fca515949e5d54fb22b8ed95575');




$tbx->loadString('[x;convert=metaphone]')
	->field('x', 'testing');

tbxTest('TSTNK');




$tbx->loadString('[x;convert=nl2br]')
	->field('x', "a\nmulti\nline\ntest");

tbxTest("a<br />\nmulti<br />\nline<br />\ntest");




$tbx->loadString('[x;convert=number_format]')
	->field('x', 74657374);

tbxTest('74,657,374');




$tbx->loadString('[x;convert=ord]')
	->field('x', 'a');

tbxTest('97');




$tbx->loadString('[x;convert=sha1]')
	->field('x', 'testing');

tbxTest('dc724af18fbdd4e59189f5fe768a5f8311527050');




$tbx->loadString('[x;convert=soundex]')
	->field('x', 'testing');

tbxTest('T235');




$tbx->loadString('[x;convert=strip_tags]')
	->field('x', '--<a>testing</a>--');

tbxTest('--testing--');




$tbx->loadString('[x;convert=stripcslashes]')
	->field('x', 'test\\"ing');

tbxTest('test"ing');




$tbx->loadString('[x;convert=stripslashes]')
	->field('x', 'test\\"ing');

tbxTest('test"ing');




$tbx->loadString('[x;convert=strlen]')
	->field('x', 'testing');

tbxTest('7');




$tbx->loadString('[x;convert=strrev]')
	->field('x', 'testing');

tbxTest('gnitset');




$tbx->loadString('[x;convert=strtolower]')
	->field('x', 'TESTING SOME TEXT');

tbxTest('testing some text');




$tbx->loadString('[x;convert=strtoupper]')
	->field('x', 'testing some text');

tbxTest('TESTING SOME TEXT');




$tbx->loadString('[x;convert=ucfirst]')
	->field('x', 'testing some text');

tbxTest('Testing some text');




$tbx->loadString('[x;convert=ucwords]')
	->field('x', 'testing some text');

tbxTest('Testing Some Text');




$tbx->loadString('[x;convert=uuencode]')
	->field('x', 'testing');

tbxTest("'=&5S=&EN9P``\n`\n");




$tbx->loadString('[x;convert=uudecode]')
	->field('x', "'=&5S=&EN9P``\n`\n");

tbxTest('testing');




$tbx->loadString('[x;convert=upper]')
	->field('x', 'testing some text');

tbxTest('TESTING SOME TEXT');




$tbx->loadString('[x;convert=wordwrap]')
	->field('x', 'this is a very long string that is about to be broken apart into two different lines');

tbxTest("this is a very long string that is about to be broken apart into two\ndifferent lines");




$tbx->loadString('[x;convert=wordwrap,ucwords,lcfirst]')
	->field('x', 'this is a very long string that is about to be broken apart into two different lines');

tbxTest("this Is A Very Long String That Is About To Be Broken Apart Into Two\nDifferent Lines");
