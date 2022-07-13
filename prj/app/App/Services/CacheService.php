<?php
namespace App\Services;

use \App\App;

/**
 * キャッシュへのデータのセット、取得用のヘルパーメソッドを提供するクラス.
 * Memcached 用のキーの定義が分散していると、重複の恐れがあるので、キャッシュのキーも CacheService 内で定義すること.
 */
class CacheService
{
    /**
     * Memcached からデータを取得する汎用メソッド.
     * Memcached に値がなければ、データを取得して Memcached にセットする.
     * @param string   $key        キャッシュキー
     * @param callable $callback   値を取得するためのコールバック.
     * @param mixed    $args       コールバックに渡す引数.
     * @param mixed    $expiration 期限切れとなるまでの時間. 詳しくはこちら http://www.php.net/manual/ja/memcached.expiration.php.
     */
    public static function get($key, $callback, $args = null, $expiration = 0)
    {
        $app = App::getInstance();
        $value = $app->memcached->get($key);
        if ($value === false) {
            $value = $callback($args, $expiration); // 有効期限が取得した値で設定される場合があるので、引数で渡す.
            // valueがfalseの場合は問題が発生していると考えられるので、後で更新を試みるようにする.
            $app->memcached->set($key, $value, $value === false ? 60 : $expiration);
        }

        return $value;
    }

    /**
     * キャッシュをクリアする.
     */
    public static function clear($key)
    {
        $app = App::getInstance();
        $app->memcached->delete($key);
    }

    /**
     * セッション保存用キー.
     * @param  string $sessionId
     * @return string
     */
    public static function getSessionKey($sessionId)
    {
        $app = App::getInstance();

        return $app->getContainer()['settings']['sessionkey.prefix'] . $sessionId;
    }

    /**
     * SQEX Bridge用 nativeSessionIdなどを保存するためのBridgeSessionObj保存用キー
     * @param string $user_id SQEX Bridge user id
     * return string
     */
    public static function getBridgeSessionObjKey($user_id)
    {
        $app = App::getInstance();

        return $app->getContainer()['settings']['sqexbridge.sess.prefix'] . $user_id;
    }

    /**
     *
     */
    public static function getMultiPlayProcessInfoKey()
    {
        $app = App::getInstance();

        return $app->getContainer()['settings']['memcached.prefix'] . "node_procinfo";
    }
    /**
     * マスターデータキャッシュ用キー (テーブル単位).
     * @param  string $table
     * @return string
     */
    public static function getMasterORMAllKey($table)
    {
        $app = App::getInstance();

        return $app->getContainer()['settings']['memcached.prefix'] . "master_orm_all_{$table}";
    }

    /**
     * マスターデータキャッシュ用キー (レコード単位).
     * @param  string $table
     * @param  int    $id
     * @return string
     */
    public static function getMasterORMOneKey($table, $id)
    {
        $app = App::getInstance();

        return $app->getContainer()['settings']['memcached.prefix'] . "master_orm_one_{$table}_{$id}";
    }

    /**
     * GameParameter用キー.
     * @return string
     */
    public static function getGameParametersKey()
    {
        $app = Slim::getInstance();

        return $app->config("memcached.prefix") . "game_parameters_all";
    }

    /**
     * メンテナンス状態用キー.
     * @return string
     */
    public static function getMaintenanceKey()
    {
        $app = App::getInstance();

        return $app->getContainer()['settings']['memcached.prefix'] . "maintenance";
    }

    /**
     * アプリバージョン用キー.
     * @param  int    $platform
     * @return string
     */
    public static function getAppVersionKey($platform)
    {
        $app = App::getInstance();

        return $app->getContainer()['settings']['memcached.prefix'] . "app_version_{$platform}";
    }

    /**
     * ログインボーナスキャッシュ用キー.
     * @param  int    $day
     * @return string
     */
    public static function getLoginBonusKey($day)
    {
        $app = App::getInstance();

        return $app->getContainer()['settings']['memcached.prefix'] . "login_bonus_{$day}";
    }

    /**
     * ログインボーナスの繰り返し日数キャッシュ用キー.
     * @return string
     */
    public static function getLoginBonusCycleDayKey()
    {
        $app = App::getInstance();

        return $app->getContainer()['settings']['memcached.prefix'] . "login_bonus_cycle_day";
    }

}
