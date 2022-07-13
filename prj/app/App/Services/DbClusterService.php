<?php
namespace App\Services;

use \App\Models\ResultCode;
use \App\App;

/**
 * データベースクラスターへの接続用サービス.
 */
class DbClusterService
{
    // クラスター一覧. $app->getContainer()['settings']['db.clusters'] で取得.
    private static $clusters;

    // 各シャードに含まれる論理データベースへの接続名.
    private static $shards;

    // テーブル毎のシャーディング設定. $app->getContainer()['settings']['db.sharding_configs'] で取得.
    private static $shardingConfigs;

    /**
     * データベースへの接続設定を \ORM クラスにセット.
     * PHP アプリケーション起動時に実行する.
     */
    public static function configure()
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        // クラスター一覧をセット.
        self::$clusters = $container['settings']['db.clusters'];
        // シャーディング設定一覧をセット.
        self::$shardingConfigs = $container['settings']['db.sharding_configs'];
        // データベースへの接続設定一覧をセット.
        $connections = $container['settings']['db.connections'];
        $shards = [];
        foreach ($connections as $shardName => $shardConnections) {
            $shards[$shardName] = [];
            // master
            $connName = "{$shardName}_master";
            \ORM::configure($shardConnections['master'], null, $connName);
            $shards[$shardName]['master'] = $connName;
            // replicas
            if (isset($shardConnections['replicas'])) {
                $i = 0;
                $shards[$shardName]['replicas'] = [];
                foreach ($shardConnections['replicas'] as $replicaConnection) {
                    $connName = "{$shardName}_replica_{$i}";
                    \ORM::configure($replicaConnection, null, $connName);
                    $shards[$shardName]['replicas'][] = $connName;
                    $i++;
                }
            }
        }
        // 論理データベースへの接続名一覧をセット.
        self::$shards = $shards;
    }

    /**
     * クラスター一覧を返す.
     * @return array
     */
    public static function getClusters()
    {
        return self::$clusters;
    }

    /**
     * 全てのシャードの接続名配列を返す.
     * @return array
     */
    public static function getShards()
    {
        return self::$shards;
    }

    /**
     * DB接続設定値を取得する.
     * @param  string $key
     * @param  string $connectionName
     * @return mixed
     */
    public static function getConfig($key, $connectionName)
    {
        return \ORM::get_config($key, $connectionName);
    }

    /**
     * クラスターに対するDBコネクション名を取得する.
     * @param  string $clusterName
     * @param  int    $shardKey
     * @param  mixed  $useMaster   true もしくは "master" であれば、マスターDBを使う. それ以外の値であれば、レプリカDBを使う.
     * @return string
     */
    public static function getConnectionName($clusterName, $shardKey = null, $useMaster = true)
    {
        $shardId = self::getShardId($clusterName, $shardKey);
        $shardNames = self::getShardNames($clusterName);
        if (isset($shardId)) {
            $shardName = $shardNames[$shardId];
        } else {
            $shardName = $shardNames;
        }
        if ($useMaster === true || $useMaster === 'master') {
            return self::$shards[$shardName]['master'];
        } else {
            // 複数レプリカがある場合は、今のところランダムで決定.
            $replicas = self::$shards[$shardName]['replicas'];
            $replicaCount = count($replicas);
            $replicaId = rand(0, $replicaCount - 1) % $replicaCount;

            return $replicas[$replicaId];
        }
    }

    /**
     * シャードIDを計算して返す.
     * @param  string $clusterName
     * @param  int    $shardKey
     * @return int
     */
    public static function getShardId($clusterName, $shardKey)
    {
        $shardNames = self::getShardNames($clusterName);

        if (is_array($shardNames)) {
            // シャーディングするクラスターで、$shardKey が指定されていなければエラー.
            if ($shardKey === null) {
                $app = App::getInstance();
                $app->logger->addNotice("{$clusterName}: Shard key is required for a sharded cluster.");
                $app->responseArray = ["resultCode" => ResultCode::DB_SHARDING_ERROR];
                $app->halt(500);
            }
            // シャード個数でシャードキーを割ったあまりをシャードIDとする.
            $shardId = $shardKey % self::getShardCount($clusterName);
        } else {
            // シャーディングしないクラスターで、$shardKey が指定されていたらエラー.
            if ($shardKey != null) {
                $app = App::getInstance();
                $app->logger->addNotice("{$clusterName}: Shard key must be null for a non-sharded cluster.");
                $app->responseArray = ["resultCode" => ResultCode::DB_SHARDING_ERROR];
                $app->halt(500);
            }
            $shardId = null;
        }

        return $shardId;
    }

    /**
     * 指定したクラスターについて、指定可能なシャードIDの配列を返す.
     * @param  string $clusterName
     * @return array  指定可能なシャードIDの配列. シャーディングしないクラスターであれば null.
     */
    public static function getShardIds($clusterName)
    {
        $shardNames = self::getShardNames($clusterName);

        if (is_array($shardNames)) {
            // シャードIDは0以上、シャード個数未満.
            $shardIds = range(0, self::getShardCount($clusterName) - 1);
        } else {
            $shardIds = null;
        }

        return $shardIds;
    }

    /**
     * MySQL の DSN を分解する.
     * @param  string $dsn
     * @return arrray $host, $dbname からなる配列.
     */
    public static function decomposeConnectionString($dsn)
    {
        preg_match('/host=([a-zA-Z0-9._-]*);dbname=([a-zA-Z0-9._-]*)/', $dsn, $matches);
        list($host, $dbname) = [$matches[1], $matches[2]];

        return [$host, $dbname];
    }

    /**
     * DBへの接続設定一覧を取得する.
     * @param  bool  $includeSlave スレーブDBも含める場合は true.
     * @return array
     */
    public static function getConnectionSettings($includeSlave = false)
    {
        $app = App::getInstance();
        $container = $app->getContainer();
        $dbConnectionConfig = $container['settings']['db.connections'];

        $connectionSettings = [];
        foreach ($dbConnectionConfig as $shardName => $shard) {
            $master = $shard['master'];
            $connectionSettings[] = self::getConnectionSetting($master);
            if ($includeSlave) {
                foreach ($shard['replicas'] as $replica) {
                    $connectionSettings[] = self::getConnectionSetting($replica);
                }
            }
        }

        return $connectionSettings;
    }

    /**
     * DB接続設定を抜き出す.
     * @param array
     * @return array
     */
    public static function getConnectionSetting($config)
    {
        list($host, $dbname) = self::decomposeConnectionString($config['connection_string']);

        return ['username' => $config['username'], 'password' => $config['username'], 'host' => $host, 'dbname' => $dbname];
    }

    /**
     * クラスターに含まれるシャード名一覧を返す.
     * @param  string $clusterName
     * @return mixed
     */
    protected static function getShardNames($clusterName)
    {
        // 指定したクラスター名に対する設定がなければエラー終了.
        if (! isset(self::$clusters[$clusterName])) {
            $app = App::getInstance();
            $app->logger->addNotice("{$clusterName} is not found.");
            $app->responseArray = ["resultCode" => ResultCode::DB_SHARDING_ERROR];
            $app->halt(500);
        }

        return self::$clusters[$clusterName];
    }

    /**
     * クラスターに含まれるシャード数を返す.
     * @param  string $clusterName
     * @return int
     */
    protected static function getShardCount($clusterName)
    {
        $shardNames = self::getShardNames($clusterName);
        $shardCount = 1;
        if (is_array($shardNames)) {
            $shardCount = count($shardNames);
        }

        return $shardCount;
    }

    /**
     * テーブルに対するシャーディング設定を取得する.
     * @param  string $tableName
     * @return array
     */
    protected static function getShardingConfig($tableName)
    {
        if (! isset(self::$shardingConfigs[$tableName])) {
            // エラー終了.
            $app = App::getInstance();
            $app->logger->addNotice("DB Sharding config for {$tableName} is not found.");
            $app->responseArray = ["resultCode" => ResultCode::DB_SHARDING_ERROR];
            $app->halt(500);
        }

        return self::$shardingConfigs[$tableName];
    }

    /**
     * 保存先クラスター名を返す.
     * @return string
     */
    public static function getClusterName($tableName)
    {
        $shardingCondig = self::getShardingConfig($tableName);

        return $shardingCondig['cluster_name'];
    }

    /**
     * シャードキーカラム名を返す.
     * @return string
     */
    public static function getShardKeyColumn($tableName)
    {
        $shardingCondig = self::getShardingConfig($tableName);

        return isset($shardingCondig['shard_key_column']) ? $shardingCondig['shard_key_column'] : null;
    }

    /**
     * テーブル名一覧を返す.
     * @return array
     */
    public static function getTables()
    {
        $tables = array_keys(self::$shardingConfigs);

        return $tables;
    }

    /**
     * 全データベースを削除し、スキーマを更新する.
     * ***** 間違って実行しないよう注意. *****
     */
    public static function initializeDatabaseSchema()
    {
        foreach (self::getShards() as $shardName => $shard) {
            $connName = $shard['master'];
            $schema_sql = file_get_contents(APP_ROOT . '/db/schema.sql');
            \ORM::raw_execute($schema_sql, [], $connName);
        }
    }
}

?>