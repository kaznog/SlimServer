<?php
/**
 * アプリケーション起動処理.
 * Slimを起動し、DB接続, Memcached接続などをおこなう.
 */
$settings = require APP_ROOT . "/config/settings.php";
// var_dump($settings);
$app = new \App\App($settings);
$app->name = "App";
$app->mode = $env;

// connection to db
\App\Services\DbClusterService::configure();

// connection to memcached
$app->memcached = \App\Services\MemcachedService::connection();

// HTTP client
$app->httpClient = new \App\Models\HttpClient();