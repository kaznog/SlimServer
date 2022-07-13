<?php

chdir("/var/www/current/scripts");
if (!defined("APP_ROOT")) {
	define("APP_ROOT", __DIR__.'/..');
}

require APP_ROOT . '/scripts/bootstrap.php';

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use \App\Services\MultiPlayService;
use \App\App;

echo "start " . __FILE__ . " supervisord\n";

$amqpconf = $app->getContainer()['settings']['ampq.host'];

$connection = new AMQPConnection($amqpconf[0], $amqpconf[1], $amqpconf[2], $amqpconf[3]);
$channel = $connection->channel();
$channel->queue_declare('unset_townentry_reserve', false, false, false, false);

$callback = function ($recv) {
	$app = App::getInstance();
	$app->logger->addNotice("AMQP UnsetTownEntryReserve Reciever : Recieved : " . $recv->body);
	echo " Recieved " . $recv->body, "\n";
	$res = json_decode($recv->body, true);
	if ($res['playerId'] != '') {
		$app->playerId = $res['playerId'];
		if (!MultiPlayService::unsetTownEntryReserve()) {
			$app->logger->addNotice("AMQP UnsetTownEntryReserve Reciever : MultiPlayService::UnsetTownEntryReserve Failure : " . $recv->body);
		}
	}
};

$channel->basic_consume('unset_townentry_reserve', '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
	$channel->wait();
}