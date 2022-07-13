<?php
namespace App\Models;

use \App\Services\CacheService;
use \App\Models\ClusterORM;

/**
 * メンテナンス状態
 */
class Maintenance
{
    // ID: 固定値1
    const ID = 1;

    /**
     * ただ一つのインスタンスを取得する.
     * @return ClusterORM
     */
    public static function get()
    {
        $key = CacheService::getMaintenanceKey();
        $maintenance = CacheService::get(
            $key,
            function () {
                $maintenance = ClusterORM::for_table('maintenances')->find_one(self::ID);
                // DBにレコードがなければ作成.
                if (! $maintenance) {
                    $maintenance = ClusterORM::for_table('maintenances')
                        ->create(
                            [
                                'id' => self::ID,
                                'start_at' => null,
                                'end_at' => null,
                                'message' => ''
                            ]
                        )->set_expr('created_at', 'NOW()');
                    $maintenance->save();
                }

                return $maintenance;
            }
        );

        return $maintenance;
    }

    /**
     * キャッシュをクリアする.
     */
    public static function clearCache()
    {
        $key = CacheService::getMaintenanceKey();
        CacheService::clear($key);
    }
}
