<?php
namespace Middlewares;

use \Interfaces\MiddlewareInterface;
use \Slim\Http\Request;
use \Slim\Http\Response;

class MiddlewareBase implements MiddlewareInterface
{
	/**
	 * Middleware invokable function
	 *
	 * @param  \Slim\Http\Request                       $request  PSR7 request
	 * @param  \Slim\Http\Response                      $response PSR7 response
	 * @param  callable                                 $next     Next middleware
	 *
	 * @return \Slim\Http\Response
	 */
	public function __invoke(Request $request, Response $response, callable $next)
	{
		// Middleware before Dispatch
		$response = $this->beforeDispatch($request, $response);
        // Dispatch
        $response = $next($request, $response);
        // Middleware after Dispatch
        $response = $this->afterDispatch($request, $response);

		return $response;
	}

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
		return $response;
	}

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
		return $response;
	}

}
?>