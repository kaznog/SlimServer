<?php
namespace App\Services;

use \App\App;

//
// Laravel の Memcached クラスを参考に作成.
//
class MemcachedService
{
    const PERSISTENT_ID = 'pool';
    // 指定回数接続失敗でプールから削除
    const SERVER_FAILURE_LIMIT = 2;
    // タイムアウト
    const RETRY_TIMEOUT = 20;

    /**
     * The Memcached connection instance.
     *
     * @var Memcached
     */
    protected static $connection;

    /**
     * Get the Memcached connection instance.
     *
     * <code>
     *    // Get the Memcache connection and get an item from the cache
     *    $name = Memcached::connection()->get('name');
     *
     *    // Get the Memcache connection and place an item in the cache
     *    Memcached::connection()->set('name', 'Taylor');
     * </code>
     *
     * @return Memcached
     */
    public static function connection()
    {
        if (is_null(static::$connection)) {
            $app = App::getInstance();
            static::$connection = static::connect($app->getContainer()['settings']['memcached.hosts']);
            static::$connection->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
            static::$connection->setOption(\Memcached::OPT_SERVER_FAILURE_LIMIT, self::SERVER_FAILURE_LIMIT);
            static::$connection->setOption(\Memcached::OPT_RETRY_TIMEOUT, self::RETRY_TIMEOUT);
        }

        return static::$connection;
    }

    /**
     * Create a new Memcached connection instance.
     *
     * @param  array     $servers
     * @return Memcached
     */
    protected static function connect($servers)
    {
        $memcache = new \Memcached(self::PERSISTENT_ID);
        $memcache->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        $memcache->setOption(\Memcached::OPT_SERVER_FAILURE_LIMIT, self::SERVER_FAILURE_LIMIT);
        $memcache->setOption(\Memcached::OPT_RETRY_TIMEOUT, self::RETRY_TIMEOUT);
        if (!count($memcache->getServerList())) {
            $memcache->addServers($servers);
        }

        if ($memcache->getVersion() === false) {
            throw new \Exception('Could not establish memcached connection.');
        }

        return $memcache;
    }

    /**
     * Dynamically pass all other method calls to the Memcache instance.
     *
     * <code>
     *    // Get an item from the Memcache instance
     *    $name = Memcached::get('name');
     *
     *    // Store data on the Memcache server
     *    Memcached::set('name', 'Taylor');
     * </code>
     */
    public static function __callStatic($method, $arguments)
    {
        return call_user_func_array([static::connection(), $method], $arguments);
    }
}

?>