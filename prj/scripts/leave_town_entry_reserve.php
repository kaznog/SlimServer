<?php
// タウン入室予約期限を過ぎた予約分減算してを入室可能数回復する

chdir("/var/www/current/scripts");
if (!defined("APP_ROOT")) {
	define("APP_ROOT", __DIR__.'/..');
}

require APP_ROOT . '/scripts/bootstrap.php';

use \App\Services\MultiPlayService;
use \App\App;

echo "start " . __FILE__ . " supervisord\n";

while(true) {
	MultiPlayService::leaveTownEntryReserve();
	sleep(60);	// 1分待機
}