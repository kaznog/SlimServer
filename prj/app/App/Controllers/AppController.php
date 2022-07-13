<?php
namespace App\Controllers;

use \App\Models\ResultCode;
use \App\Models\AppVersion;
use \App\Models\Platform;

class AppController
{
    /**
     * API: アプリバージョン確認
     */
	public static function checkVersion($app, $args)
	{
        $resultCode = ResultCode::SUCCESS;
        $container = $app->getContainer();
        // $platform = (int) $container->get('request')->getParam('platform');
        // $version = $container->get('request')->getParam('appVersion');
        $app->logService->logger('admin')->addDebug(__CLASS__ . ':' . __FUNCTION__ . " args:" . serialize($args));
        $platform = $args['platform'] ? (int)$args['platform'] : Platform::PLATFORM_IOS;
        $version = $args['appVersion'] ? $args['appVersion'] : '0.0.1';
        $abver = 1;

        if (! $version) {
            $resultCode = ResultCode::OUTDATED;
        } else {
            $appVersion = AppVersion::get($platform);
            if (version_compare($version, $appVersion->applying_version, '==')) {
                $resultCode = ResultCode::APPLYING;
            } elseif (version_compare($version, $appVersion->required_version, '<')) {
                $resultCode = ResultCode::OUTDATED;
            }
            $abver = $appVersion->abdb_version;
		}	
        $app->responseArray = [
            'abVer' => (int) $abver,
            'resultCode' => $resultCode
        ];
	}

    /**
     * API: ヘルスモニター用 Deep チェック
     * 成功: HTTP Status 200
     * 失敗: HTTP Status 500 (DB障害)
     */
    public static function healthDeep($app, $args)
    {
        // common
        $identitie = ClusterORM::for_table('players_identities')->select('id')->find_one();

        // player
        $clusterName = DbClusterService::getClusterName('players');
        $shardIds = DbClusterService::getShardIds($clusterName);
        foreach ($shardIds as $shardId) {
            $players = ClusterORM::for_table('players')
                ->use_replica()
                ->select_shard($shardId)
                ->select('id')
                ->find_one();
        }

        $app->responseArray = [
            'resultCode' => ResultCode::SUCCESS
        ];
    }

}
?>