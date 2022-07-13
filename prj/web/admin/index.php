<?php
// web/admin/index.php
$env = getenv('APP_ENV');
if ($env == "local") {
	define("APP_ROOT", "/var/www/current");
} else {
	define("APP_ROOT", __DIR__ . "/../..");
}

require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/app/bootstrap.php';
require_once APP_ROOT . '/app/admin_dispatcher.php';

$app->run();
?>