<?php
namespace infrajs\event;
use infrajs\ans\Ans;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../../');	
	require_once('vendor/autoload.php');
}


$ans = array();
$ans['title'] = 'Последовательность событий';

$obj1=array('unick'=>'a');
$obj2=array('unick'=>'b');
Event::$classes['layer'] = function ($obj) {
	return $obj['unick'];
};



$test = '';
Event::handler('layer.oncheck', function (&$obj) use (&$test) {
	$test.='2:'.$obj['unick']."#";

},'env:external');
Event::handler('layer.oncheck', function (&$obj) use (&$test) {
	$test.='1:'.$obj['unick'].'-';
},'external');

Event::fire('layer.oncheck', $obj1);

Event::fire('layer.oncheck', $obj2);

if ($test!=='1:a-2:a#1:b-2:b#') return Ans::err($ans, 'Некорректный порядок '.$test);


Event::handler('test', function (&$obj) use (&$test) {
	echo 2;
},'tpl:tpl');
$r=false;

try {
	Event::fire('test');
} catch (\Exception $e){
	$r=true;
}
if (!$r) return Ans::err($ans, 'Не сработало исключение');





return Ans::ret($ans);

