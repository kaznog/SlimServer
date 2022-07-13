<?php
namespace Middlewares;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\App;

class initializeSession extends MiddlewareBase
{
	/**
	 * Middleware before Dispatch function
	 *
	 * @param  \Slim\Http\Request                       $request  PSR7 request
	 * @param  \Slim\Http\Response                      $response PSR7 response
	 * @param  callable                                 $next     Next middleware
	 *
	 * @return \Slim\Http\Response
	 */
	public function beforeDispatch(Request $request, Response $response)
	{
		$app = App::getInstance();
		$memcachedhosts = $app->getContainer()['settings']['memcached.hosts'];
		$session_save_path = [];
		foreach ($memcachedhosts as $hosts) {
		    $session_save_path[] = $hosts[0]  . ':' . $hosts[1];
		}
		$session_save_path = implode(',', $session_save_path);
		if (ini_set('session.save_handler', 'memcached') && ini_set('session.save_path', $session_save_path)){
		    session_start();
		}

		return $response;
	}
}