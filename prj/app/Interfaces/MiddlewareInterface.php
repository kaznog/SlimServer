<?php
namespace Interfaces;

use \Slim\Http\Request;
use \Slim\Http\Response;

interface MiddlewareInterface
{
	/**
	 * Middleware invokable function
	 *
	 * @param  \Slim\Http\Request                       $request  PSR7 request
	 * @param  \Slim\Http\Response                      $response PSR7 response
	 * @param  callable                                 $next     Next middleware
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function __invoke(Request $request, Response $response, callable $next);

	/**
	 * Middleware before Dispatch function
	 *
	 * @param  \Slim\Http\Request                       $request  PSR7 request
	 * @param  \Slim\Http\Response                      $response PSR7 response
	 * @param  callable                                 $next     Next middleware
	 *
	 * @return \Slim\Http\Response
	 */
	public function beforeDispatch(Request $request, Response $response);

	/**
	 * Middleware after Dispatch function
	 *
	 * @param  \Slim\Http\Request                       $request  PSR7 request
	 * @param  \Slim\Http\Response                      $response PSR7 response
	 * @param  callable                                 $next     Next middleware
	 *
	 * @return \Slim\Http\Response
	 */
	public function afterDispatch(Request $request, Response $response);
}
?>