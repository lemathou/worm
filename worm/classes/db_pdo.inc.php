<?php Namespace Worm;

/**
 * Base de données MySQL PDO
 */
class db extends db_abstract
{

protected static function debug($method, $info)
{
	Debug::add('db ('.get_called_class().')', $method, $info);
}

protected static function sql_connect($host, $username, $password, $dbname=null)
{
	return new PDO('mysql:host='.$host.';dbname='.$dbname, $username, $password);
}

protected static function charset($charset)
{
	static::sql_query('SET NAMES '.$charset);
}

protected static function database_select($name)
{
	static::sql_query('USE '.$name);
}

/* DB methods */

public static function error_last()
{
	//static::$_c->
	//return mysql_error(static::$_c);
}

public static function insert_last_id()
{
	
	//return mysql_insert_id(static::$_c);
}

public static function affected_rows_last()
{
	return mysql_affected_rows(static::$_c);
}

public static function string_escape($string)
{
	return mysql_real_escape_string($string);
}

/* Structure Queries */

public static function database_create($name, $opt=array())
{

}

public static function table_create($name, $schema, $opt=array())
{

}

public static function table_update($name, $schema, $opt=array())
{

}

public static function table_delete($name)
{

}

public static function table_purge($name)
{

}

public static function table_check($name)
{

}

public static function table_reindex($name, $index_name=null)
{

}

/* Data Queries */

public static function sql_query($sql)
{
	try {
		if (! ($q = static::$_c->query($sql)))
			throw new worm_db_exception('Error executing sql query : '.$sql);
		else
			return $q;
	}
	catch(exception $e) {
		die($e->getMessage());
	}
}

public static function sql_fetch_row($q)
{
	return $q->fetchRow();
}

public static function sql_fetch_array($q)
{
	return $q->fetchArray();
}

public static function sql_fetch_assoc($q)
{
	return $q->fetchAssoc();
}

public static function sql_fetch_object($q)
{
	return $q->fetchObject();
}

public static function sql_num_rows($q)
{
	return $q->numhRows();
}

}

