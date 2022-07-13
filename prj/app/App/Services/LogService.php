<?php
namespace App\Services;

use \App\Models\ResultCode;
use \Slim\Slim;

/**
 * ログ処理のサービスクラス.
 */
class LogService
{
    private $loggers = [];
    private $logConfig;

    public function __construct($logConfig)
    {
        $this->logConfig = $logConfig;
    }

    /**
     * ロガーを返す.
     * @param  string $name ロガー名
     * @return \Monolog\Logger
     */
    public function logger($name)
    {
        if (isset($this->loggers[$name])) {
            $logger = $this->loggers[$name];
        } else {
            $logger = $this->createLogger($name);
            $this->loggers[$name] = $logger;
        }

        return $logger;
    }

    /**
     * ロガーを生成する.
     * @param  string $name ロガー名
     * @return \Monolog\Logger
     */
    protected function createLogger($name)
    {
        if (isset($this->logConfig[$name])) {
            $logger = new \Monolog\Logger($name);
            $handlers = $this->logConfig[$name];
            $logger->setHandlers($handlers);
            return $logger;
        } else {
            // エラー終了: ロガー未設定エラー.
            $app = \App\App::getInstance();
            $app->getContainer()->logger->error("configuration for log ({$name}) is not found.");
            $app->responseArray = ["resultCode" => ResultCode::UNKNOWN_ERROR];
            $app->halt(200);
        }
    }
}

?>