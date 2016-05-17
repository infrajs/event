<?php
namespace infrajs\event;
use infrajs\ans\Ans;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../');	
	require_once('vendor/autoload.php');
}
$ans = array();
$ans['title'] = 'Проверка событий';

$val = false;
Event::handler('ontestfire', function () use (&$val) {
	$val = true;
});

Event::fire('ontestfire');

if (!$val) return Ans::err($ans, 'Событие не сработало');



$test=[];
Event::handler('event:0,1',function() use(&$test){
	$test[]=Event::$handler['key'];
},'3');

Event::handler('event:1',function() use(&$test){
	$test[]=Event::$handler['key'];
},'2');


Event::handler('event',function() use(&$test){
	$test[]=Event::$moment['handler']['key'];
},'1');

Event::handler('event',function() use(&$test){
	$test[]=Event::$moment['handler']['key'];
},'0');

Event::fire('event');

$test=[];
Event::handler('go',function() use(&$test){
	$test[]=Event::$moment['handler']['key'];
},'1:0');

Event::handler('go',function() use(&$test){
	$test[]=Event::$moment['handler']['key'];
},'0');

Event::fire('go');
if (implode('', $test)!=='01') return Ans::err($ans, 'Некорректная работа с 0 '.implode(',', $test));



$test=[];
Event::handler('onevent',function() use(&$test){
	$test[]=Event::$moment['handler']['key'];
},'3:0,1');

Event::handler('onevent',function() use(&$test){
	$test[]=Event::$moment['handler']['key'];
},'2:1');


Event::handler('onevent',function() use(&$test){
	$test[]=Event::$moment['handler']['key'];
},'1');

Event::handler('onevent',function() use(&$test){
	$test[]=Event::$moment['handler']['key'];
},'0');

Event::fire('onevent');
if (implode('', $test)!=='1203') return Ans::err($ans, 'Некорректный порядок событий '.implode(',', $test));



$test=[];
$obj1=array('id'=>1);
$obj2=array('id'=>2);
Event::$classes['cls']=function ($obj) {
	return $obj['id'];
};

Event::handler('cls.event', function () use (&$test){
	$test[]=1;
},'two');
Event::handler('cls.event', function () use (&$test){
	$test[]=2;
	return false;
},'one',$obj2);

Event::handler('cls.event', function () use (&$test){
	$test[]=3;
},'one',$obj2);



$r=Event::fire('cls.event', $obj2);
if ($r) return Ans::err($ans, 'Некорректный результат первого события');

$r=Event::fire('cls.event', $obj1);
if (!$r) return Ans::err($ans, 'Некорректный результат второго события');

$r=Event::fire('cls.event', $obj2);
if ($r) return Ans::err($ans, 'Некорректный результат третьего события');

Event::fire('cls.event');
Event::fire('cls.event', $obj2);

if (implode('',$test)!=='1211') return Ans::err($ans, 'Некорректное выполнение '.implode('',$test));






//Звонок в дверь
Event::fire('Звонок в дверь');
$res = null;
Event::handler('Звонок в дверь', function () use (&$res) {
	$res = true;
	return false;
},'Anton:Vika');
Event::handler('Звонок в дверь', function () use (&$res) {
	$res = false;
	return false;
},'Vika');
if (!$res) return Ans::err($ans, 'Звонок в дверь не выполнен');



//Звонок по телефону
$res = null;
Event::handler('Звонок по телефону', function () use (&$res) {
	$res = false;
	return false;
},'Anton:Vika');
Event::handler('Звонок по телефону', function () use (&$res) {
	$res = true;
	return false;
},'Vika');
Event::fire('Звонок по телефону');
if (!$res) return Ans::err($ans, 'Звонок по телефону не выполнен');




return Ans::ret($ans);

