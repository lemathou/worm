<?php Namespace Worm;

/**
 * Requête DELETE en base de données
 */
class db_delete extends db_update
{

public function log($force=false)
{
	return array_merge(parent::log(), array(
		'd'=>$this->affected_rows(),
	));
}

}

