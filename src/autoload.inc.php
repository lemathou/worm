<?php //Namespace Worm;

const WORM_APP_MODEL_PREFIX = 'App\\model_';

function __autoload($classname)
{
	//echo '<p>loading : '.$classname.'</p>';
	if (substr($classname, 0, 5)=='Worm\\')
		worm_autoloader($classname);
	if (substr($classname, 0, strlen(WORM_APP_MODEL_PREFIX))==WORM_APP_MODEL_PREFIX)
		model_autoloader($classname);
}

function worm_autoloader($classname)
{
	//echo '<p>loading : '.$classname.'</p>';
	$filename = WORM_PATH.'/classes/'.strtolower(substr($classname, 5)).'.inc.php';
	
	if (! file_exists($filename)) {
		$msg = '<p>Class file '.$classname.' in file '.$filename.' missing</p>';
		throw new Worm\Autoload_Exception($msg);
		return;
	}
	// @todo : ajouter timestamp pour inclusion
	//echo $filename.'<br />';
	include $filename;

	if (method_exists($classname, '__init'))
		$classname::__init();
}

function model_autoloader($classname)
{
	//echo '<p>loading : '.$classname.'</p>';
	$filename = APP_PATH.'/classes/model/'.strtolower(substr($classname, strlen(WORM_APP_MODEL_PREFIX))).'.php';
	
	if (! file_exists($filename)) {
		$msg = '<p>Class file '.$classname.' in file '.$filename.' missing</p>';
		throw new Worm\Autoload_Exception($msg);
		return;
	}
	// @todo : ajouter timestamp pour inclusion
	//echo $filename.'<br />';
	include $filename;

	if (method_exists($classname, '__init'))
		$classname::__init();
}

//include 'cache.inc.php';
//cache::__init();

//include 'db.inc.php';
//db::__init();

//include 'orm.inc.php';
//model_manager::__init();

\Worm\Core::__init();

