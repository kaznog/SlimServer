<?php
namespace App\Models;

use \App\Services\CacheService;

/**
 * マスターデータをキャッシュから取得できるようにするためのORM(Idiorm)拡張.
 */
class MasterORM extends ClusterORM
{
    // キャッシュ時間.
    public static $_cacheTtl = 3600;

    public static function for_table($table_name, $connection_name = null)
    {
        return new self($table_name, [], $connection_name);
    }

    /**
     * 指定ID(PK)のレコードを取得.
     * キャッシュがあればそこから、無ければDBから取得してキャッシュする.
     * レプリケーション遅延により古いデータがキャッシュに乗らないように、マスターDBから読む
     */
    public function get_one($id)
    {
        $key = CacheService::getMasterORMOneKey($this->_table_name, $id);

        return CacheService::get(
            $key,
            function ($id) {
                return self::for_table($this->_table_name)->find_one($id);
            },
            $id,
            $this->_cacheTtl
        );
    }

    /**
     * テーブル内すべてのレコードを取得.
     * キャッシュがあればそこから、無ければDBから取得してキャッシュする.
     * レプリケーション遅延により古いデータがキャッシュに乗らないように、マスターDBから読む
     */
    public function get_all()
    {
        $key = CacheService::getMasterORMAllKey($this->_table_name);

        return CacheService::get(
            $key,
            function () {
                return self::for_table($this->_table_name)->find_many();
            },
            null,
            $this->_cacheTtl
        );
    }

    /**
     * キャッシュをクリアする.
     * @param int $id クリアするキャッシュのID.
     */
    public function clearCache($id = null)
    {
        // 個別キャッシュをクリア.
        if (isset($id)) {
            $key = CacheService::getMasterORMOneKey($this->_table_name, $id);
            CacheService::clear($key);
        }
        // 全体キャッシュは必ずクリアする.
        $key = CacheService::getMasterORMAllKey($this->_table_name);
        CacheService::clear($key);
    }

    /**
     * 全レコードをキャッシュする.
     */
    public function warmUp()
    {
        // 一旦キャッシュをクリア.
        $this->clearCache();
        // 全レコードを読み込んでキャッシュ.
        $all = self::for_table($this->_table_name)->get_all();
        foreach ($all as $one) {
            self::for_table($this->_table_name)->get_one($one->id);
        }
    }
}
