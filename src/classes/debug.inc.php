<?php namespace Worm;

class Debug
{

protected static $lcat = array();
protected static $l = array();

public static function add($cat, $function, $info)
{
	if (! isset(static::$l[$cat])) {
		static::$l[$cat] = array();
		$n = count(static::$l);
		static::$l[$n] = array(
			'ts' => microtime(true),
			'cat' => $cat,
			'function' => $function,
			'info' => $info,
		);
		static::$lcat[$cat][] =& static::$l[$n];
	}
}

public static function get($cat=NULL)
{
	return !empty($cat) ?static::$lcat[$cat] :static::$l;
}

public static function get_all()
{
	return static::get();
}

}
