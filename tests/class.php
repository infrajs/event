<?php
namespace infrajs\event;
use infrajs\ans\Ans;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../../');	
	require_once('vendor/autoload.php');
}


$ans = array();
$ans['title'] = 'Классы.событий';


$obj=array();
$test = '';
Event::handler('test.ontest', function () use (&$test) {
	$test.='a';
});

Event::handler('test.ontest', function () use (&$test) {
	$test.='b';
},'',$obj);

$r=Event::fire('test.ontest', $obj);

if($test!='ab'||!$r) return Ans::err($ans,'Событие с объектом '.$test);



$obj1=array('unick'=>1);
$obj2=array('unick'=>2);
$obj3=array('unick'=>3);
Event::$classes['layer']=function($obj){
	return $obj['unick'];
};

$test = '';
Event::handler('layer.ontest', function (&$obj) use (&$test, &$obj2) {
	$test.=$obj['unick'];
	if($obj['unick']==$obj2['unick']){
		if(!Event::isEqual($obj, $obj2)) return;
	}
	if($obj['unick']===1) return false;
	$test.=$obj['unick'];
});


$r=Event::fire('layer.ontest', $obj2);
if(!$r) return Ans::err($ans,'Событие анализирую объект возвращает true');

$r=Event::fire('layer.ontest', $obj1);
if($r) return Ans::err($ans,'Событие анализирую объект возвращает false');

$r=Event::fire('layer.ontest', $obj3);
if(!$r) return Ans::err($ans,'Событие анализирую объект возвращает true');

$r=Event::fire('layer.ontest', $obj1);
if($r) return Ans::err($ans,'Кэш. Событие анализирую объект возвращает false');

$r=Event::fire('layer.ontest', $obj3);
if(!$r) return Ans::err($ans,'Кэш. Событие анализирую объект возвращает true');

if($test!='22133') return Ans::err($ans,'События должны кэшироваться '.$test);


return Ans::ret($ans);

