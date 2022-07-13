<?php
namespace App\Services;

use \App\App;
use App\Models\UUID;

/**
 * リクエストの検証ロジック.
 */
class RequestVerifyService
{
    // セッションIDをセットするHTTPリクエストヘッダ名
    const HEADER_SESSION_ID_KEY = 'X-APP-SESSIONID';
    // リクエストハッシュをセットするHTTPリクエストヘッダ名
    const HEADER_REQUEST_HASH_KEY = 'X-APP-REQUESTHASH';

    /**
     * ヘッダからセッションIDを取得して返す.
     * @param  array  $headers HTTPヘッダ配列.
     * @return string セッションID.
     */
    public static function getSessionId($headers)
    {
        return isset($headers[self::HEADER_SESSION_ID_KEY]) ? $headers[self::HEADER_SESSION_ID_KEY] : null;
    }

    /**
     * Memcachedを参照し、セッションIDからプレイヤーIDを取得.
     * @param  string $sessionId
     * @return int    $playerId
     */
    public static function getPlayerIdBySessionId($sessionId)
    {
        $key = CacheService::getSessionKey($sessionId);
        $app = App::getInstance();

        return $app->memcached->get($key);
    }

    /**
     * セッションIDを発行.
     * @param $playerId プレイヤーID
     * @return string セッションID
     */
    public static function issueSessionId($playerId)
    {
//        $sessionId = uuid_create();
        $sessionId = UUID::generate(1);
        $key = CacheService::getSessionKey($sessionId);
        $app = App::getInstance();
        if ($app->memcached->set($key, $playerId, self::calcSessionExpires()) === false) {
            $app->logger->addDebug("memcached set failure result code:" . $app->memcached->getResultCode());
            return false;
        }

        return $sessionId;
    }

    /**
     * セッションを無効化する.
     * @param string $sessionId セッションID.
     */
    public static function invalidateSessionId($sessionId)
    {
        $key = CacheService::getSessionKey($sessionId);
        $app = App::getInstance();
        $app->memcached->delete($key);
    }

    /**
     * セッションの有効期限(次に到来する朝4:00)を計算する.
     * @return int タイムスタンプ
     */
    public static function calcSessionExpires()
    {
        //$now = time();
        //return strtotime("+10 sec") - $now;

        $now = time();
        $nowhour = (int) strftime("%k", $now);
        $sessionExpires = "tomorrow 4 am";
        if (0 <= $nowhour && $nowhour < 4) {
            $sessionExpires = "4 am";
        }

        return strtotime($sessionExpires) - $now;

    }

    /**
     * リクエストハッシュを検証する.
     * 検証に成功したら true を、失敗したら false を返す.
     * @param  array   $headers
     * @param  string  $requestUri
     * @param  string  $jsonString
     * @return boolean 検証結果.
     */
    public static function verifyRequestHash($headers, $requestUri, $jsonString)
    {
        $requestHash = $headers[self::HEADER_REQUEST_HASH_KEY];
        $expectedHash = self::getExpectedHash($headers, $requestUri, $jsonString);
        return $expectedHash === $requestHash;
    }

    /**
     * 各種リクエストパラメタから期待されるリクエストハッシュを返す.
     */
    public static function getExpectedHash($headers, $requestUri, $jsonString)
    {
        $app = App::getInstance();
        $sessionId = $headers[self::HEADER_SESSION_ID_KEY];
        if (empty($sessionId)) {
            $sessionId = '';
        }
        $baseString = self::getBaseString($sessionId, $requestUri, $jsonString);
        return self::getHash($baseString);
    }

    /**
     * Request Hash を求めるための、ベース文字列を返す.
     * @param string $sessionId
     * @param string $requestUri
     * @param string $jsonString
     */
    public static function getBaseString($sessionId, $requestUri, $jsonString)
    {
        $baseString = '';
        if (strlen($sessionId) > 0) {
            $baseString .= $sessionId . ' ';
        }
        $baseString .= $requestUri;
        if (strlen($jsonString) > 0) {
            $baseString .= " " . $jsonString;
        }
        $app = App::getInstance();
        $container = $app->getContainer();
        $baseString .= " " . $container['settings']["requesthash.secret"];

        return $baseString;
    }

    /**
     * Request Hash を返す.
     * @param string $requestUri
     */
    public static function getHash($baseString)
    {
        $hash = md5($baseString);
        return $hash;
    }
}
