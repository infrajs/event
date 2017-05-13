<?php
namespace infrajs\event;
use infrajs\once\Once;
use infrajs\event\Event;
use infrajs\each\Each;

class Event {
	public static $list = array();
	public static $classes = array();
	public static $moment = array();
	private static function &getList($name) //У одного имени есть два списка. глобальное событие и события alien других объектов 
	{
		$list=&static::$list;
		if (!isset($list[$name])) {
			$list[$name] = array(
				'data' => array(), //Массив с данными по объектам
				'result' => array(), //Выполнено событие или нет
				'list' => array(), //Очередь
				'keys' => array(), //Ключи всех подписок с количеством
				'readyobj' => array(),//Массив с временными отметками по объектам что выполнено. При равенстве количество с keys. Ключ попадает в массив ready
				'readykeys' => array() //Выполненные ключи
			);
		}
		return $list[$name];
	}
	public static function createFire($name, &$obj) 
	{
		$fire = Event::createContext($name, $obj);
		$fire['data'] = &$fire['list']['data'][$fire['objid']];
		$fire['data']['fire'] = &$fire; //У data fire Один
		return $fire;
	}
	public static function createHandler($name, $callback, $key, &$obj) 
	{
		$handler = Event::createContext($name, $obj, $key);
		$handler['callback'] = $callback;
		
		$handler['list']['list'][] = $handler;

		if ($handler['key']||$handler['key']=='0') {
			if (empty($handler['list']['keys'][$handler['key']])) $handler['list']['keys'][$handler['key']] = array($handler);
			else $handler['list']['keys'][$handler['key']][] = $handler; //Сосчитали все ключи
		}
		return $handler;
	}
	public static function createContext($name, &$obj, $key='')
	{
		
		$p=explode(':', $key, 2);
		$key=$p[0];
		if (sizeof($p)==2) {
			$keys = explode(',', $p[1]);
		} else {
			$keys = array();
		}

		$p=explode('.', $name);
		if (sizeof($p)>1) {
			$class=$p[0];
		} else {
			$class='';
			if ($obj) throw new \Exception('Для события с объектом класс обязателен');
		}
		if ($class) { 
			//одноимённый с классом ключ есть всегда. 
			//Вроде того что у класса есть свои собственные обработчики котоорые должны сработать первыми.
			//Если в качестве ключе указать название класса этот обработчик всегда будет первым
			$keys[]=$class;
		}
		/*if ($class == 'Controller') {
			$class = 'Infrajs';
			$p[0] = $class;
			$name = implode('.',$p);
		} else if ($class == 'Layer') {
			$class = 'layer';
			$p[0] = $class;
			$name = implode('.',$p);
		}*/
		$classes = static::$classes;
		if ($obj) {
			if(empty($classes[$class])) {
				echo '<pre>';
				debug_print_backtrace();
				throw new \Exception('Функция класса объекта '.$class.' не указанна пример Event::$classes["'.$class.'"] = function($obj) { return $obj["id"] }');
			} else {
				$objid = $classes[$class]($obj);
			}
		} else {
			$objid = '';
		}
		
		$list = &Event::getList($name);
		$list['class'] = $class;
		$context = array(
			'key' => $key,
			'executed' => array(),
			'name'=> $name,
			'class'=> $class,
			'keys'=> $keys,
			'obj'=> &$obj,
			'objid'=> $objid,
			'list'=> &$list
		);

		if (empty($list['data'][$context['objid']])) $list['data'][$context['objid']] = array();
		return $context;
	}
	public static function is($r){
		return is_null($r)? true : $r;
	}
	//wait when listen
	public static function one($name, $callback, $key = '', &$obj = null)
	{
		$ready = false;
		Event::handler($name, function (&$obj) use (&$ready, $callback){
			if ($ready) return;
			$ready = true;
			return $callback($obj);
		}, $key, $obj);
	}
	public static function onext($name, $callback, $key = '', &$obj = null)
	{
		$ready = false;
		Event::createHandler($name, function (&$obj) use (&$ready){
			if ($ready) return;
			$ready = true;
			return $callback($obj);
		}, $key, $obj);
	}

	public static function handler($name, $callback, $key = null, &$obj = null)
	{
		$handler = static::createHandler($name, $callback, $key, $obj);
		static::keystik($handler);
		if ($obj) { 
			if (!empty($handler['list']['result'][$handler['objid']])) {
				//Метка result появляется когда очередь уже выполнена иначе событие выполнится в общем порядке
				//Подписка на совершённое событие 
				$r = $callback($obj); //Подписка на конкретный объект
				if (!Event::is($r)) $handler['list']['result'][$handler['objid']] = false;
			}
		} else { //Подписка на все объекты
			foreach ($handler['list']['result'] as $objid=>$k) { //срабатывает для уже обработанных объектов
				if (empty($handler['list']['result'][$objid])) continue; //Для прерванных false результатов не запускаем
				$r = $callback($handler['list']['data'][$objid]['fire']['obj']);
				if (!Event::is($r)) $handler['list']['result'][$handler['objid']] = false;
			}
		}
	}
	public static function clear($name) {
		static::tik($name, true);
	}
	public static function tik($name = null, $clear = null)
	{
		/**
		 * Режим повторения, сбросить что есть и начать заного.
		 * Передаётся класс или имя
		 * clear true - удалить и всех подписчиков
		 **/
		$lists = &Event::$list;
		if (!$name) {
			foreach ($lists as $name=>$v) {
				Event::tik($name, $clear);
			}
			return;
		}
		if (!empty($lists[$name])) {
			$list = &$lists[$name];
			for ($i = 0, $l = sizeof($list['list']); $i < $l; $i++) {
				$list['list'][$i]['executed'] = array();
			}
			$list['data'] = array();
			$list['result'] = array(); //Выполнено событие или нет
			$list['readyobj'] = array();//Массив с временными отметками по объектам что выполнено. При равенстве количество с keys. Ключ попадает в массив ready
			$list['readykeys'] = array(); //Выполненные ключи
			if ($clear) {
				$list['keys'] = array();
				$list['list'] = array();
			}
		} else {
			foreach ($lists as $i => $v) {
				if ($lists[$i]["class"] != $name) continue;
				Event::tik($i, $clear);
			}
		}	
	}
	
