<?php

define('WEB_PATH', realpath(dirname(__FILE__)));
define('APP_PATH', realpath(WEB_PATH.'/../app'));
define('WORM_PATH', realpath(WEB_PATH.'/../worm'));
define('VAR_PATH', realpath(WEB_PATH.'/../var'));

include WORM_PATH.'/bootstrap.inc.php';

