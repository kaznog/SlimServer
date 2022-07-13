<?php
namespace App;

class App extends \Slim\App
{
	private static $instance;

	public static function getInstance()
	{
		return self::$instance ?? null;
	}

	public function __construct($container = [])
	{
		parent::__construct($container);
		self::$instance = $this;
	}

	public function halt(int $code)
	{
        // $response = new \Slim\Http\Response($code);
        $container = $this->getContainer();
        $request  = $container->get('request');
        $response = $container->get('response');
	    $response->withHeader('Content-Type', 'application/json;charset=utf-8');
	    $resArray = $this->responseArray ?? array();
	    $response->getBody()->write(json_encode($resArray));
        throw  new \Slim\Exception\SlimException($request, $response);
	}

}
?>