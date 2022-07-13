<?php
namespace Middlewares;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\App;

class UseCookie extends MiddlewareBase
{
    public function beforeDispatch(Request $request, Response $response)
	{
		$app = App::getInstance();
		$cookies = $request->getCookieParams();
		$app->logService->logger('admin')->addDebug("cookies: " . var_export($cookies, true));
		$app->getContainer()['cookie'] = new \Slim\Http\Cookies($cookies);
		return $response;		
	}

	public function afterDispatch(Request $request, Response $response)
	{
		$app = App::getInstance();
		$response = $response->withHeader('Set-Cookie', $app->getContainer()['cookie']->toHeaders());
		return $response;
	}
}
?>