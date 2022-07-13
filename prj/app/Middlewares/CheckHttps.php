<?php

use \Slim\Http\Request;
use \Slim\Http\Response;

class CheckHttps
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
        if ($request->getScheme() !== "https") {
        	$app = \App\App::getInstance();
        	$resArray = ["resultCode" => ResultCode::UNKNOWN_ERROR];
        	$app->halt(403, json_encode($resArray));
        }
        return $response;
	}
}
?>