	public static function fire($name, &$obj = null)
	{
		/**
		 * Уникальность очереди событий определяется именем события содержащей имя класса события.
		 * Все подписки хранятся в классе и объект не меняется
		 **/

		$fire = Event::createFire($name, $obj);
		
		$list = &$fire['list'];
		$data = &$fire['data'];


		/**
		 * TODO: Реализация is isshow... нужно сбрасывать события
		 **/
		
		if (isset($list['result'][$fire['objid']]) && !is_null($list['result'][$fire['objid']])) return $list['result'][$fire['objid']];
		
		if (isset($data['executed']) && $data['executed'] === false) return true; //Защита от рекурсий вложенный вызов вернёт true

		$data['executed'] = false;

		if (!isset($list['readykeys'][$fire['objid']])) $list['readykeys'][$fire['objid']] = array();
		if (!isset($list['readyobj'][$fire['objid']])) $list['readyobj'][$fire['objid']] = array();
		
		
		// TODO: проверить обработку несуществующих ключей
		for ($i = 0, $l = sizeof($list['list']);  $i < $l; $i++) { //Подписка на ходу
			Event::keystik($list['list'][$i]);
		}
		$r = Event::execute($fire, $list);
		if (Event::is($r)) $r = true;
		else $r = false;
		$list['result'][$fire['objid']] = $r;
		$data['executed'] = true;
		return $list['result'][$fire['objid']];
	}
	private static function keystik(&$handler){
		$handler['keystik'] = array_filter($handler['keys'], function ($n) use (&$handler) { //Из условия подписчика убраны несуществующие ключи
			if (empty($handler['list']['keys'][$n])) return false;
			if (!sizeof($handler['list']['keys'][$n])) return false;
			return true;
		});
	}
	private static function hv($val){
		if (is_numeric($val)||(is_string($val)&&$val!=='')) return true;
		return false;
	}
	private static function execute($fire, &$list)
	{
		$omit = false;

		for ($i = 0; $i < sizeof($list['list']); $i++) { //Подписка на ходу
			$handler=&$list['list'][$i];
			if (!empty($handler['executed'][$fire['objid']])) continue;
			
			if(!is_null($handler['obj']) && $handler['objid']!==$fire['objid'] ) {
				continue;
			} else {
				//Объекта у подписки не указан
				//fire с объктом, подписка без объекта
				//подписка должна выполниться для всех объектов fire. Проходим дальше
			}
			if (empty($handler['keystik'])) Event::keystik($handler);
			$iskeys = array_diff($handler['keystik'], $list['readykeys'][$fire['objid']]); //Проверили выполнены ли все существующие ключи

			if (sizeof($iskeys) && (sizeof($iskeys)!=1 || is_null($handler['key']) || !in_array($handler['key'], $handler['keys']))) {
				$omit=array(
						'keys'=>$iskeys,
						'handler'=>$handler, 
						'fire'=> $fire
				);

				continue; //Найден неудовлетворённый ключ.. может быть выход из цикла и на ислкючение
			}
			

			
			if (empty($list['readyobj'][$fire['objid']][$handler['key']])) $list['readyobj'][$fire['objid']][$handler['key']] = 0;
			$list['readyobj'][$fire['objid']][$handler['key']]++;
			
			

			if (static::hv($handler['key'])) {//Если есть ключ
				if (!in_array($handler['key'], $list['readykeys'][$fire['objid']])) { //И этого ключа ещё не было
					//Если выполнено по объекту столько же сколько всего обработчиков
					if ($list['readyobj'][$fire['objid']][$handler['key']] === sizeof($list['keys'][$handler['key']])) {
						$list['readykeys'][$fire['objid']][]=$handler['key'];
					}
				}
			}
			

			$handler['executed'][$fire['objid']] = true;
			$moment = array(
				"fire" => $fire,
				"handler" => $handler,
				"parent" => static::$moment,
				"list" => $list,
				"i" => $i
			);
			static::$moment = $moment;

			
			$r = $handler['callback']($fire['obj']);
			static::$moment=$moment['parent'];

			if (!static::is($r)) return $r;

			$r = static::execute($fire, $list);
			return $r;

		}
		if ($omit) {
			echo '<pre>';
			unset($fire['obj']);
			print_r($omit);
			echo '<hr>';
			print_r($list);
			throw new \Exception('Рекурсивная зависимость подписчиков. '.implode(',', $omit['keys']));
		}
	}
}
