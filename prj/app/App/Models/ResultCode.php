<?php
namespace App\Models;

/**
 * 応答コード.
 */
class ResultCode
{
    // 成功.
    const SUCCESS = 0;
    // アプリのバージョンアップが必要.
    const OUTDATED = 1;
    // アプリが申請中. 通信先を申請用サーバに変更する必要がある.
    const APPLYING = 2;

    //==========================================================
    // 全般
    //==========================================================

    // リクエストハッシュ不正.
    const INVALID_REQUEST_HASH                      = 101;
    // JSONパラメータ不正(型不正もしくはJSONでない).
    const INVALID_PARAMETERS                        = 102;
    // JSONパラメータ不正(必須パラメータが無い).
    const INSUFFICIENT_PARAMETERS                   = 103;
    // JSONスキーマが不正.
    const INVALID_JSON_SCHEMA                       = 104;
    // DB エラー.
    const DB_ERROR                                  = 105;
    // シャーディングエラー.
    const DB_SHARDING_ERROR                         = 106;
    // NGワード
    const NG_WORD                                   = 107;

    // トランザクションが存在しない.
    const TRANSACTION_NOT_FOUND                     = 108;
    // トランザクションが完了済み.
    const TRANSACTION_ALREADY_COMMITED              = 109;
    // memcached error
    const MEMCACHED_SET_ERROR                       = 110;

    // redis error
    const REDIS_SET_ERROR                           = 120;

    const REQUEST_RETRY                             = 199;

    //==========================================================
    // SQUARE ENIX BRIDGE
    //==========================================================
    // 通信時エラー
    const SQEXBRIDGE_FAILURE_SESSION                = 201;

    const SQEXBRIDGE_FAILURE_NATIVETOKEN_CREATE     = 202;

    const SQEXBRIDGE_FAILURE_SESSION_UPDATE         = 203;

    const SQEXBRIDGE_FAILURE_PEOPLE_CREATE          = 204;

    const SQEXBRIDGE_FAILURE_PEOPLE_LOGIN_CREATE    = 205;

    const SQEXBRIDGE_FAILURE_PEOPLE_GET             = 206;

    const SQEXBRIDGE_FAILURE_GAME_WORLD_CREATE      = 207;

    const SQEXBRIDGE_FAILURE_GAME_WORLD_GET         = 208;


    //==========================================================
    // プレイヤー
    //==========================================================

    // プレイヤーが既に存在する.
    const PLAYER_ALREADY_EXISTS                     = 301;
    // プレイヤーが存在しない (存在しないUserIdでログインしようとした場合など).
    const PLAYER_NOT_FOUND                          = 302;
    // アクセストークンが不正
    const INVALID_ACCESS_TOKEN                      = 303;
    // ログイン方法が不正
    const INVALID_PLAYER_IDENTITY_KIND              = 304;
    // ログイン必須.
    const LOGIN_REQUIRED                            = 305;

    //==========================================================
    // Multi Server
    //==========================================================
    const MULTI_SERVER_STATE_MAINTENANCE            = 400;
    const MULTI_SERVER_TOWN_ENTRY_FULL              = 500;
    const TOWN_NOT_EXIST                            = 501;
    const TOWN_RESERVED                             = 502;

    // 未知のエラー.
    const UNKNOWN_ERROR = 999;
}

?>