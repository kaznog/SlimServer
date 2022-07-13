<?php

use \App\Models\ResultCode;
/**
 * API(/api以下)のディスパッチ設定.
 */

$app->map(['GET', 'POST'],
	'/{controller:[a-z_]+}/{action:[a-z_]+}[/{params:.*}]',
	function ($request, $response, $args) use ($app) {
    $params = $request->getParams();
    $app->logService->logger('admin')->addDebug("headers: " . var_export($request->getHeaders(), true));
    $app->logService->logger('admin')->addDebug("environment: " . var_export($app->getContainer()['environment'], true));

    $params = [$app, $params];
    // $app->logService->logger('admin')->addDebug("params: " . serialize($params));
    $callable = [
        sprintf('App\Controllers\%sController', Utils\Str::camelize($args['controller'])),
        Utils\Str::camelize($args['action'], false)
    ];
    if (is_callable($callable)) {
        call_user_func_array($callable, $params);
    } else {
    	$app->halt(404);
    }
	return $response;    
});

// $app->map(['GET', 'POST'],
// 	'/[?{params:.*}]',
// 	function ($request, $response, $args) use ($app) {
//     $args = $request->getQueryParams();
//     array_unshift($args, $app);
// 	$container = $app->getContainer();
// 	$app->responseArray = [
// 		"resultCode" => ResultCode::SUCCESS
// 	];
// 	return $response;  
// });

// $app->get('/', function ($request, $response, $args) {
// 	file_put_contents('/var/www/shared/call.log', "index\n", FILE_APPEND);
//     $app = \App\App::getInstance();
// 	file_put_contents('/var/www/shared/call.log', "app logger:\n" . var_export($app->logger, true) . "\n", FILE_APPEND);
// 	// file_put_contents('/var/www/shared/call.log', "app logService:\n" . var_export($app->logService, true) . "\n", FILE_APPEND);    	
// 	$container = $app->getContainer();
// 	$app->logger->addDebug('index message:');
// 	$container['responseArray'] = [
// 		"resultCode" => ResultCode::SUCCESS
// 	];
// 	$app->logger->addDebug(var_export($app->getContainer()['responseArray'], true));
// 	$app->logger->addDebug("uri: " . $request->getUri()->getBasePath());
// 	return $response;
// });

$app->add( new \Middlewares\CheckNotForProduction() );
$app->add( new \Middlewares\CheckMaintenance() );
$app->add( new \Middlewares\CheckLogin() );
$app->add( new \Middlewares\CheckRequestHash() );
if ($app->getContainer()['settings']['https']) {
	$app->add( new \Middlewares\CheckHttps() );
}
$app->add( new \Middlewares\UseMonolog() );
$app->add( new \Middlewares\DefaultResponseParams() );
?>