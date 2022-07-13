<?php
namespace Middlewares;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\Services\RequestVerifyService;
use \App\Models\ResultCode;
use \App\App;

/**
 * リクエストハッシュを検証するフック処理.
 */
class CheckRequestHash extends MiddlewareBase
{
    public function beforeDispatch(Request $request, Response $response)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $data = null;
        $json = null;
        if ($request->isPost()) {
            $data = file_get_contents('php://input');
            $app->requestJson = json_decode($data);
            if (!empty($data) && is_null($app->requestJson)) {
                $app->logger->addDebug("CheckRequestHash INVALID_PARAMETERS.");
                $app->responseArray = [
                    'message' => 'invalid JSON.',
                    'resultCode' => ResultCode::INVALID_PARAMETERS,
                ];
                $app->halt(200);

                return;
            }
        }

        if ($container['settings']["requesthash.check"]) {
            $headers[RequestVerifyService::HEADER_REQUEST_HASH_KEY] = $request->getHeader('x-app-requesthash');
            $headers[RequestVerifyService::HEADER_REQUEST_HASH_KEY] = $headers[RequestVerifyService::HEADER_REQUEST_HASH_KEY][0] ?? "";
            $headers[RequestVerifyService::HEADER_SESSION_ID_KEY] = $request->getHeader('x-app-sessionId');
            $headers[RequestVerifyService::HEADER_SESSION_ID_KEY] = $headers[RequestVerifyService::HEADER_SESSION_ID_KEY][0] ?? "";
            $app->logService->logger('admin')->addDebug(__CLASS__ . '::' . __FUNCTION__ . " headers: " . var_export($headers, true));
            $requestUri = $request->getUri()->getBasePath() . '/'. $request->getUri()->getPath();
            if ($container['environment']['QUERY_STRING']) {
                $requestUri .= '?' . $container['environment']['QUERY_STRING'];
            }
            if ($requestUri === '/api/app/health_deep') {
                return $response;
            }
            if (RequestVerifyService::verifyRequestHash($headers, $requestUri, $data) === false) {
                if ($container['settings']["debug"]) {
                    $sessionId = $headers[RequestVerifyService::HEADER_SESSION_ID_KEY];
                    if (empty($sessionId)) {
                        $sessionId = '';
                    }
                    $baseString = RequestVerifyService::getBaseString($sessionId, $requestUri, $data);
                    $validHash = RequestVerifyService::getHash($baseString);
                    // debug時はエラー時レスポンスに正しいハッシュなどを追加.
                    $app->responseArray = [
                        'message' => 'invalid Request Hash.',
                        'resultCode' => ResultCode::INVALID_REQUEST_HASH,
                        'debug.baseString' => RequestVerifyService::getBaseString($headers[RequestVerifyService::HEADER_SESSION_ID_KEY], $requestUri, $data),
                        'debug.receivedHash' => $headers[RequestVerifyService::HEADER_REQUEST_HASH_KEY],
                        'debug.validBaseString' => $baseString,
                        'debug.validHash' => $validHash,
                        'debug.script_name' => $container['environment']['SCRIPT_NAME'],
                        'debug.path_info' => $container['environment']['PATH_INFO'],
                        'debug.QUERY_STRING' => $container['environment']['QUERY_STRING']
                        ];
                } else {
                    $app->responseArray = [
                        'message' => 'invalid Request Hash.',
                        'resultCode' => ResultCode::INVALID_REQUEST_HASH,
                        ];
                }
                $app->halt(200);
            }
        }
        return $response;
    }
}
