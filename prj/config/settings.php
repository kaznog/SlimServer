<?php

// environment
$env = getenv('APP_ENV');
$settings = require APP_ROOT . "/config/settings/{$env}.php";
// $settings['settings']['determineRouteBeforeAppMiddleware'] = true;
$settings['settings']['db.sharding_configs'] = require APP_ROOT . "/config/settings/db_sharding_configs.php";
return $settings;

?>