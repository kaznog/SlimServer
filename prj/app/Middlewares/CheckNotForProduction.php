<?php
namespace Middlewares;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\App;

class CheckNotForProduction extends MiddlewareBase
{
    public function beforeDispatch(Request $request, Response $response)
	{
		$app = App::getInstance();
		$notForProductionList = ["/api/sample", "/api/debug", "/admin/console"];
		if ($app->mode === "production") {
		    $haystack = $request->getUri()->getBasePath() . '/' . $request->getUri()->getPath();
		    foreach ($notForProductionList as $needle) {
		        if (strpos($haystack, $needle) === 0) {
		            exit();
		        }
		    }
		}
		return $response;
	}
}
?>