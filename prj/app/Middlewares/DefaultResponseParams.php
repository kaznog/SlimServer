<?php
namespace Middlewares;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\Models\ServerVersion;
use \Utils\Str;

class DefaultResponseParams extends MiddlewareBase
{
	/**
	 * Middleware after Dispatch function
	 *
	 * @param  \Slim\Http\Request                       $request  PSR7 request
	 * @param  \Slim\Http\Response                      $response PSR7 response
	 * @param  callable                                 $next     Next middleware
	 *
	 * @return \Slim\Http\Response
	 */
	public function afterDispatch(Request $request, Response $response)
	{
	    $app = \App\App::getInstance();
		$container = $app->getContainer();
		// $app->logger->addDebug("DefaultResponseParams::insertParams");
	    $resArray = $app->responseArray ?? array();
	    $resArray["serverTime"] = \Utils\Str::timeToStr(time());
	    $resArray["serverVersion"] = \App\Models\ServerVersion::VERSION;
	    // $response->withJson($resArray);
	    $response->withHeader('Content-Type', 'application/json;charset=utf-8');
	    $response->getBody()->write(json_encode($resArray));

        return $response;
	}
}
?>