<?php
namespace App\Controllers;

use \App\Models\ResultCode;
use \App\Services\ValidationService;
use \App\Services\SqexBridgeService;
use \App\Models\BridgeSession;
use \App\Services\MultiPlayService;

class SampleController
{
    public static function getBridgeNativeSessionId($app, $args)
    {
        $schema = '
        {
            "type":"object",
            "properties":{
                "user_id":                 {"type":"string", "required":true}
            }
        }';
        ValidationService::validateJson($app->requestJson, $schema);

        $session = new BridgeSession($app->requestJson->user_id);
        $nativeSessionId = $session->getNativeSessionId();
        $app->responseArray = [
            "resultCode"      => ResultCode::SUCCESS,
        	"native_session_id" => $nativeSessionId
        ];
    }

    /**
     * API: デバッグ用 SQEX client native session id
     */
    public static function getClientNativeSessionId($app, $args)
    {
        $schema = '
        {
            "type":"object",
            "properties":{
            	"uuid":                 {"type":"string", "required":true},
                "nativeToken":          {"type":"string", "required":true},
                "devicePlatform":       {"type":"integer", "required":true, "enum": [1, 2]}
            }
        }';
        ValidationService::validateJson($app->requestJson, $schema);

		$app->responseArray = [
            "resultCode"      => ResultCode::SQEXBRIDGE_FAILURE_SESSION
		];
		$container = $app->getContainer();
		$sqex_config = $container['settings']['SQEX_GRIDGE'];

        $nativeToken= $app->requestJson->nativeToken;
        $uuid       = $app->requestJson->uuid;
        $deviceType = $app->requestJson->devicePlatform;
		$data = "{\"UUID\":\"$uuid\",\"deviceType\":$deviceType,\"nativeToken\":\"$nativeToken\"}";
		$result = self::openURL($sqex_config['HTML_NATIVE_SESSION_CREATE'], $data);
		if ( $result === false ) {
			$app->logger->addDebug("native session create failure");
			$app->halt(200);
		}
		$result = gzdecode($result);
		if ( $result === false ) {
			$app->logger->addDebug("native session create result gzdecode failure");
			$app->logger->addDebug("native session create result gzdecode failure result:" . $result);
			$app->halt(200);
		}
		$json = json_decode($result, true);
		$app->logger->addDebug("ClientNativeSession result: ". serialize($result));
		$json = json_decode($result, true);
		if ( $json === false ) {
			$app->logger->addDebug("native session create result json decode failure");
			$app->halt(200);
		}
		$sharedSecurityKey	= $json['sharedSecurityKey'];
		$clientNativeSessionId	= $json['nativeSessionId'];
		$app->responseArray = [
            "resultCode"      => ResultCode::SUCCESS,
			"clientNativeSessionId" => $clientNativeSessionId
		];
    }
    /**
     * API: SQEX
     */
    public static function testNativeSessid($app, $args)
    {
        $schema = '
        {
            "type":"object",
            "properties":{
                "uuid":                 {"type":"string", "required":true},
                "devicePlatform":       {"type":"integer", "required":true, "enum": [1, 2]}
            }
        }';
        ValidationService::validateJson($app->requestJson, $schema);
		$app->responseArray = [
			"resultCode"      => ResultCode::SQEXBRIDGE_FAILURE_SESSION,
			"nativeSessionId" => '',
            "userId"          => '',
            "worldId"         => ''
		];
		$container = $app->getContainer();
		$sqex_config = $container['settings']['SQEX_GRIDGE'];

        $sqexBridge = new SqexBridgeService('', $app->requestJson->devicePlatform);
        $result = $sqexBridge->utility_nativetoken_create();
        $nativeToken = $result['ret']['nativeToken'];
        $uuid       = $app->requestJson->uuid;
        $deviceType = $app->requestJson->devicePlatform;
        $app->logger->addDebug("utility_nativetoken_create result nativeToken: " . $nativeToken);
		$data = "{\"UUID\":\"$uuid\",\"deviceType\":$deviceType,\"nativeToken\":\"$nativeToken\"}";
		$result = self::openURL($sqex_config['HTML_NATIVE_SESSION_CREATE'], $data);
		if ( $result === false ) {
			$app->logger->addDebug("native session create failure");
			return;
		}
		$result = gzdecode($result);
		if ( $result === false ) {
			$app->logger->addDebug("native session create result gzdecode failure");
			return;
		}
		$app->logger->addDebug("ClientNativeSession gzdecoded result: ". var_export($result, true));
		$json = json_decode($result, true);
		if ( $json === false ) {
			$app->logger->addDebug("native session create result json decode failure");
			return;
		}
		$sharedSecurityKey	= $json['sharedSecurityKey'];
		$clientNativeSessionId	= $json['nativeSessionId'];

		$result = $sqexBridge->session_update($clientNativeSessionId);
        if ($result['res'] != ResultCode::SUCCESS) {
			$app->logger->addDebug("native session update failure");
            return;
        }
        $nativeSessionId   = $result['ret']['nativeSessionId'];
        $sharedSecurityKey = $result['ret']['sharedSecurityKey'];

        $bRegist = false;
        $sqexBridge->setNativeSessionId($nativeSessionId);
        $result = $sqexBridge->people_login_create();
        if ($result['res'] != ResultCode::SUCCESS) {
        	// 404コードはアカウントが作られていない
        	// それ以外のエラーは終了する
        	if ($result['resCode'] == 404) {
        		$result = $sqexBridge->people_create();
        		if ($result['res'] != ResultCode::SUCCESS) {
        			$app->logger->addDebug(__CLASS__ . '::' . __FUNCTION__ . " SQEX Bridge people_create failure response errcode:[".$result['resCode']."] ".$result['ret']);
        			return;
        		}
        		$bRegist = true;
    	    } else {
	        	$app->logger->addDebug(__CLASS__ . '::' . __FUNCTION__ . " SQEX Bridge people_login_create failure response errcode:[".$result['resCode']."] ".$result['ret']);
    	    	return;
    	    }
        }
        $userId  = $result['ret']['nativeUserId'];
        $worldId = $sqex_config['WORLD_ID'];

        $session = new BridgeSession($userId);
        $session->setNativeSessionId($nativeSessionId);
        $session->setSharedSecurityKey($sharedSecurityKey);
        $session->setWorldId($worldId);
        $session->setRegist($bRegist ? 1 : 0);

		// ワールド取得
		$world_info = $sqexBridge->game_world_get(false);
		if ($world_info['res'] != ResultCode::SUCCESS) {
			if ( $world_info['errorCode'] == 'E1001' ) {
				// ワールド作成
				$result = $sqexBridge->game_world_create();
				if ($result['res'] != ResultCode::SUCCESS) {
					$app->logger->addDebug(__CLASS__ . '::' . __FUNCTION__ . " SQBridge game_world_create failure response errcode:[".$result['resCode']."] ".$result['ret']);
					return;
				}

				// ワールド再取得
				$world_info = $sqexBridge->game_world_get();
				if ($world_info['res'] != ResultCode::SUCCESS) {
					$app->logger->addDebug(__CLASS__ . '::' . __FUNCTION__ . " SQBridge game_world_get failure response errcode:[".$world_info['resCode']."] ".$world_info['ret']);
					return;
				}
			}
		}

		// ユーザープロフィール
		$user_profile = $sqexBridge->people_get(true);
		if ($user_profile['res'] != ResultCode::SUCCESS) {
			$app->logger->addDebug(__CLASS__ . '::' . __FUNCTION__ . " SQBridge people_get failure response errcode:[".$user_profile['resCode']."] ".$user_profile['ret']);
			return;
		}
        $session->setUserProfile($user_profile);
		$app->logger->addDebug(__CLASS__ . '::' . __FUNCTION__ . " SQBridge SessionToCahce");
        // このトークンの有効期限は１分間
        $app->responseArray = [
            "resultCode"      => ResultCode::SUCCESS,
            "nativeSessionId" => $nativeSessionId,
            "userId"          => $userId,
            "userProfile"     => $user_profile['ret'],
            "worldId"         => $worldId,
            "worldInfo"       => $world_info['ret']
        ];
    }

    /**
     * get node process information
     */
    public static function getNodeProcessInfo($app, $args)
    {
        // node server プロセス情報の取得
        $processInfo = MultiPlayService::getProcessInfo();
        if (empty($processInfo)) {
			$app->logger->addDebug(__CLASS__ . "::" . __FUNCTION__ . " MultiPlay Server Empty");
			$app->responseArray = ["resultCode" => ResultCode::MULTI_SERVER_STATE_MAINTENANCE];
			$app->halt(200);
        }
        $app->responseArray = [
            "resultCode"      	=> ResultCode::SUCCESS,
            "processInfo"		=> $processInfo
        ];
    }

	// URL実行
	protected function openURL($url, $params=null, $timeout=5)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,				$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,	true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,	true);
		curl_setopt($ch, CURLOPT_TIMEOUT,			$timeout);
		// curl_setopt($ch, CURLOPT_HTTPHEADER,		array('Content-type" => "application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_HTTPHEADER,		array('Content-type" => "application/json'));
		if ( !is_null($params) ) {
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}
		$result = curl_exec($ch);
		// エラー検出
		if ( $result === false ) {
			$err = curl_error($ch);
			$result = "connection error.(".$err.")";
		}
		curl_close($ch);
		return $result;
	}

}