<?php

$t0 = microtime(true);

if (! defined('CONFIG_PATH'))
	define('CONFIG_PATH', realpath(APP_PATH.'/config'));
if (! defined('CACHE_PATH'))
	define('CACHE_PATH', realpath(VAR_PATH.'/cache'));

include WORM_PATH.'/autoload.inc.php';

