<?php
namespace Middlewares;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\Services\LogService;

class UseMonolog extends MiddlewareBase
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
        UseMonolog::execute();
        return $response;
    }

    public static function execute()
    {
        $app = \App\App::getInstance();
        $container = $app->getContainer();
        if (isset($app->batch)) {
            // バッチ用ログ設定.
            $logConfig = $container['settings']['log.batch'];
        } else {
            // HTTP用ログ設定.
            $logConfig = $container['settings']['log.web'];
        }

        // default のハンドラーをSlim Log Writer に設定.
        $handlers = isset($logConfig['default']) ? $logConfig['default'] : null;
        if ($handlers) {
            $logger = new \Monolog\Logger('default');
            $logger->setHandlers($handlers);
            $app->logger = $logger;
        }

        if ( !isset($app->batch) ) {
            // 独自ログをセットする.
            $app->logService = new \App\Services\LogService($logConfig);
        }
    }
    /**
     * StreamHandler を生成する.
     * @param  string                                $stream
     * @param  integer                               $level
     * @param  boolean                               $bubble
     * @param  \Monolog\Formatter\FormatterInterface $formatter
     * @return \Monolog\Handler\StreamHandler
     */
	 public static function createStreamHandler($stream, $level = Logger::DEBUG, $bubble = true, $formatter = null)
	 {
        $handler = new \Monolog\Handler\StreamHandler($stream, $level, $bubble);
        if ($formatter) {
            $handler->setFormatter($formatter);
        }

        return $handler;
    }
}
?>