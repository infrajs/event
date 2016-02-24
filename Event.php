<?php
namespace infrajs\event;
use infrajs\once\Once;
use infrajs\event\Event;
/**
 * 1. Добавляются обработчики Event::handler('question.otherkey.otherkey', $handler, 'selfkey') обработчик запустится с 1 аргументом, проанализировав который обработчик должен сказать null или вернуть значение.
 * 2. Во втором этапе вызывается Event::handler('question', $obj) и начинают запускаться обработчики. Первый вернувший false ответ остановит очередь. Если обработчики ничего не вернуть is будет возвращать true.
 * При повторных вызовах возвращается кэш. Очердь одноразовая, простая, быстрая.
 * При подписки добавляются ключевые слова
 **/

class Event {
	private static $list;
	public static $fire;
	public static $handler;
	public static $classes = array();
	public static $conf = array();
	private static function &getList($name) //У одного имени есть два списка. глобальное событие и события alien других объектов 
	{
		$list=&static::$list;
		if (!isset($list[$name])) {
			$list[$name] = array(
				'result'=>array(), //Выполнено событие или нет
				'list'=>[], //Очередь
				'keys'=>array(), //Ключи всех подписок с количеством
				'readyobj'=>array(),//Массив с временными отметками по объектам что выполнено. При равенстве количество с keys. Ключ попадает в массив ready
				'ready'=>array() //Выполненные ключи
			);
		}
		return $list[$name];
	}
	public static function createContext($name, &$obj, $key='')
	{
		$p=explode(':', $key, 2);
		$key=$p[0];
		if (sizeof($p)==2) {
			$keys = explode(',', $p[1]);
			//$keys = array_map('trim', $keys);
		} else {
			$keys = array();
		}
		$p=explode('.', $name);
		if (sizeof($p)>1) {
			$class=$p[0];
		} else {
			if ($obj) {
				$name='alien.'.$name;
				$class='alien';
			} else {
				$class='';
			}
		}
		if ($class) {
			$keys[]=$class;
		}
		$classes=static::$classes;
		if (isset($classes[$class])&&is_array($obj)) {
			$objid = $classes[$class]($obj);
		} else {
			$objid = '';
		}
		$handler=array(
			'key' => trim($key),
			'executed' => array(),
			'name' => $name,
			'class' => $class,
			'keys' => $keys,
			'obj' => &$obj,
			'objid' => $objid
		);
		return $handler;
	}
	public static function handler($name, $callback, $key = null, &$obj = null)
	{

		$handler = static::createContext($name, $obj, $key);
		$handler['callback'] = $callback;
		$list=&static::getList($handler['name']);
		$list['list'][] = $handler;

		
		if (!isset($list['keys'][$handler['key']])) {
			$list['keys'][$handler['key']]=0;
		}
		$list['keys'][$handler['key']]++; //Сосчитали все ключи
		
		/**
		 * Если все подписки имею ключи и нет ниодного выполненного ключа, то будет выполнена первая подписка в очереди.
		 **/

		if (isset($list['result'][$handler['objid']])) {
			$conf=Event::$conf;
			echo '<pre>';
			print_r($handler);
			print_r($list);
			throw new \Exception('Подписка на совершённое событие');
		}
	}
	public static function one($name, $callback, $key = null, &$obj = null) {
		$ready = false;
		static::handler($name, function () use (&$ready){
			if ($ready) return;
			$ready = true;
			return $callback();
		}, $key, $obj);
	}
	
	/**
	 * Одно событие для одного объекта генерируется один раз
	 * fire('layer.onshow', $layer);
	 **/
	public static function fire($name, &$obj=null)
	{
		/**
		 * Уникальность очереди событий определяется именем события содержащей имя класса события.
		 * Все подписки хранятся в классе и объект не меняется
		 **/


		$fire = static::createContext($name, $obj);
		$list = &Event::getList($fire['name']);

		
		
		if (isset($list['result'][$fire['objid']])) {
			return $list['result'][$fire['objid']];
		}
		if (!isset($list['ready'][$fire['objid']])) {
			$list['ready'][$fire['objid']]=array();
		}
		if (isset($list['readyobj'][$fire['objid']])) {
			$list['readyobj'][$fire['objid']]=array();
		}
		

		//Подписка в подписке
		//$list['result'][$fire['objid']] = $fire;
		//Генерация события в подписке
		
		for ($i = 0, $l = sizeof($list['list']);  $i < $l; $i++) { //Подписка на ходу
			$handler=&$list['list'][$i];
			$handler['keys']=array_intersect($handler['keys'], array_keys($list['keys'])); //Убрали ключи которых вообще нет
		}
		$r=Event::run($fire, $list);
		if (is_null($r) || $r) $r = true;
		else $r = false;

		$list['result'][$fire['objid']] = $r;

		return $list['result'][$fire['objid']];
		
	}
	private static function run(&$fire, &$list)
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

			$iskeys = array_diff($handler['keys'], $list['ready'][$fire['objid']]); //Проверили выполнены ли все существующие ключи
			
			if (sizeof($iskeys) && (sizeof($iskeys)!=1 || is_null($handler['key']) || !in_array($handler['key'], $handler['keys']))) {
				$omit=array('keys'=>$iskeys,'handler'=>$handler);
				continue; //Найден неудовлетворённый ключ.. может быть выход из цикла и на ислкючение
			}

			if(!is_null($handler['key'])) {
				//Если есть ключ и его ещё не было
				if(!in_array($handler['key'], $list['ready'][$fire['objid']])) {
					if(!isset($list['readyobj'][$fire['objid']][$handler['key']])){
						$list['readyobj'][$fire['objid']][$handler['key']] = 0;
					}
					$list['readyobj'][$fire['objid']][$handler['key']]++;
					//Если выполнено по объекту столько же сколько всего обработчиков
					if($list['readyobj'][$fire['objid']][$handler['key']]===$list['keys'][$handler['key']]) {
						$list['ready'][$fire['objid']][]=$handler['key'];
					}
				}
			}

			$handler['executed'][$fire['objid']] = true;
			Event::$fire = $fire;
			Event::$handler = $handler;
			$r = $handler['callback']($fire['obj']);
			if (!is_null($r) && !$r) return $r;

			$r=Event::run($fire, $list);
			return $r;

		}
		if ($omit) {
			$conf=Event::$conf;
			
			echo '<pre>';
			unset($fire['obj']);
			print_r($omit);
			echo '<hr>';
			print_r($list);
			throw new \Exception('Рекурсивная зависимость подписчиков. '.implode(',', $omit['keys']));
		}
	}
}

Event::$classes['alien'] = function($obj){
	return '';
};