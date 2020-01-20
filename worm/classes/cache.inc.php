<?php namespace Worm;

class cache
{

protected static $c;
protected static $enabled = false;

public static function __init()
{
	if (! static::$enabled)
		return;
	
	static::$c = new Memcached();
	static::$c->addServer('localhost', 11211);
}

public static function set($name, $value, $expiration=null)
{
	if (! static::$enabled)
		return;
	
	//return static::$c->set($name, $value, $expiration);
	return apc_store($name, $value, $expiration);
}

public static function get($name)
{
	if (! static::$enabled)
		return;
	
	//return static::$c->get($name);
	return apc_fetch($name);
}

public static function rm($name)
{
	if (! static::$enabled)
		return;
	
	//return static::$c->delete($name);
	return apc_delete($name);

}

public static function search($names)
{
	if (! static::$enabled)
		return;
	
	return apc_fetch($names);
}

}

//cache::__init();

