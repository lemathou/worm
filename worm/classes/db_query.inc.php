<?php Namespace Worm;

/**
 * Requête générique en base de données
 */
class db_query extends db
{

/* Query id */
protected $n;
protected $sql;
/* Query executed */
protected $exec;
protected $error;
/* Spent time */
protected $t0;
protected $t1;
protected $t;

/* Encapsulated db engine query */
protected $q;

public function __construct($sql, $execute=true)
{
	$this->n = static::$_log_n++;
	static::$_log[$this->n] = $this;
	$this->sql = $sql;
	static::debug('__construct', $sql);
	if ($execute)
		$this->execute();
}

public function execute()
{
	if ($this->exec) {
		// Already executed exception
		return;
	}
	$this->exec = true;
	
	if (static::$_t_debug)
		$this->t0 = microtime(true);
	$this->q = static::sql_query($this->sql);
	if (static::$_log_debug)
		$this->log();
	if (static::$_t_debug) {
		$this->t1 = microtime(true);
		$this->t += $this->t1-$this->t0;
	}
}

public function error()
{
	if (! $this->exec)
		$this->execute();
	
	if (! is_string($this->error))
		$this->error = static::error_last();
	
	return $this->error;
}

public function log($force=false)
{
	if (! $this->exec)
		$this->execute();
	
	return array(
		'sql'=>$this->sql,
		'error'=>$this->error,
	);
}

}

