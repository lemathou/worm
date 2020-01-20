<?php namespace Worm;

/**
 * Manager contenant l'ensemble des spécifications des modèles métier
 */
class Core
{

/* Connection au moteur de base de données */
protected static $__db;

protected static $__cache_loaded = false;

/* Stockage global des spécifications des modèles */
protected static $__models = array();
/* Stockage global des spécifications des champs des modèles */
protected static $__fields = array();
/* Summary */
protected static $__summary = array();

/* Champs spéciaux */
protected static $__special_fields = array('id', 'ctime', 'cid', 'utime', 'uid', 'dutime', 'rev');
//protected static $__special_fields = array('id', 'ctime', 'utime', 'rev');
/* Champs de type related */
protected static $__related_fields = array('related', 'function', 'calculated', 'object_list');

/* Cache global d'objets */
protected static $__objects = array();

/* Transaction */
protected static $__transaction = false;
protected static $__save_list = array();

/* Debug des opérations en base de données */
protected static $_db_debug;

public static function __init()
{
	// Generate related fields
	if (! static::$__cache_loaded)
		static::__cache_load();
	
	//db::__init();
}

/**
 * Used to make the class alive
 */
public static function _hup()
{
	// ouahaha
}

/**
 * Debugging (log)
 */
protected static function debug($method, $info)
{
	Debug::add('core ('.get_called_class().')', $method, $info);
}


/* Cache de modèles */


/**
 * Load model cache
 */
protected static function __cache_load()
{
	static::$__cache_loaded = true;
	if (file_exists($filename=CACHE_PATH.'/models.json'))
		list(static::$__models, static::$__fields) = json_decode(file_get_contents($filename), true);
	else
		static::__cache_regen();
}

/**
 * Regen model cache
 */
protected static function __cache_regen()
{
	$fp = opendir(APP_PATH.'/classes/model');
	// Génération du cache pour chaque model
	while ($file=readdir($fp)) if (substr($file, 0, 1)!='.') {
		 //echo $file.'<br />';
		$classname = WORM_APP_MODEL_PREFIX.substr($file, 0, -4);
		$classname::_cache_regen();
	}
	
	// Parse fields
	foreach (static::$__fields as $model=>&$fields) {
		// Clean fields definition
		static::__fields_clean($fields);
		// Parse related fields to store
		foreach ($fields as $name=>&$field) {
			if (! is_array($field))
				continue;
			if (! isset($field['type'])) {
				static::debug('__cache_regen', 'No field type for '.$model.'.'.$name);
				continue;
			}
			// Analyse related field
			if ($field['type']=='related' && in_array('store', $field)) {
				static::debug('__cache_regen', 'Related found : '.$model.'.'.$name.' relating '.$field['relates']);
				$related = static::__related_analyse($model, $name, $field['relates']);
				// @todo : array_merge_recursive
				foreach ($related as $m=>$i) {
					foreach ($i as $f=>$j) {
						foreach ($j as $r) {
							static::$__fields[$m][$f]['related'][] = $r;
						}
					}
				}
			}
			// Analyse calculated field
			elseif ($field['type']=='calculated' && in_array('store', $field)) {
				// Every relates
				foreach ($field['relates'] as $relates) {
					$related = static::__calculated_analyse($model, $name, $relates);
					static::debug('__cache_regen', 'Related found : '.$model.'.'.$name.' relating '.$relates);
					// @todo : array_merge_recursive
					foreach ($related as $m=>$i) {
						foreach ($i as $f=>$j) {
							foreach ($j as $r) {
								static::$__fields[$m][$f]['calculated'][] = $r;
							}
						}
					}
				}
			} /* end fields type related and calculated analysis */
		} /* end foreach fields */
	}
	file_put_contents(CACHE_PATH.'/models.json', json_encode(array(static::$__models, static::$__fields)));
}

protected static function __fields_clean(&$fields)
{
	foreach ($fields as $name=>&$field) {
		if (is_numeric($name)) {
			unset($fields[$name]);
			$name = $field;
			$field = array();
			$fields[$name] =& $field;
		}
		
		if (! isset($field['type']))
			$field['type'] = '';
	}
}

protected static function __related_analyse($model, $name, $relates)
{
	$fields = static::$__fields[$model];
	$field = $fields[$name];
	
	$related = array();
	
	//echo '<p>Related found :</p>';
	$r = explode('.', $field['relates']);
	var_dump($r);

	$r2 = array();
	/* $m current model having related */
	$m = $model;
	/* $f fields of current model */
	$f =& $fields;
	foreach ($r as $i=>$j) {
		//echo '<p>Related : '.$m.' -> '.$j.' Analysing...</p>';
		if (! isset($f[$j])) {
			//echo '<p>Related : '.$m.' -> '.$j.' does not exists !</p>';
			break;
		}
	
		$m2 = isset($f[$j]['model']) ?$f[$j]['model'] :null;
		//echo '<p>Model : '.$m2.'</p>';
	
		$r2[] = array($m2, $j);
	
		if (! isset($f[$j]['model'])) {
			//echo '<p>Related : '.$m.' - '.$j.' is not a model</p>';
			break;
		}
		$m = $f[$j]['model'];
		$f =& static::$__fields[$m];
	}

	$r3 = array();
	/* $m current model having related */
	$m = $model;
	/* $f fields of current model */
	$f =& $fields;
	foreach ($r as $i=>$j) {
		if (! isset($f[$j])) {
			//echo '<p>Related : '.$m.' - '.$j.' does not exists !</p>';
			break;
		}
	
		$related[$m][$j][] = array('model'=>$model, 'field'=>$name, 'relates'=>$r2, 'rev'=>array_reverse($r3));
		
		// test if we can go deeper in $m
		if (! isset($f[$j]['model'])) {
			//echo '<p>Related : '.$m.' - '.$j.' is not a model</p>';
			break;
		}
	
		// on remonte : le champ $j du modèle $m
		array_shift($r2);
		$r3[] = array($m, $j);
		$m = $f[$j]['model'];
		$f =& static::$__fields[$m];
	}
	
	return $related;
}

protected static function __calculated_analyse($model, $name, $relates)
{
	$fields = static::$__fields[$model];
	$field = $fields[$name];
	
	if (is_array($relates)) {
		$related = array(
			$relates['model']=>array(
				$relates['field']=>array(
					array(
						'model' => $model,
						'field' => $name,
						'select_ids_fct' => isset($relates['select_ids_fct']) ?$relates['select_ids_fct'] :'',
						'update_fct' => isset($relates['update_fct']) ?$relates['update_fct'] :'',
					),
				),
			),
		);
		return $related;
	}

	return array();
}

/* Données */


public static function __models()
{
	return static::$__models;
}

public static function __fields()
{
	return static::$__fields;
}

public static function __objects()
{
	return static::$__objects;
}


/* Gestion des dépendances */


/**
 * Met à jour les objets dépendant
 * @param string $modelname
 * @param int $id ID de l'objet
 * @param [] $values New values
 * @param [] $values_orig Old values
 */
protected static function __update_dep($modelname, $id, $values, $values_orig)
{
	$fields =& static::$__fields[$modelname];
	foreach($values as $name=>$value) {
		$field = $fields[$name];
		if ($field['type']=='object') {
			if (isset($values_orig[$name]) && is_numeric($value_orig=$values_orig[$name])) {
				if (isset(static::$__objects[$field['model']][$value_orig])) {
					$o = static::$__objects[$field['model']][$value_orig];
					$k = array_keys($o->_values[$field['rev']], $id);
					foreach($k as $i)
						unset($o->_values[$field['rev']][$i]);
					$o->updated();
					if (static::$_cache)
						cache::set($field['model'].'_'.$value_orig, $o);
				}
			}
			if (is_numeric($value)) {
				if (isset(static::$__objects[$field['model']][$value])) {
					$o = static::$__objects[$field['model']][$value];
					$o->_values[$field['rev']][] = $id;
					$o->updated();
					if (static::$_cache)
						cache::set($field['model'].'_'.$value, $o);
				}
			}
		}
		elseif ($field['type']=='object_list') {
		}
	}
}


/* Base de données */

/**
 * Renvoie l'association des noms de champ propres
 * entre le modèle et la base de données
 * @param string $modelname
 * @return []string
 */
public static function __db_fields($modelname)
{
	$fields = array();
	
	foreach(static::$__fields[$modelname] as $name=>$field) {
		if ((! in_array($field['type'], static::$__related_fields)) || in_array('store', $field) || ! empty($field['store'])) {
			if (isset($field['db_fieldname']))
				$fields[$name] = $field['db_fieldname'];
			elseif ($field['type']=='object')
				$fields[$name] = $name.'_id';
			else
				$fields[$name] = $name;
		}
	}
	return $fields;
}


/**
 * Récupérer les propriétés d'une liste objet en base de donnée à partir d'une liste d'id
 *
 * @param string $modelname
 * @param []int $ids
 * @return []
 */
protected static function __db_find_ids($modelname, $ids)
{
	if (is_numeric($ids))
		$ids = array($ids);
	elseif(!is_array($ids))
		return;
	$dbname = static::$__models[$modelname]['db_name'];	
	$fields = static::$__fields[$modelname];
	
	$fields_own_sql = static::__db_fields($modelname);
	//var_dump($fields_own_sql);
	$select_sql = static::$__special_fields;
	foreach($fields_own_sql as $i=>$j) {
		$select_sql[] = ($i!=$j) ?$j.' as '.$i :$i;
	}
	$sql = 'SELECT '.implode(', ', $select_sql).' FROM `'.$dbname.'` WHERE `id` IN ('.implode(', ', $ids).')';
	$q = db::select($sql);
	$list = array();
	while ($row=$q->fetch_assoc()){
		$list[$row['id']] = $row;
	}
	foreach($fields as $name=>$field){
		if ($field['type']=='object_list'){
			// @todo db_fieldname
			$sql = 'SELECT `id`, `'.$field['rev'].'_id` ref_id FROM `'.$field['model'].'` WHERE `'.$field['rev'].'_id` IN ('.implode(', ', $ids).')';
			$q = db::select($sql);
			while (list($id, $ref_id)=$q->fetch_row()){
				$list[$ref_id][$name][] = $id;
			}
		}
	}
	return $list;
}

/**
 * Récupérer une liste d'id d'objets en base de donnée à partir de paramètres
 *
 * @param string $modelname
 * @param [] $params
 * @param [] $order
 * @param [] $limit
 * @return []int
 */
protected static function __db_search($modelname, $params=array(), $order=null, $limit=null)
{
	if (! is_array($params))
		$params = array();
	
	$sql = 'SELECT `id`, `rev`, `utime` FROM `'.$modelname.'`'
		.(!empty($params) ?' WHERE '.implode(' AND ', $params) : '')
		.(!empty($order) ?' ORDER BY '.$order :'')
		.(!empty($limit) ?' LIMIT '.$limit :'');
	$q = db::select($sql);
	$list = array();
	while($row=$q->fetch_row()){
		//$list[$id] = $row;
		$list[] = $row[0];
	}
	return $list;
}

/**
 * Insère les données d'un objet
 *
 * @param string $modelname
 * @param []mixed $data
 * @return int|false
 */
protected static function __db_insert($modelname, $data)
{
	if (! is_array($data))
		return;
	
	$f = array('ctime', 'utime');
	$v = array($t=time(), $t);
	
	$fields_own_sql = static::__db_fields($modelname);
	foreach($data as $name=>$value) {
		if (isset($fields_own_sql[$name])) {
			$f[] = '`'.$fields_own_sql[$name].'`';
			$v[] = is_null($value) ?'NULL' :'"'.db::string_escape($value).'"';
		}
	}
	$sql = 'INSERT INTO `'.$modelname.'` ('.implode(', ', $f).') VALUES ('.implode(', ', $v).')';
	$q = db::insert($sql);
	return $q->insert_id();
}

/**
 * Met à jour les données d'un objet
 *
 * @param string $modelname
 * @param [] $params
 * @param []mixed $data
 */
protected static function __db_update($modelname, $params, $data)
{
	if (! is_array($data))
		return;
	if (is_numeric($params))
		$p = '`id`='.$params;
	else
		return;
	
	$u = array('`utime`='.time(), 'rev=rev+1');
	$fields_own_sql = static::__db_fields($modelname);
	foreach($data as $name=>$value)
		$u[] = '`'.$fields_own_sql[$name].'`='.(is_null($value) ?'NULL' :'"'.db::string_escape($value).'"');
	$sql = 'UPDATE `'.$modelname.'` SET '.implode(', ', $u).' WHERE '.$p;
	$q = db::update($sql);
	return $q->affected_rows() > 0 ?true :false;
}

/**
 * Comptage
 *
 * @param string $modelname
 * @param [] $params
 * @param [] $limit
 */
protected static function __db_count($modelname, $params=null, $limit=null)
{
	$sql = 'SELECT COUNT(*) FROM `'.$modelname.'`'
		.(!empty($params) ?' WHERE '.implode(' AND ', $params) : '')
		.(!empty($limit) ?' LIMIT '.$limit :'');
	$q = db::count($sql);
	list($nb) = $q->fetch_row();
	return $nb;
}

/**
 * Suppression
 *
 * @param string $modelname
 * @param [] $params
 * @param [] $limit
 */
protected static function __db_delete($modelname, $params=null, $limit=null)
{
	$sql = 'DELETE FROM `'.$modelname.'`'
		.(!empty($params) ?' WHERE '.implode(' AND ', $params) : '')
		.(!empty($limit) ?' LIMIT '.$limit :'');
	$q = db::delete($sql);;
	return $q->affected_rows() > 0 ?true :false;
}

/* transaction */

protected static function __save_execute()
{
	static::debug('save_execute', 'begin');
	//echo '<pre>'; var_dump(static::$__save_list); echo '</pre>';
	foreach(static::$__save_list as $o) {
		static::debug('save_execute', get_class($o).'#'.$o->id);
		$o->persist();
	}
	static::$__transaction = false;
	static::debug('save_execute', 'end');
}

}

