<?php
namespace infrajs\event;
use infrajs\ans\Ans;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../../');	
	require_once('vendor/autoload.php');
}
$ans = array();
$ans['title'] = 'Проверка ключей событий';



$test = '';
Event::handler('ontestkeys', function () use (&$test) {
	$test .= 3;
},'some:test');
Event::handler('ontestkeys', function () use (&$test) {
	$test .= 1;
},'test');
Event::handler('ontestkeys', function () use (&$test) {
	$test .= 2;
},'test');
Event::fire('ontestkeys');
if ($test!='123') return Ans::err($ans, 'Группа событий не сработало в нужном порядке '.$test);



$test = '';
Event::handler('oh.ontestkeys', function () use (&$test) {
	$test .= 2;
});
Event::handler('oh.ontestkeys', function () use (&$test) {
	$test .= 3;
},'test');
Event::handler('oh.ontestkeys', function () use (&$test) {
	$test .= 1;
},'oh');


Event::fire('oh.ontestkeys');

if ($test!='123') return Ans::err($ans, 'Имя класса - ключ по умаолчанию '.$test);


return Ans::ret($ans);

