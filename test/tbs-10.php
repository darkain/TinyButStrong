<?php


////////////////////////////////////////////////////////////////////////////////
// TESTING FOR TIMESTAMPS THAT ARE BROKEN IN TBS BUT NOT TBX
// https://github.com/Skrol29/tinybutstrong/issues/10
////////////////////////////////////////////////////////////////////////////////




$tbx->loadString('[timestamp;date=Y-m-d H:i:s - 1]')
	->field('timestamp', 1523447097);

tbxTest(date('2018-04-11 11:44:57 - 1'));




$tbx->loadString("[timestamp;date='Y-m-d H:i:s - 2']")
	->field('timestamp', 1523447097);

tbxTest(date('2018-04-11 11:44:57 - 2'));




$tbx->loadString('[timestamp;date=Y-m-d H:i:s - 3]')
	->field('timestamp', '1523447097');

tbxTest(date('2018-04-11 11:44:57 - 3'));




$tbx->loadString("[timestamp;date='Y-m-d H:i:s - 4']")
	->field('timestamp', '1523447097');

tbxTest(date('2018-04-11 11:44:57 - 4'));




$tbx->loadString('[timestamp;date=Y-m-d H:i:s - 5]')
	->field('timestamp', 1528648609);

tbxTest(date('2018-06-10 16:36:49 - 5'));




$tbx->loadString("[timestamp;date='Y-m-d H:i:s - 6']")
	->field('timestamp', 1528648609);

tbxTest(date('2018-06-10 16:36:49 - 6'));




$tbx->loadString('[timestamp;date=Y-m-d H:i:s - 7]')
	->field('timestamp', '1528648609');

tbxTest(date('2018-06-10 16:36:49 - 7'));




$tbx->loadString("[timestamp;date='Y-m-d H:i:s - 8']")
	->field('timestamp', '1528648609');

tbxTest(date('2018-06-10 16:36:49 - 8'));
