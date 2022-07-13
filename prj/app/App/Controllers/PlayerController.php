<?php
namespace App\Controllers;

use \App\Models\ResultCode;
use \App\Services\ValidationService;
use \App\Services\NgWordService;
use \App\Services\SignupService;
use \App\Services\LoginService;
use \App\Models\InfoMessage;
use \App\Services\SqexBridgeService;
use \App\Models\BridgeSession;


class PlayerController {
    /**
     * API: SQEXトークン取得
     */
    public static function getNativeToken($app, $args)
    {
        $schema = '
        {
            "type":"object",
            "properties":{
                "devicePlatform":       {"type":"integer", "required":true, "enum": [1, 2]}
            }
        }';
        ValidationService::validateJson($app->requestJson, $schema);
        $sqexBridge = new SqexBridgeService('', $app->requestJson->devicePlatform);
        $result = $sqexBridge->utility_nativetoken_create();
        $app->responseArray = [
            "resultCode" => $result['res'],
            "nativeToken" => $result['ret']['nativeToken'] ?? ''
        ];
    }

    /**
     * API: SQEX native session update
     */
    public static function updateSess($app, $args)
    {
        $schema = '
        {
            "type":"object",
            "properties":{
                "clientNativeSessionId":{"type":"string", "required":true},
                "devicePlatform":       {"type":"integer", "required":true, "enum": [1, 2]}
            }
        }';
        ValidationService::validateJson($app->requestJson, $schema);

        $container = $app->getContainer();
        $sqex_config = $container['settings']['SQEX_GRIDGE'];

        $sqexBridge = new SqexBridgeService('', $app->requestJson->devicePlatform);
        $result = $sqexBridge->session_update($app->requestJson->clientNativeSessionId);
        if ($result['res'] != ResultCode::SUCCESS) {
            $app->responseArray = [ "resultCode"      => $result['res'] ];
            $app->halt(200);
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
                $app->responseArray = [
                    "resultCode"      => ResultCode::SUCCESS,
                    "userId"            => "",
                    "nativeSessionId"   => $nativeSessionId,
                    "sharedSecurityKey" => $sharedSecurityKey
                 ];
                $app->halt(200);
            } else {
                $app->logger->addDebug(__CLASS__ . '::' . __FUNCTION__ . " SQEX Bridge people_login_create failure response errcode:[".$result['resCode']."] ".$result['ret']);
                $app->responseArray = [
                    "resultCode"      => $result['res'],
                    "userId"            => "",
                    "nativeSessionId"   => $nativeSessionId,
                    "sharedSecurityKey" => $sharedSecurityKey
                ];
                $app->halt(200);
            }
        }
        $userId  = $result['ret']['nativeUserId'];

