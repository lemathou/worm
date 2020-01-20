<?php Namespace Worm;

/**
 * Requête INSERT en base de données
 */
class db_insert extends db_query
{

protected $insert_id;

public function insert_id()
{
	if (! $this->exec)
		$this->execute();
	
	if (! is_numeric($this->insert_id)) {
		if (static::$_t_debug)
			$t0 = microtime(true);
		$this->insert_id = static::insert_last_id();
		if (static::$_t_debug)
			$this->t += microtime(true)-$t0;
	}
	
	return $this->insert_id;
}

public function log($force=false)
{
	return array_merge(parent::log(), array(
		'i'=>$this->insert_id(),
	));
}

}

