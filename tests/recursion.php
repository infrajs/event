<?php
namespace infrajs\event;
use infrajs\ans\Ans;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../../');	
	require_once('vendor/autoload.php');
}


$ans = array();
$ans['title'] = 'Рекурсия в событиях';



$test = '';
Event::handler('ontestrec', function () use (&$test) {
	Event::handler('ontestrec', function () use (&$test) {
		$test.='b';
	});
	$test.='a';
});
$r=Event::fire('ontestrec');
if($test!='ab'||!$r) return Ans::err($ans,'Вложенная подписка '.$test);



$test = '';
Event::handler('onfoo', function () use (&$test) {
	$r=Event::fire('onfoo'); //Реультат события без учёта текущего обработчика
	if(!$r) return false;

	$test.='2'; //Так я могу записать себя в конец списка

	return false; //Событие false только тогда когда оно true 
});
Event::handler('onfoo', function () use (&$test) {
	$test.='1';
});


$r=Event::fire('onfoo');

if($r) return Ans::err($ans,'Самозависимость');
if($test!='12') return Ans::err($ans,'Динамическое управление порядком');



Event::handler('ohoho', function () use (&$test) {
	
},'foo:bar');
Event::handler('ohoho', function () use (&$test) {
	
},'bar:foo');

$r=false;
try {
	Event::fire('ohoho');
} catch (\Exception $e){
	$r=true;
}
if (!$r) return Ans::err($ans,'Должно было сработать рекурсивное исключение');


Event::handler('ohoho2', function () use (&$test) {
	$test='asdf';
},'some:some');

Event::fire('ohoho2');

if ($test!='asdf') return Ans::err($ans,'Сам себя не может заблокировать');


return Ans::ret($ans);