        $app->responseArray = [
            "resultCode"        => ResultCode::SUCCESS,
            "userId"            => (string)$userId,
            "nativeSessionId"   => $nativeSessionId,
            "sharedSecurityKey" => $sharedSecurityKey
        ];
    }

    /**
     * API: SQEX login
     */
    public static function loginBridge($app, $args)
    {
        $schema = '
        {
            "type":"object",
            "properties":{
                "userId":               {"type":"string",  "required":false},
                "nativeSessionId":      {"type":"string",  "required":true},
                "sharedSecurityKey":    {"type":"string",  "required":true},
                "devicePlatform":       {"type":"integer", "required":true, "enum": [1, 2]}
            }
        }';
        ValidationService::validateJson($app->requestJson, $schema);

        $container = $app->getContainer();
        $sqex_config = $container['settings']['SQEX_GRIDGE'];

        // if (!isset($_SESSION[BridgeSession::SESSION_BRIDGE_NSID]) ||
        //     !isset($_SESSION[BridgeSession::SESSION_SHARED_SECURITY_KEY]) ||
        //     strlen($_SESSION[BridgeSession::SESSION_BRIDGE_NSID]) < 1 ||
        //     strlen($_SESSION[BridgeSession::SESSION_SHARED_SECURITY_KEY]) < 1
        // ) {
        //     $app->logger->addDebug(__CLASS__ . '::' . __FUNCTION__ . " SESSION FALIURE");
        //     $app->responseArray = [ "resultCode"      => ResultCode::SQEXBRIDGE_FAILURE_SESSION ];
        //     $app->halt(200);
        // }
        $sqexBridge = new SqexBridgeService($app->requestJson->nativeSessionId, $app->requestJson->devicePlatform);

        // ユーザ情報が無ければ作成
        $userId  = '';
        $bRegist = false;       // 初回ログインかどうか判定
        if ( !empty($app->requestJson->userId) ) {
            $userId = $app->requestJson->userId;

        } else {
            $result = $sqexBridge->people_create();
            if ($result['res'] != ResultCode::SUCCESS) {
                $app->logger->addDebug(__CLASS__ . '::' . __FUNCTION__ . " SQEX Bridge people_create failure response errcode:[".$result['resCode']."] ".$result['ret']);
                $app->responseArray = [ "resultCode"      => $result['res'] ];
                $app->halt(200);
            }
            $userId  = $result['ret']['nativeUserId'];
            $bRegist = true;
            $result = $sqexBridge->people_login_create();
            if ($result['res'] != ResultCode::SUCCESS) {
                $app->logger->addDebug(__CLASS__ . '::' . __FUNCTION__ . " SQEX Bridge people_login_create failure response errcode:[".$result['resCode']."] ".$result['ret']);
                // ログインできない場合は、ログインさせない?
                $app->responseArray = [ "resultCode"      => $result['res'] ];
                $app->halt(200);
            }
        }
        $worldId = $sqex_config['WORLD_ID'];
        // 共通memcached SESSIONからmemcached keyへの準備
        $session = new BridgeSession($userId);
        $session->setNativeSessionId($app->requestJson->nativeSessionId);
        $session->setSharedSecurityKey($app->requestJson->sharedSecurityKey);
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
                    $app->responseArray = [
                        "resultCode"      => $result['res'],
                        "userId"          => $userId,
                    ];
                    $app->halt(200);
                }

                // ワールド再取得
                $world_info = $sqexBridge->game_world_get();
                if ($world_info['res'] != ResultCode::SUCCESS) {
                    $app->logger->addDebug(__CLASS__ . '::' . __FUNCTION__ . " SQBridge game_world_get failure response errcode:[".$world_info['resCode']."] ".$world_info['ret']);
                    $app->responseArray = [
                        "resultCode"      => $result['res'],
                        "userId"          => $userId,
                    ];
                    $app->halt(200);
                }
            }
        }
        // ユーザープロフィール
        $user_profile = $sqexBridge->people_get(true);
        if ($user_profile['res'] != ResultCode::SUCCESS) {
            $app->logger->addDebug(__CLASS__ . '::' . __FUNCTION__ . " SQBridge people_get failure response errcode:[".$user_profile['resCode']."] ".$user_profile['ret']);
            $app->responseArray = [
                "resultCode"      => $user_profile['res'],
                "userId"          => $userId,
            ];
            $app->halt(200);
        }
        $session->setUserProfile($user_profile);

        $app->responseArray = [
            "resultCode"      => ResultCode::SUCCESS,
            "userId"          => (string)$userId,
        ];
    }

    /**
     * API: サインアップ
     * SQUARE ENIX ユーザーID からプレイヤーデータを作成する.
     * @param \App\App $app
     */
    public static function signup($app, $args)
    {
        $app->logger->addDebug("signup start");
        $schema = '
        {
            "type":"object",
            "properties":{
                "userId":               {"type":"string", "required":true},
                "name":                 {"type":"string", "required":true, "minLength":1, "maxLength":45},
                "gender":               {"type":"integer", "required":true, "enum": [1, 2]},
                "devicePlatform":       {"type":"integer", "required":true, "enum": [1, 2]}
            }
        }';
        ValidationService::validateJson($app->requestJson, $schema);
        $app->logger->addDebug("requestJson validate");

        $userId             = $app->requestJson->userId;
        $name               = $app->requestJson->name;
        $gender             = $app->requestJson->gender;
        $devicePlatform     = $app->requestJson->devicePlatform;
        $app->logger->addDebug("userId" . $userId);

        // 何か使う？
        //$session = new BridgeSession($userId);

        // NGワードチェック.
        $ngWordService = new NgWordService();
        $ngWordService->check($name);

        $signupService = new SignupService();
        $playerIdentity = $signupService->signup(
            $userId,
            $name,
            $gender,
            $devicePlatform
        );

        $app->responseArray = [
            "playerId" => (int) $playerIdentity->id,
            "resultCode" => ResultCode::SUCCESS
        ];
    }

    /**
     * API: ログイン
     * SQUARE ENIX ユーザーIDを指定してログインし、セッションIDを発行する.
     */
    public static function login($app, $args)
    {
        // validation
        $schema = '
        {
            "type":"object",
            "properties":{
                "userId":                 {"type":"string", "required":true}
            }
        }';
        ValidationService::validateJson($app->requestJson, $schema);

        $userId = $app->requestJson->userId;

        $loginService = new LoginService();
        $loginResult = $loginService->login(
            $userId
        );
        // ログイン成功.
        // ログインボーナス.
        $loginBonus = [
            "friendPoint" => (int)0
        ];
        if ($loginResult->loginBonus) {
            $loginBonus["friendPoint"] = (int) $loginResult->loginBonus->friend_point;
        }
        // ソーシャル関係ボーナス.
        $socialBonus = [
            "likeCnt" => (int)0,
            "likeFriendPt" => (int)0
        ];
        if ($loginResult->socialBonus) {
            $socialBonus['likeCnt'] = (int) $loginResult->socialBonus['likeCnt'];
            $socialBonus['likeFriendPt'] = (int) $loginResult->socialBonus['likeFriendPt'];
        }

        $sessionId = $loginResult->sessionId;
        $app->responseArray = [
            'playerId' => (int) $loginResult->playerId,
            'sessionId' => (string) $sessionId,
            'loginBonus' => $loginBonus,
            'mailCount' => (int) $loginResult->mailCount,
            'socialBonus' => $socialBonus,
            'LivingTown' => $loginResult->LivingTown,
            // "sessionExpiresAt" => Str::timeToStr(\App\Services\RequestVerifyService::calcSessionExpires() + time()),
            'infomsg' => InfoMessage::get(),
            'resultCode' => ResultCode::SUCCESS
        ];
    }
}

?>