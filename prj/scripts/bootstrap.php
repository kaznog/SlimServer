<?php

if (!defined("APP_ROOT")) {
	define("APP_ROOT", __DIR__.'/..');
}
require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/app/bootstrap.php';

$app->batch = true;
\Middlewares\UseMonolog::execute();