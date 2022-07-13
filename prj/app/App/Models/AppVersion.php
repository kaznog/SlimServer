<?php
namespace App\Models;

use \App\Services\CacheService;
use \App\App;

/**
 * アプリバージョン
 */
class AppVersion
{
    /**
     * インスタンスを取得する.
     * @param  int        $platform
     * @return ClusterORM
     */
    public static function get($platform)
    {
        if (!in_array($platform, [Platform::PLATFORM_IOS, Platform::PLATFORM_ANDROID])) {
            // エラー終了.
            $app = App::getInstance();
            $app->responseArray = ["resultCode" => ResultCode::INVALID_PARAMETERS];
            $app->halt(200);
        }

        $key = CacheService::getAppVersionKey($platform);
        $appVersion = CacheService::get(
            $key,
            function () use ($platform) {
                $appVersion = ClusterORM::for_table('app_versions')->find_one($platform);
                // DBにレコードがなければ作成.
                if (! $appVersion) {
                    $appVersion = ClusterORM::for_table('app_versions')
                        ->create(
                            [
                                'id' => $platform,
                                'required_version' => '0.0.1',
                                'applying_version' => '1.0.0',//null,
                                'abdb_version' => 0,  // asset bundle database version
                            ]
                        )->set_expr('created_at', 'NOW()');
                    $appVersion->save();
                }

                return $appVersion;
            }
        );

        return $appVersion;
    }

    /**
     * キャッシュをクリアする.
     */
    public static function clearCache()
    {
        foreach ([Platform::PLATFORM_IOS, Platform::PLATFORM_ANDROID] as $platform) {
            $key = CacheService::getAppVersionKey($platform);
            CacheService::clear($key);
        }
    }
}
