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
$channel->queue_declare('disconnected', false, false, false, false);

$callback = function ($recv) {
	$now_date = date('Y-m-d H:i:s', strtotime('now'));
	$app = App::getInstance();
	$app->logger->addNotice("AMQP Disconnected Reciever : Recieved : " . $recv->body);
	echo $now_date . " Recieved " . $recv->body, "\n";
	$res = json_decode($recv->body, true);
	if ($res['townId'] != '') {
		if (!MultiPlayService::leaveTownForAMQPRecieve($res['townId'])) {
			$app->logger->addNotice("AMQP Disconnected Reciever : MultiPlayService::leaveTownForAMQPRecieve Failure : " . $recv->body);
			echo "AMQP Disconnected Reciever : MultiPlayService::leaveTownForAMQPRecieve Failure : " . $recv->body;
		}
	}
};

$channel->basic_consume('disconnected', '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
	$channel->wait();
}