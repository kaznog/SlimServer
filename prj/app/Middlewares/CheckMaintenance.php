<?php
namespace Middlewares;

use \Slim\Http\Request;
use \Slim\Http\Response;
use \App\Models\ResultCode;
use \App\Models\PlayersIdentity;
use \App\Services\MaintenanceService;

/**
 * メンテナンス状態をチェックするフック処理.
 */
class CheckMaintenance extends MiddlewareBase
{
    public function beforeDispatch(Request $request, Response $response)
    {
        $app = \App\App::getInstance();

        $service = new MaintenanceService();
        $maintenance = $service->isUnderMaintenance();
        if ($maintenance) {
            $requestPath = $request->getUri()->getBasePath() . '/' . $request->getUri()->getPath();
            if ($service->isAllowedApi($requestPath)) {
                return $response;
            }

            // 特定のIPだけログインできる
            if( !isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])
                || ($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] != '127.0.0.1') ) {
                $app->responseArray = [
                    "message" => $maintenance->message,
                    "resultCode" => ResultCode::MAINTENANCE,
                ];
                $app->halt(200);
            } else {
                $app->logger->addNotice('CheckMaintenance:'.$_SERVER['HTTP_X_CLUSTER_CLIENT_IP'].' '.$requestPath);
                $playerId = $app->playerId;
                // ログインAPIであれば、uuidに対するプレイヤーIDを取得する.
                if ($requestPath == "/api/player/login") {
                    $uuid      = $app->requestJson->uuid;
                    $playersIdentity = PlayersIdentity::findByUuid($uuid);
                    if ($playersIdentity) {
                        $playerId = $playersIdentity->id;
                    }
                    $app->logger->addNotice('CheckMaintenance: '.$requestPath.' playerId: '.$playerId);
                }
            }
        }
        return $response;
    }
}
