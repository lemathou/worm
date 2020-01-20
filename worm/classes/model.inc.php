<?php namespace Worm;

/**
 * Base des modèles métier
 */
abstract class Model extends Core
{

/* Propriétés de classe */

/* @var string log des mises à jour du modèle */
protected static $_db_log = true;
/* @var string Nom raccourcis */
protected static $_name = '';
/* @var string nom de table */
protected static $_dbname = '';
/* Spécifications des champs du modèle */
protected static $_fields = array();
/* Liste des champs "résumé" du modèle */
protected static $_summary = array();

/* @var int Nombre appromimatif d'éléments => en résulte un affichage spécifique des formulaires de sélection, etc. */
protected static $_count_avg = null;
protected static $_select_form = null;
/* @var string Affichage par défaut (doit permettre d'afficher abstraction faite de PHP donc pas de __tostring()) */
protected static $_tostring = '{_name}#{id}';


/* Class initialisation */

/**
 * Regen class cache
 * Store class definitions in global classes cache
 */
protected static function _cache_regen()
{
	// Model general definition
	static::$__models[static::$_name] = array(
		'db_name' => &static::$_dbname,
	);
	
	// Link field definitions
	static::$__fields[static::$_name] =& static::$_fields;
	static::$__summary[static::$_name] =& static::$_summary;
	
	// Initialize cache
	static::$_objects = array();
	static::$__objects[static::$_name] =& static::$_objects;
}

/**
 * Initialisation de la classe
 */
public static function __init()
{
	static::debug('__init', 'begin');
	if (! isset(static::$__models[static::$_name]) || get_called_class() == 'Worm\\Model')
		return;
	
	static::debug('__init', 'retrieve cached data');
	// retrieve cached data
	static::$_fields =& static::$__fields[static::$_name];
	static::$_summary =& static::$__summary[static::$_name];
	static::$_objects = array();
	static::$__objects[static::$_name] =& static::$_objects;
}

/**
 * Debugging (log)
 */
protected static function debug($method, $info)
{
	Debug::add('model ('.get_called_class().')', $method, $info);
}


/* Méthodes de classe */

public static function _models()
{
	return static::$__models[static::$_name];
}

public static function _fields()
{
	return static::$_fields;
}

public static function _objects()
{
	return static::$_objects;
}

public static function _forge($data=null)
{
	$object = new static($data);
	return $object;
}

protected static function _forge_updated($data=null)
{
	$object = static::_forge();
	$object->set_updated($data);
	return $object;
}


/* Recherche */


/**
 * Requête complexe
 * @param [] $params
 * @return Qrm\Query
 */
public static function query($params=array())
{
	return new Query(static::$_name, $params);
}

/**
 * Récupérer un objet par son identifiant
 * @param int $id
 * @return Worm\Model
 */
public static function find($id)
{
	$objects =& static::$_objects;
	if (is_numeric($id)){
		if (isset($objects[$id]))
			return $objects[$id];
		elseif (static::$_cache && ($o=cache::get(static::$_name.'_'.$id)))
			return $objects[$id] = $o;
		elseif ($row=static::__db_find_ids(static::$_name, $id))
			return $objects[$id] = static::_forge_updated($row);
	}
	elseif (is_string($id)){
	
	}
}
public static function get($id)
{
	return static::find($id);
}

/**
 * Récupérer un objet au hasard
 * @return Worm\Model
 */
public static function find_rand()
{
	$l = static::search_rand(1);
	return array_pop($l);
}
/**
 * Récupérer une liste d'objets au hasard
 * @return []Worm\Model
 */
public static function search_rand($nb=10)
{
	return static::search(null, 'rand()', $nb);
}

/**
 * Récupérer une liste d'objets par paramètres
 * @param [] $params
 * @param [] $order
 * @param [] $limit
 * @return []Worm\Model
 */
public static function search($params=array(), $order=null, $limit=null)
{
	return static::select_ids(static::__db_search(static::$_name, $params, $order, $limit));
}
public static function select($params=array(), $order=null, $limit=null)
{
	return static::search($params, $order, $limit);
}

/**
 * Récupérer une liste d'objets par une liste d'id
 * @param []int $ids
 * @return []Worm\Model
 */
public static function search_ids($ids)
{
	$list = array();
	$objects =& static::$_objects;
	foreach($ids as $i=>$id) {
		if (empty($id))
			unset($ids[$i]);
		elseif (isset($objects[$id])) {
			$list[$id] = $objects[$id];
			unset($ids[$i]);
		}
		elseif ($o=static::cache_get($id)) {
			$list[$id] = $objects[$id] = $o;
			unset($ids[$i]);
		}
	}
	if (! empty($ids)) {
		foreach(static::__db_find_ids(static::$_name, $ids) as $id=>$row)
			$list[$id] = $objects[$id] = static::_forge_updated($row);
	}
	return $list;
}
public static function select_ids($ids) {
	return static::search_ids($ids);
}

/**
 * Compte les objets
 * @param [] $params
 * @param [] $limit
 * @return int
 */
public static function count($params=null, $limit=null)
{
	return static::__db_count(static::$_name, $params, $limit);
}


/* Préchargement */


/**
 * Charger en cache une liste d'objets par paramètres
 * @param [] $params
 * @return void
 */
public static function load($params=array())
{
	static::load_ids(static::_db_select($params));
}

/**
 * Charger une liste d'objets par une liste d'ids
 * @param []int $ids
 * @return void
 */
public static function load_ids($ids)
{
	$objects =& static::$_objects;
	foreach($ids as $i=>$id){
		if (isset($objects[$id])){
			unset($ids[$i]);
		}
		elseif (static::$_cache && ($o=cache::get(static::$_name.'_'.$id))){
			$objects[$id] = $o;
			unset($ids[$i]);
		}
	}
	if (! empty($ids)){
		foreach(static::_db_find_ids($ids) as $id=>$row)
			$objects[$id] = static::_forge_updated($row);
	}
}


/* CACHE */


/**
 * Nom d'un objet en cache
 * @param int $id
 * @return string
 */
protected static function _cache_name($id)
{
	return static::$_name.'_'.$id;
}

/**
 * Récupère un objet du cache
 * @param int $id
 * @return Worm\Model|null
 */
protected static function cache_find($id)
{
	if (! static::$_cache)
		return;
	
	return cache::get(static::_cache_name($id));
}
protected static function cache_get($id)
{
	return static::cache_find($id);
}

/**
 * Récupère une liste d'objets du cache
 * @param []int $ids
 * @return []Worm\Model
 */
protected static function cache_search($ids)
{
	if (! static::$_cache)
		return;
	
	$list = array();
	foreach($ids as $id)
		$list[] = static::_cache_name($id);
	return cache::search($list);
}


/* Requêtes en base de données */


/*
 * Gestion des champs en base de donnée
 * @return []
 */
public static function _db_fields()
{
	return static::__db_fields(static::$_name);
}

/**
 * Récupérer les propriétés d'un objet en base de donnée à partir de son id
 * @return []
 */
protected static function _db_find_id($id)
{
	if (!is_numeric($id))
		return;
	
	$list = static::__db_find_ids(static::$_name, array($id));
	if (! empty($list))
		return array_pop($list);
}

/**
 * Récupérer les propriétés d'une liste objet en base de donnée à partir d'une liste d'id
 */
protected static function _db_find_ids($ids)
{
	return static::__db_find_ids(static::$_name, $ids);
}

/**
 * Récupérer une liste d'id d'objets en base de donnée à partir de paramètres
 */
protected static function _db_search($params=array())
{
	return static::__db_search(static::$_name, $params);
}
protected static function _db_select($params=array())
{
	return static::_db_search($params);
}

protected static function _db_insert($data)
{
	return static::__db_insert(static::$_name, $data);
}

protected static function _db_update($params, $data)
{
	return static::__db_update(static::$_name, $params, $data);
}


/* Préchargement */


/**
 * Met à jour les objets dépendant
 */
protected static function _update_dep($id, $values, $values_orig=array())
{
	return static::__update_dep(static::$_name, $id, $values, $values_orig);
}


/*
 * Propriétés d'objet
 */


/* Valeurs natives de l'objet */
/* ID */
protected $id = null;
/* Creation datetime */
protected $ctime = null;
protected $cid;
/* Update datetime */
protected $utime = null;
protected $uid;
/* Dependant Update datetime */
protected $dutime = null;
/* Revision number */
protected $rev = null;

/* Valeurs courantes */
protected $_values = array();
/* Valeurs d'origine (en bdd) */
protected $_values_orig = array();


/*
 * Méthodes d'objet
 */


public function __construct($data=null)
{
	if (is_array($data))
		$this->set($data);
}

/**
 * Fields to serialize
 */
public function __sleep()
{
	return static::$__special_fields + array('_values_orig');
}

/**
 * When retriving serialized
 */
public function __wakeup()
{
	$this->_values = $this->_values_orig;
}

/**
 * When Cloning
 */
public function __clone()
{
	$this->id = null;
	$this->ctime = null;
	$this->cid = null;
	$this->utime = null;
	$this->uid = null;
	$this->dutime = null;
	$this->rev = null;
}

/*
 * Getters
 */

/**
 * Default value
 * @return string
 */
public function __tostring()
{
	return static::$_name.'#'.$this->id;
}

/**
 * Default getter
 * @return mixed
 */
public function __get($name)
{
	if (! is_string($name))
		return;
	if (in_array($name, static::$__special_fields))
		return $this->{$name};
	if (! isset(static::$_fields[$name]))
		return;
	
	$field =& static::$_fields[$name];
	
	if ($field['type']=='object') {
		$model = 'App\\model_'.$field['model'];
		//var_dump($model); var_dump($this->_values[$name.'_id']);
		return $model::find($this->_values[$name]);
	}
	elseif ($field['type']=='object_list') {
		$model = WORM_APP_MODEL_PREFIX.$field['model'];
		if (empty($this->_values[$name]))
			return array();
		return $model::select_ids($this->_values[$name]);
	}
	elseif ($field['type']=='related') {
		if (in_array('store', $field))
			return $this->_values[$name];
		else
			return $this->__get_related($field['relates']);
	}
	elseif ($field['type']=='function') {
		$function = isset($field['function']) ?$field['function'] :$name.'_get';
		if (in_array('store', $field))
			return $this->_values[$name];
		else
			return $this->$function();
	}
	// if tests...
	elseif (array_key_exists($name, $this->_values)) {
		return $this->_values[$name];
	}
}

/**
 * Get a related field
 * @param string $relates
 * @return mixed
 */
public function __get_related($relates)
{
	$e = explode('.', $relates);
	$r = $this;
	while(list($i, $j) = each($e)) {
		if (($r=$r->{$j}) == null)
			break;
	}
	return $r;
}

/**
 * Get updated field values
 * @return []mixed
 */
public function updated_values()
{
	static::debug('updated_values', 'begin');
	$values = array();
	foreach($this->_values as $name=>$value) {
		// @todo gérér les champs complexes, partiellement extraits, etc.
		if ((! array_key_exists($name, $this->_values_orig)) || $this->_values_orig[$name] !== $value)
			$values[$name] = $value;
	}
	//var_dump($this->_values_orig); var_dump($this->_values); var_dump($values);
	static::debug('updated_values', 'end');
	return $values;
}

/**
 * Get cache name
 * @return string
 */
protected function cache_name()
{
	return static::_cache_name($this->id);
}

/*
 * Setters
 */

protected function set($data)
{
	if (! is_array($data))
		return;
	
	foreach($data as $name=>$value)
		$this->__set($name, $value);
}

protected function set_updated($data)
{
	foreach($data as $name=>$value){
		if (in_array($name, static::$__special_fields))
			$this->$name = $value;
		else
			$this->_values[$name] = $value;
	}
	$this->updated();
}

public function __set($name, $value)
{
	if (! is_string($name))
		return;
	// Special field
	if (in_array($name, static::$__special_fields))
		return;
	// Undefined
	if (! isset(static::$_fields[$name]))
		return;
	// Read Only
	if (in_array('ro', static::$_fields[$name]))
		return;
	
	$field =& static::$_fields[$name];
	
	if ($field['type']=='object') {
		$this->_values[$name] = $value;
	}
	else {
		$this->_values[$name] = $value;
	}
	//var_dump($this->_values);
}

/**
 * Set the object in an updated state (bdd==object)
 */
protected function updated()
{
	foreach($this->_values as $name=>$value)
		$this->_values_orig[$name] = $value;
}

/**
 * Update related fields
 * @param string $name
 * @param mixed $value
 * @param mixed $value_old
 */
protected function related_update($name, $value, $value_old)
{
	static::debug('related_update', 'Starting : '.$name);
	$field =& static::$_fields[$name];
	if (! isset($field['related']))
		return;
	
	foreach($field['related'] as $r) {
		static::debug('related_update', 'rule '.$name.' : model '.$r['model'].' ID#'.$this->id);
		//var_dump($r);
		$model = WORM_APP_MODEL_PREFIX.$r['model'];
		$fieldname = $r['field'];
		$ids = array($this->id);
		//echo "<p>IDS</p>";
		//var_dump($ids);
		// Look for objects to update
		foreach($r['rev'] as $i) {
			if (count($ids)) {
				$m = WORM_APP_MODEL_PREFIX.$i[0];
				$params = array('`'.$i[1].'_id` IN ('.implode(', ', $ids).')');
				$ids = $m::_db_search($params);
				//var_dump($params); echo "<p>IDS related from $m</p>"; var_dump($ids);
			}
			else {
				return;
			}
		}
		// Look for final value to update
		//$r2 = explode('.', $r['relates']);
		if (count($r['relates'])>1) {
			//list($m, $lastfield) = array_pop($r2);
			//$o = $m
			foreach($r['relates'] as $i) {
				//echo '<p>Value : '.$value.'</p>'; echo '<p>value change :</p>'; var_dump($i);
				if ($i[0] && is_numeric($value)) {
					$m = WORM_APP_MODEL_PREFIX.$i[0];
					$value = $m::find($value);
				}
				elseif ($value === null) {
					break;
				}
				else {
					$value = $value->{$i[1]};
				}
			}
		}
		// Finally udapte...
		echo '<p>IDS related :</p>';var_dump($ids);
		//echo '<p>IDS :</p>'; var_dump($ids);
		foreach($model::search_ids($ids) as $o) {
			static::debug('related_update', 'update related '.$model.' ID#'.$o->id.' : '.$fieldname.' = '.$value);
			//var_dump($o);
			$o->{$fieldname} = $value;
			$o->save_prepare();
		}
	}
}

/**
 * Update related calculated fields
 * @param string $name
 * @param mixed $value
 * @param mixed $value_old
 */
protected function calculated_update($name, $value, $value_old)
{
	static::debug('calculated_update', 'Starting : '.$name.' : '.$value_old.' => '.$value);
	$field =& static::$_fields[$name];
	
	if (! isset($field['calculated']))
		return;
	
	foreach($field['calculated'] as $r) {
		//var_dump($r); echo '<br />';
		$model = WORM_APP_MODEL_PREFIX.$r['model'];
		$fieldname = $r['field'];
		// Sélection
		$select_ids_fct = $r['select_ids_fct'];
		// Différentes fonctions de sélection
		if (false) {
			//echo '<p>ID # '.$this->id.'</p>';
			$ids = array($this->id);
		}
		// many2one : objets correspondant aux valeurs changées
		else {
			$ids = array($value_old, $value);
		}
		//var_dump($ids);
		
		// Mise à jour
		$update_fct = $r['update_fct'];
		
		// @todo liste de cas possibles
		if (false) {
		}
		// min
		elseif ($update_fct=='min') {
		}
		// max
		elseif ($update_fct=='max') {
		}
		// count ($update_fct=='count')
		else {
			// Valeurs mises à jour
			// nb-- pour $value_old
			// $nb++ pour value
			
			// Objets mis à jour
			foreach($model::search_ids($ids) as $o) {
				$nb = static::count(array($name.'_id=\''.$o->id.'\''));
				$o->{$fieldname} = $nb;
				$o->save_prepare();
				static::debug('calculated_update', 'Trigger calculated Update for '.$model.' ID#'.$o->id);
				//var_dump($o);
			}
		}
	}
}

/**
 * Reset / Clean / Return to original state
 */
public function reset()
{
	foreach($this->_values_orig as $name=>$value)
		$this->_values[$name] = $value;
}

public function clean()
{
	return $this->reset();
}

/**
 * Save the object
 */
public function save($values=null)
{
	$transaction = false;
	if (! static::$__transaction) {
		static::$__transaction = $transaction = true;
		static::$__save_list = array();
	}
	$this->save_prepare($values);
	
	if ($transaction) {
		static::__save_execute();
	}
}

protected function save_prepare($values=null)
{
	if ( in_array($this, static::$__save_list)) {
		// @todo : exception : déjà mise à jour !
		static::debug('save_prepare', 'ID#'.$this->id.' already commited for update');
		//return;
	}
	// @todo : mise à jour 2 fois dans ce cas... pas top !
	// if (! $this->id)
	// 	$this->insert($values);
	
	if (is_array($values) && !empty($values))
		$this->set($values);
	
	static::debug('save_prepare', 'ID#'.$this->id.' Preparing saving...');
	$values = $this->updated_values();
	if (empty($values)) {
		//echo '<p>Nothing to save</p>';
		return;
	}
	//var_dump($values);

	if ( in_array($this, static::$__save_list)) {
		static::$__save_list[] = $this;
	}
		
	foreach($values as $name=>$value) {
		$this->related_update($name, $value, isset($this->_values_orig[$name]) ?$this->_values_orig[$name] :null);
		//$this->calculated_update($name, $value, isset($this->_values_orig[$name]) ?$this->_values_orig[$name] :null);
	}
}

public function persist()
{
	if ($this->id)
		return $this->update();
	else
		return $this->insert();
}

/**
 * Insert the new object
 */
protected function insert()
{
	$values = $this->updated_values();
	if($id = static::_db_insert($values)) {
		$this->id = $id;
		$this->update_dep($values);
		$this->updated();
		static::$__objects[static::$_name][$id] = $this;
		$this->cache_save();
		return true;
	}
	
	return false;
}

/**
 * Update the existing object
 */
protected function update()
{
	$values = $this->updated_values();
	if (static::_db_update($this->id, $values)) {
		$this->update_dep($values, $this->_values_orig);
		$this->updated();
		$this->cache_save();
		return true;
	}
	
	return false;
}

protected function update_dep($values, $values_orig=array())
{
	return static::_update_dep($this->id, $values, $values_orig=array());
}

/**
 * Save to cache
 */
protected function cache_save()
{
	if (! static::$_cache)
		return;

	cache::set($this->cache_name(), $this);
}

/**
 * Duplicate
 */
protected function duplicate()
{

}

}

