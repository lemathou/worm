<?php Namespace Worm;

/**
 * Requête SELECT en base de données
 */
class db_select extends db_query
{

protected $num_rows;

public function fetch($method=null)
{
	return $this->fetch_row();
}

public function fetch_row()
{
	if (! $this->exec)
		$this->execute();
	
	if (static::$_t_debug)
		$t0 = microtime(true);
	$r = static::sql_fetch_row($this->q);
	if (static::$_t_debug)
		$this->t += microtime(true)-$t0;
	return $r;
}

public function fetch_array()
{
	if (! $this->exec)
		$this->execute();
	
	if (static::$_t_debug)
		$t0 = microtime(true);
	$r = static::sql_fetch_array($this->q);
	if (static::$_t_debug)
		$this->t += microtime(true)-$t0;
	return $r;
}

public function fetch_assoc()
{
	if (! $this->exec)
		$this->execute();
	
	if (static::$_t_debug)
		$t0 = microtime(true);
	$r = static::sql_fetch_assoc($this->q);
	if (static::$_t_debug)
		$this->t += microtime(true)-$t0;
	return $r;
}

public function fetch_object()
{
	if (! $this->exec)
		$this->execute();
	
	if (static::$_t_debug)
		$t0 = microtime(true);
	$r = static::sql_fetch_object($this->q);
	if (static::$_t_debug)
		$this->t += microtime(true)-$t0;
	return $r;
}

public function num_rows()
{
	if (! $this->exec)
		$this->execute();
	
	if (! is_numeric($this->num_rows)) {
		if (static::$_t_debug)
			$t0 = microtime(true);
		$this->num_rows = static::sql_num_rows($this->q);
		if (static::$_t_debug)
			$this->t += microtime(true)-$t0;
	}
	
	return $this->num_rows;
}

public function log($force=false)
{
	return array_merge(parent::log(), array(
		'nb'=>$this->num_rows(),
	));
}

}

