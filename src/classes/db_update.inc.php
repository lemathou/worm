<?php Namespace Worm;

/**
 * Requête UPDATE en base de données
 */
class db_update extends db_query
{

protected $affected_rows;

public function affected_rows()
{
	if (! $this->exec)
		$this->execute();
	
	if (! is_numeric($this->affected_rows)) {
		if (static::$_t_debug)
			$t0 = microtime(true);
		$this->affected_rows = static::affected_rows_last();
		if (static::$_t_debug)
			$this->t += microtime(true)-$t0;
	}
	
	return $this->affected_rows;
}

public function log($force=false)
{
	return array_merge(parent::log(), array(
		'u'=>$this->affected_rows(),
	));
}

}

