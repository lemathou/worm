<?php Namespace Worm;

abstract class db_abstract
{

protected static $_c;
protected static $_log = array();
protected static $_log_n = 0;
protected static $_log_debug = true;
protected static $_t_debug = true;

public static function __init()
{
	static::connect();
}

public static function _t()
{
	$t = 0;
	foreach(static::$_log as $q) {
		
	}
}

public static function _log()
{
	foreach(static::$_log as $q) {
		var_dump($q);
	}
}

public static function connect($force=false)
{
	if (! is_null(static::$_c) && ! $force)
		return;
	
	static::$_c = static::sql_connect(DB_HOST, DB_USER, DB_PASS);
	static::database_select(DB_BASE);
	static::charset('UTF8');
}

protected static function charset($charset)
{
	// Overload
}

protected static function sql_connect($host, $username, $password)
{
	// overload
}

public static function sql_disconnect()
{
	// Overload
}

protected static function database_select($name)
{
	// overload
}

/* Data Queries */

public static function forge($sql)
{
	return static::query($sql);
}

public static function query($sql)
{
	return new db_query($sql);
}

public static function select($sql)
{
	return new db_select($sql);
}

public static function count($sql)
{
	return static::select($sql);
}

public static function insert($sql)
{
	return new db_insert($sql);
}

public static function update($sql)
{
	return new db_update($sql);
}

public static function delete($sql)
{
	$q = new db_delete($sql);
	return $q;
}

}

