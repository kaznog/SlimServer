<?php
namespace Middlewares;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\Models\ResultCode;
use \App\Services\RequestVerifyService;

/**
 * ログイン状態をチェックするフック処理.
 */
class CheckLogin extends MiddlewareBase
{
    public function beforeDispatch(Request $request, Response $response)
    {
        $app = \App\App::getInstance();
        $playerId = null;
        $sessionId = null;

        $needSessionId = TRUE;

        // check if required login from request path info.
        $loginFreeList = [
            "/api/player/get_native_token",
            "/api/player/update_sess",
            "/api/player/login_bridge",
            "/api/app/check_version",
            "/api/player/signup",
            "/api/player/login",
            "/api/app/health_deep",
            "/api/sample/",
            "/api/debug/"
        ];
        $haystack = $request->getUri()->getBasePath() . '/' . $request->getUri()->getPath();
        foreach ($loginFreeList as $needle) {
            if (strpos($haystack, $needle) === 0) {
                $needSessionId = FALSE;
                break;
            }
        }

        if ($needSessionId) {
            // セッションIDからプレイヤーIDを取得する.
            $headers[RequestVerifyService::HEADER_SESSION_ID_KEY] = $request->getHeader('x-app-sessionId');
            $headers[RequestVerifyService::HEADER_SESSION_ID_KEY] = $headers[RequestVerifyService::HEADER_SESSION_ID_KEY][0] ?? "";
            $sessionId = RequestVerifyService::getSessionId($headers);
            $app->logger->addDebug(__CLASS__ . "::" . __FUNCTION__ . " sessionId[" . $sessionId . "]");
            $playerId = RequestVerifyService::getPlayerIdBySessionId($sessionId);
            if ($playerId === false) {
                $app->responseArray = [
                    "message" => "login required.",
                    "resultCode" => ResultCode::LOGIN_REQUIRED,
                ];
                $app->halt(200);
            }
        }
        $app->playerId = $playerId;
        $app->sessionId = $sessionId;
        return $response;
    }
}
