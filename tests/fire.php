<?php
namespace infrajs\event;
use infrajs\ans\Ans;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../../');	
	require_once('vendor/autoload.php');
}


$ans = array();
$ans['title'] = 'Проверка событий';

$val = false;
Event::listeng('ontest', function () use (&$val) {
	$val = true;
});

Event::fireg('ontest');

if (!$val) return Ans::err($ans, 'Событие не сработало');


return Ans::ret($ans);