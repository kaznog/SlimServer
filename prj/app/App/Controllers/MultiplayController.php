<?php
namespace App\Controllers;

use \App\Models\ResultCode;
use \App\Services\ValidationService;
use \App\Services\MultiPlayService;
use \App\App;

class MultiPlayController
{
    public static function getToken($app, $args)
    {
        $schema = '
        {
            "type":"object",
            "properties":{
                "serverType":               {"type":"integer", "required":true, "enum": [0, 1, 2]},
                "pvpId":					{"type":"string",  "required":false}
            }
        }';
        ValidationService::validateJson($app->requestJson, $schema);

        $pvpId = "";
        // よくわからんがpvpパラメータに関する初期化が発生するらしい
        if ($app->requestJson->serverType == MultiPlayService::RT_SERVER_PVP_BATTLE) {
            $app->logger->addDebug("servertype:" . $app->requestJson->serverType . " == MultiPlayService::RT_SERVER_PVP_BATTLE" );
        	if (!isset($app->requestJson->pvpId)) {
        		$app->responseArray = ["resultCode" => ResultCode::INSUFFICIENT_PARAMETERS];
        		$app->halt(200);
        	}
        	$pvpId = $app->requestJson->pvpId;
        }

        // nodeにかかわるメンテナンス処理が入るらしい

        // pvpに関する初期設定が入るらしい
        $hostId = false;

        // node server プロセス情報の取得
        $processInfo = MultiPlayService::getProcessInfo();
        if (empty($processInfo)) {
			$app->logger->addDebug(__CLASS__ . "::" . __FUNCTION__ . " MultiPlay Server Empty");
			$app->responseArray = ["resultCode" => ResultCode::MULTI_SERVER_STATE_MAINTENANCE];
			$app->halt(200);
        }

        $app->logger->addDebug(__CLASS__."::".__FUNCTION__." processInfo:".serialize($processInfo));
        // node serverを選択
        $serverInfo = MultiPlayService::selectServer($processInfo, $hostId);
        if ($serverInfo === false) {
			// node serverが見つからなければメンテナンス
			$app->responseArray = ["resultCode" => ResultCode::MULTI_SERVER_STATE_MAINTENANCE];
			$app->halt(200);
        }

        $bBeginner	= false;
        $rtInfo 	= null;
        $pvpPlay 	= null;
        $caravan 	= null;
        $teamId 	= null;
        $teamName 	= null;
        $result = MultiPlayService::requestToken(
        	$app->playerId,
        	$app->requestJson->serverType,
        	$bBeginner,
        	$rtInfo, $pvpPlay, $caravan, $teamId, $teamName
        );
        if ($result['res'] != ResultCode::SUCCESS) {
        	$app->logger->addError(__CLASS__."::".__FUNCTION__.". error at ".__LINE__);
        	$app->responseArray = ["resultCode" => ResultCode::UNKNOWN_ERROR];
        	$app->halt(200);
        }

        $app->logger->addDebug('player['.$app->playerId.'] publish onetime token:['.$result['token'].'] type:['.$app->requestJson->serverType.']');
        $app->responseArray = [
        	"resultCode" => ResultCode::SUCCESS,
        	"token"      => $result['token'],
        	"url"		 => $serverInfo['url']
        ];
    }

    public static function createRoom($app, $args)
    {
        $schema = '
        {
            "type":"object",
            "properties":{
                "serverType":               {"type":"integer", "required":true, "enum": [0, 1, 2]},
            }
        }';
        ValidationService::validateJson($app->requestJson, $schema);

        $battle_field = MultiPlayService::createRoom($app->requestJson->serverType);

        $app->responseArray = [
            "resultCode" => ResultCode::SUCCESS,
            "roomId" => $battle_field->inquiryId
        ];
    }

    public static function getTownList($app, $args)
    {
        $towns = MultiPlayService::getTownList();
        $app->responseArray = [
            "resultCode" => ResultCode::SUCCESS,
            "Towns" => $towns
        ];
    }

    public static function entryTown($app, $args)
    {
        $schema = '
        {
            "type":"object",
            "properties":{
                "townId":               {"type":"string",  "required":true}
            }
        }';
        ValidationService::validateJson($app->requestJson, $schema);

        $townId = $app->requestJson->townId;
        if (MultiPlayService::entryTown($townId)) {
            $app->responseArray = [ "resultCode" => ResultCode::SUCCESS ];
        }
    }

    public static function unsetTownEntryReserve($app, $args)
    {
        if (MultiPlayService::unsetTownEntryReserve()) {
            $app->responseArray = [ "resultCode" => ResultCode::SUCCESS ];
        }
    }
}
