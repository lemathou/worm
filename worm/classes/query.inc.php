<?php namespace Worm;

/**
 * Requêtes complexes
 */
class Query extends Core
{

protected $name;
protected $fields;
protected $where = array();
protected $order = 'id';
protected $limit;

public static function forge($name, $params=array())
{
	return new static($name, $params);
}

public function __construct($name, $params=array())
{
	if (!is_string($name) || !isset(static::$__models[$name]))
		return false;
	$this->name = $name;
}

public function where($params)
{
	$this->where = $params;
	return $this;
}

public function order($params)
{
	$this->order = $params;
	return $this;
}

public function limit($params)
{
	$this->limit = $params;
	return $this;
}

public function find($params)
{
	
}

public function get($params=null)
{
	if ($params=='first'){
		return $this->get_first();
	}
	elseif ($params=='last'){
		return $this->get_last();
	}
	else{ //$params=='all'
		return $this->get_all();
	}
	//return $this;
}

public function get_first()
{
	list($id) = static::__db_search($this->name, $this->where, $this->order, 1);
	
	$classname = 'App\\model_'.$this->name;
	return $classname::find($id);
}

public function get_last()
{
	//
}

public function get_all()
{
	$classname = 'App\\model_'.$this->name;
	return $classname::search($this->where, $this->order, $this->limit);
	//
}

public function count()
{
	return static::__db_count($this->name, $this->where, $this->limit);
	//return $this;
}

}

