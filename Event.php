<?php
namespace infrajs\event;

class Event {
	public static $obj = array();
	public static function fireg($clsfn, $argso = array())
	{
		return static::fire(static::$obj, $clsfn, $argso);
	}
	public static function fire(&$obj = null, $clsfn, $argso = array())
	{
		if (is_null($obj)) $obj = &static::$obj;
		if ($obj !== static::$obj) {
			$clsfn = explode('.', $clsfn);
		} else {
			$clsfn = array($clsfn);
		}

		$cls = (sizeof($clsfn) > 1) ? array_shift($clsfn) : '';
		$fn = implode('.', $clsfn);

		if ($cls) {
			$depot = &static::depot(static::$obj, $cls.'.'.$fn);
		} else {
			$depot = &static::depot($obj, $fn);
		}
		$depot['evt'] = array(
			'context' => &$obj,
			'args' => array_merge(array(&$obj), $argso),//Аргументы которые передаются в callback
		);
		//Если класс, то у непосредственно объекта вообще ничего не храниться

		foreach ($depot['listen'] as &$cal) {
			static::exec($depot, $cal);
		}
	}
	public static function listeng($fn, $callback)
	{
		return static::listen(static::$obj, $fn, $callback);
	}
	public static function listen(&$obj = null, $fn, $callback)
	{
		if (is_null($obj)) $obj = &static::$obj;
		$depot = &static::depot($obj, $fn);
		$depot['listen'][] = $callback;
	}
	public static function wheng($fn, $callback)
	{
		return static::when(static::$obj, $fn, $callback);
	}
	public static function when(&$obj, $fn, $callback)
	{
		if (is_null($obj)) $obj = &static::$obj;
		//depricated, для классов не подходит
		$depot = &static::depot($obj, $fn);
		$cal = function () use (&$depot) {
			foreach ($depot['wait'] as $k => $cal) {
				unset($depot['wait'][$k][0]);//должно удалиться и в listen так как ссылка;

				static::exec($depot, $depot['wait'][$k][1]);
				unset($depot['wait'][$k]);
				break;
			}
		};
		$depot['wait'][] = array(&$cal,&$callback);
		$depot['listen'][] = &$cal;
	}
	public static function waitg($fn, $callback)
	{
		return static::wait(static::$obj, $fn, $callback);
	}
	public static function wait(&$obj, $fn, $callback)
	{
		if (is_null($obj)) $obj = &static::$obj;
		//depricated, для классов не подходит
		$depot = &static::depot($obj, $fn);
		if ($depot['evt']) {
			static::exec($depot, $callback);
		} else {
			$cal = function () use (&$depot) {
				foreach ($depot['wait'] as $k => $cal) {
					unset($depot['wait'][$k][0]);//должно удалиться и в listen так как ссылка;

					static::exec($depot, $depot['wait'][$k][1]);
					unset($depot['wait'][$k]);
					break;
				}
			};

			$depot['wait'][] = array(&$cal,&$callback);
			$depot['listen'][] = &$cal;
		}
	}
	public static function handleg($fn, $callback)
	{
		return static::handle(static::$obj, $fn, $callback);
	}
	public static function handle(&$obj, $fn, &$callback)
	{
		if (is_null($obj)) $obj = &static::$obj;
		//depricated, для классов не подходит
		$depot = static::depot($obj, $fn);
		if ($depot['evt']) {
			static::exec($depot, $callback);
		}
		$depot['listen'][] = $callback;
	}
	public static function unlisteng($fn, $callback)
	{
		return static::unlisten(static::$obj, $fn, $callback);
	}
	public static function unlisten(&$obj, $fn, &$callback)
	{
		if (is_null($obj)) $obj = &static::$obj;
		$depot = static::depot($obj, $fn);
		foreach ($depot['listen'] as &$cal) {
			if ($cal === $callback) {
				unset($cal);
			}
			break;
		}
	}
	public static function &depot(&$obj, $fn)
	{
		if (is_null($obj)) $obj = &static::$obj;
		$n = '__event_depot__';
		if (!isset($obj[$n])) {
			$obj[$n] = array();
		}
		if (!isset($obj[$n][$fn])) {
			$obj[$n][$fn] = array(//При повторном событии этот массив уже будет создан
			'listen' => array(),//Массив всех подписчиков
			'wait' => array(),
			'evt' => null,//Событие ещё не состоялось, обновляется при каждом событии
		);
		}

		return $obj[$n][$fn];
	}
	public static function exec(&$depot, &$callback)
	{
		$r = call_user_func_array($callback, $depot['evt']['args']);
		if (!is_null($r)) {
			$depot['free'] = false;//Метка что событие оборвалось
			return $r;
		}
	}
}