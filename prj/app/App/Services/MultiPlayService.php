<?php
namespace App\Services;

use \App\Services\RedisService;
use \App\Models\ClusterORM;
use \App\Models\ResultCode;
use \App\Models\BattleField;
use \App\Models\Town;
use \App\App;

class MultiPlayService
{
	const RT_CURRENT_VERSION        = 10200;    // 変更時は nodejs 側の label.jsも同じにすること
	const RT_SERVER_MULTI			= 0;		// マルチ用
	const RT_SERVER_PVP_MATCHING	= 1;		// PVPマッチング用（チーム編成兼用）
	const RT_SERVER_PVP_BATTLE		= 2;		// PVPバトル用

    // バトルステータス battle_fields
    const BATTLE_STATUS_NONE   = 0; //募集前
    const BATTLE_STATUS_ENTRY  = 1; //募集中
    const BATTLE_STATUS_WAIT   = 2; //開始前
    const BATTLE_STATUS_BATTLE = 3; //バトル中
    const BATTLE_STATUS_JUDGE  = 4; //集計中
    const BATTLE_STATUS_END    = 5; //終了
    const BATTLE_STATUS_ABORT  = 6; //中断（不成立）

	public static function createRoom($playersIdentity, $field_type)
	{
		$battle_field = BattleField::create($playersIdentity, $field_type);

		// todo other
		return $battle_field;
	}

	// プロセス情報取得
	public static function getProcessInfo()
	{
		$app = App::getInstance();
		$key = CacheService::getMultiPlayProcessInfoKey();
		$processInfo = CacheService::get(
			$key,
			function () {
				$app = App::getInstance();
				$hosts = $app->getContainer()['settings']['nodejs.hosts'];
				$processInfo = [];
				$redis = new RedisService(RedisService::TYPE_KVS_SLAVE);
				foreach ($hosts as $host) {
					$host_key = RedisService::PROCESS_INFO_PREFIX . $host['id'];
					$app->logger->addDebug("redis get key[" . $host_key . "]");
					$jdat = $redis->getConnection()->get($host_key);
					if ( $jdat == null ) {
						// リアルタイムサーバが落ちている場合
						$pinfo = [];
						$pinfo['hostId']	= $host['id'];
						$pinfo['version']	= 0;
						$pinfo['down']		= true;
						$pinfo['working']	= false;
						$pinfo['sockets']	= 0;
						$pinfo['rooms']		= 0;
					} else {
						$pinfo = json_decode($jdat, true);
						$pinfo['down'] = false;
					}
					$pinfo['url'] = $host['url'];
					
					$processInfo[] = $pinfo;

				}
				return $processInfo;
			},
			null,
			4
		);
		return $processInfo;
	}

	// サーバ選択
	public static function selectServer(Array $processInfo, $hostId=false)
	{
		if ( $hostId !== false ) {
			// workingがfalseでもプロセスは動作しているので接続しに行く
			foreach ( $processInfo as $proc ) {
				if ( $proc['down'] === false && $proc['hostId'] == $hostId )		// バージョンは見ない
					return $proc;
			}
			// 見つからない場合はエラー
			return false;
		}

		$working_list = [];
		$sockets_average  = 0;
		
		$curVer = intval(self::RT_CURRENT_VERSION/100);
		foreach ( $processInfo as $proc ) {
			$ver = intval($proc['version']/100);
			if ( $proc['working'] && $curVer == $ver ) {
				$working_list[] = $proc;
				$sockets_average += $proc['sockets'];
			}
		}
		
		if ( empty($working_list) )
			return false;
		
		if ( count($working_list) == 1 )
			return $working_list[0];
		
		// 平均以下のプロセス抽出
		$below_average_list = [];
		$sockets_average = $sockets_average / count($working_list);
		foreach ( $working_list as $proc ) {
			if ( $proc['sockets'] <= $sockets_average )
				$below_average_list[] = $proc;
		}
		
		// 優先
		if ( count($below_average_list) >= 2 ) {
			$working_list = $below_average_list;
		}
		
		$app = App::getInstance();
		$redis = new RedisService(RedisService::TYPE_KVS_MASTER);
		// $ret = false;
		// $redis->getConnection()->watch(RedisService::MULTIPLAY_SELECT_SERVER_COUNTER);
		// do {
		// 	$ret = $redis->getConnection()->multi()->incr(RedisService::MULTIPLAY_SELECT_SERVER_COUNTER)->exec();
		// } while($ret == false);
		// $redis->getConnection()->discard();
		// $redis->getConnection()->unwatch();
		$ret = $redis->getConnection()->incr(RedisService::MULTIPLAY_SELECT_SERVER_COUNTER);

		// $app->logger->addDebug("counter result: ".$ret[0]);
		// $procNo = $ret[0];
		$app->logger->addDebug("counter result: ".$ret);
		$procNo = $ret;
		// $procNo = $redis->getConnection()->incr(RedisService::MULTIPLAY_SELECT_SERVER_COUNTER);
		// if ( $procNo >= 100000 ) {
		// 	$procNo = 0;
		// 	if (!$redis->getConnection()->set(RedisService::MULTIPLAY_SELECT_SERVER_COUNTER, $procNo)) {
		// 		$app->logger->addDebug(__CLASS__ . "::" . __FUNCTION__ . " REDIS SET ERROR");
		// 		$app->responseArray = ["resultCode" => ResultCode::REDIS_SET_ERROR];
		// 		$app->halt(200);
		// 	}
		// }
		$procNo = $procNo % count($working_list);
		return $working_list[$procNo];		
	}

	// token発行
	public static function requestToken($playerId, $serverType, $bBeginner, $rtInfo, $pvpPlay, $caravan, $teamId, $teamName)
	{
		try {
			// token発行
			list($msec, $now) = explode(' ', microtime());
			$token     = md5('#AUTHKEY#'.$playerId.'#'.$now.'#'.$msec);
			
			$expire    = 60;						// 60sec
			$msec      = intval($msec*1000);
			$pvp = '';
			
			$convMatchId = '';
			$pvpRate    = 0;
			// $matchDiff  = PVP_MATCH_DIFF;
			$aMatchRate = null;
			// switch ( $serverType ) {
			// 	case RT_SERVER_MULTI:
			// 		break;
			// 	case RT_SERVER_PVP_MATCHING:
			// 		{
			// 			$pvpRate     = $pvpPlay->pvpData->rate;
			// 			$regulation  = unserialize(gzuncompress(base64_decode($rtInfo->regulation)));
			// 			$convMatchId = PvPUtil::convId2Str($rtInfo->matchId);
			// 			$aMatchRate  = PvPUtil::getMatchRate($regulation->battleType, $pvpRate, $regulation->magnification, $rtInfo->aIgnore);
			// 			Log::getInstance()->debug('matching rate:'.$pvpRate.' matchRate:'.json_encode($aMatchRate));
			// 		}
			// 		break;
			// 	case RT_SERVER_PVP_BATTLE:
			// 		$pvpRate     = $pvpPlay->pvpData->rate;
			// 		$convMatchId = PvPUtil::convId2Str($rtInfo->matchId);
			// 		Log::getInstance()->debug('pvp rate:'.$pvpRate);
			// 		break;
			// 	default:
			// 		return array('res' => RES_FAILURE_PARAM);
			// }
			
			$data = json_encode(
				array(
					'roomType'    => $serverType,
					'playerId'    => $playerId,
					'date'        => $now,
					'level'       => 0,
					'bBeginner'   => $bBeginner,
					'regulation'  => null,
					'msec'        => $msec,
				)
			);
			
			$redis = new RedisService(RedisService::TYPE_KVS_MASTER);
			$redis->getConnection()->setex(RedisService::COOKIE_CONNECTION_PREFIX.$token, $expire, $data);
		} catch ( Exception $ex ) {
			$app = App::getInstance();
			$app->logger->addDebug(__CLASS__ . "::" . __FUNCTION__ . " [Exception]: " . $ex->getMessage());
			return ['res' => ResultCode::UNKNOWN_ERROR];
		}
		
		return ['res' => ResultCode::SUCCESS, 'token' => $token];
	}

	// joinできるタウンを返却
	public static function getTownList()
	{
		$towns = [];
		$towns_orm = ClusterORM::for_table('towns')
		->select_shard(null)
		->raw_query("SELECT * FROM towns WHERE entries < max_entries")
		->find_many();
		$app = App::getInstance();
		//$app->logger->addNotice(__CLASS__."::".__FUNCTION__." towns:" . var_export($towns_orm, true));
		foreach ($towns_orm as $town_orm) {
			//$app->logger->addNotice(__CLASS__."::".__FUNCTION__." town :" . var_export($town_orm, true));
			$town = new \stdClass();
			$town->id          = (int)$town_orm->id;
			$town->inquiry_id   = $town_orm->inquiry_id;
			$town->description = $town_orm->description;
			$town->max_entries = (int)$town_orm->max_entries;
			$town->entries     = (int)$town_orm->entries;
			$towns[] = $town;
		}
		return $towns;
	}

	public static function entryTown($townId)
	{
		$app = App::getInstance();
		$ret = false;
		$playerId = $app->playerId;

		$entry_key = RedisService::MULTIPLAY_TOWN_ENTRY_PREFIX . $playerId;
		$town_key = RedisService::MULTIPLAY_TOWN_TRANSACTION_PREFIX . $townId;
		$redis = new RedisService(RedisService::TYPE_KVS_MASTER);


		//--------------------------------------------------------------------------------------
		// DBトランザクション処理のデッドロック回避のためのredisロック処理
		// 同一ユーザーが連打などで連続してタウン予約した場合でも、
		// redisキーでのロックが行われるので
		// キーが削除されて個々の処理を通過出来た際には
		// ユーザーの予約情報は残っているのでDBトランザクション処理までは実行されない想定の処理
		if (!$redis->getConnection()->setNx($town_key, true)) {
			// すでにキーが設定されている場合は
			// 他のプロセスでトランザクションを開始しているので
			// デッドロックしないようにクライアントにリトライさせるレスポンスを返却する
			$app->responseArray = [ "resultCode" => ResultCode::REQUEST_RETRY ];
			// ここでhaltしても下のcatchには影響しないのでキーは削除されない
			$app->halt(200);
		}

		// 他のユーザーが該当タウンIDでの予約処理をしていない状態でタウン予約ができる状態になった
		// 本処理を実行させているユーザーだけが該当タウンへの予約ができる状態

		// 仮に処理が中断されてもcatchできなくても、3秒たったら自動削除
		$redis->getConnection()->setTimeout($town_key, 3);

		//--------------------------------------------------------------------------------------
		// 予約済み状態での再予約防止と、
		// クライアント側でタウン選択の連打対応がなされていないことへの対処

		// タウン予約をしてきたユーザーがすでにタウン予約をしているかどうか確認
        $town_entry_reserve = ClusterORM::for_table('town_entry_reserves')
		->select_shard(null)
		->find_one($playerId);

		if ($town_entry_reserve !== false) {
			if ($town_entry_reserve->town_id != -1) {
				// すでにタウン予約をしている場合は、
				// タウン予約処理中のredisキーを削除して「予約済み」レスポンスを返却して処理を終了する
				$redis->getConnection()->delete($town_key);
				$app->logger->addNotice("entryTown reserve town exists. playerId[" . $app->playerId . "] request townId[" . $townId . "] reserve town_id[" . $town_entry_reserve->town_id . "]");
				$app->responseArray = [ "resultCode" => ResultCode::TOWN_RESERVED, "reserveTownId" => $town_entry_reserve->town_id ];
				$app->halt(200);
			}
		}
		//--------------------------------------------------------------------------------------
		// ユーザーはタウン予約をしていない状態なのでDBへのトランザクションを開始する
		// タウン予約トランザクション処理
		$db = ClusterORM::for_table('towns')->select_shard(null)->current_db();
		try {
			// トランザクション開始
			$db->beginTransaction();

			ClusterORM::for_table('towns')
			->select_shard(null)
			->rawexecute("SET innodb_lock_wait_timeout=20;");

			$town = new Town();
			$town->forUpdate($townId);
			// $town = ClusterORM::for_table('towns')
			// ->select_shard(null)
			// ->raw_query("SELECT * FROM towns WHERE inquiry_id = ? for update", [$townId])
			// ->find_one();

			// $update_entries = $town->entries + 1;
			$update_entries = $town->getEntries() + 1;
			// if ($update_entries > $town->max_entries) {
			if ($update_entries > $town->getMaxEntries()) {
				// $app->logger->addNotice(__CLASS__."::".__FUNCTION__." update_entries[".$update_entries."] max_entries[".$town->getMaxEntries()."] orm[".serizlize($town->_orm)."]");
				// エラー：参加者数上限
				$app->responseArray = [ "resultCode" => ResultCode::MULTI_SERVER_TOWN_ENTRY_FULL ];
				// ここでhaltすると、
				// 下のcatchで
				//  ・キーの削除
				//  ・rollback(この段階ではそもそもrollbackの必要もないのだが。)
				// がされる
				$app->halt(200);
			}
			// $town->entries = $update_entries;
			$town->setEntries($update_entries);
			$town->save();

            $player = ClusterORM::for_table('players')
                ->select_shard($playerId)
                ->find_one($playerId);
            if ($player === false) {
                // エラー： プレイヤーが存在しない
                $app->responseArray = ["resultCode" => ResultCode::PLAYER_NOT_FOUND];
                $app->halt(200);
            }
            // town_idが未設定の場合は登録
            if ($player->town_id == -1) {
	            $player->town_id = $town->getId();
	            $player->save();
            }

            $town_entry_reserve = ClusterORM::for_table('town_entry_reserves')
			->select_shard(null)
			->find_one($playerId);
			$expire_date = date('Y-m-d H:i:s', strtotime('+60 sec'));
			if ($town_entry_reserve !== false) {
				$town_entry_reserve->town_id = $town->getId();
				$town_entry_reserve->expire  = $expire_date;
			} else {
				$town_entry_reserve = ClusterORM::for_table('town_entry_reserves')
				->create(
					[
						'id'      => $playerId,
						'town_id' => $town->getId(),
						'expire'  => $expire_date
					]
				);
			}
			$town_entry_reserve->save();
			$data = json_encode(
				[
					'townId' => $town->getInqueryId()
				]
			);
			$redis->getConnection()->set($entry_key, $data);
			$db->commit();
		} catch(\Exception $e) {
			$db->rollback();
			// 処理が中断されてもキーは削除しないと何もできなくなるので削除
			$redis->getConnection()->delete($town_key);
			$app = App::getInstance();
			$app->logger->addNotice("{$e}");
            if ($e instanceof \Slim\Exception\SlimException) {
                throw $e;
			} else {
				// エラー終了
				$app->responseArray = [ ResultCode::UNKNOWN_ERROR ];
				$app->halt(200);
			}			
			return false;
		}
		// 正常終了できる場合は予定通りキーを削除
		$redis->getConnection()->delete($town_key);
		return true;
	}

	// プレイヤーがタウンに入室したらAPIからタウン入室予約を消す処理
	public static function unsetTownEntryReserve()
	{
		$app = App::getInstance();
		$playerId = $app->playerId;
		$entry_key = RedisService::MULTIPLAY_TOWN_ENTRY_PREFIX . $playerId;
		$redis = new RedisService(RedisService::TYPE_KVS_MASTER);
		$db = ClusterORM::for_table('town_entry_reserves')
		->select_shard(null)->current_db();
		try {
        	// トランザクション開始
        	$db->beginTransaction();
			ClusterORM::for_table('town_entry_reserves')
			->select_shard(null)
			->rawexecute("SET innodb_lock_wait_timeout=20;");

			$town_entry_reserves = ClusterORM::for_table('town_entry_reserves')
			->select_shard(null)
			->raw_query("SELECT * FROM town_entry_reserves WHERE id = ? for update", [$playerId])
			->find_one();

			$app->logger->addNotice(__CLASS__ . "::" . __FUNCTION__ . " playerId[" . $playerId . "] town_id[" . $town_entry_reserves->town_id . "]");
			echo __CLASS__ . "::" . __FUNCTION__ . " playerId[" . $playerId . "] town_id[" . $town_entry_reserves->town_id . "]\n";
			$town_entry_reserves->town_id = -1;
			$town_entry_reserves->expire  = null;
			$town_entry_reserves->save();
			$redis->getConnection()->delete($entry_key);
			$db->commit();
		} catch (\Exception $e) {
			$db->rollback();
			$app->logger->addNotice("{$e}");
            if ($e instanceof \Slim\Exception\SlimException) {
                throw $e;
			} else {
				// エラー終了
				$app->responseArray = [ ResultCode::UNKNOWN_ERROR ];
				$app->halt(200);
			}			
			return false;
		}
		return true;
	}

	// 定期的にタウン入室予約期限を過ぎた予約分減算してを入室可能数回復する
	public static function leaveTownEntryReserve()
	{
		$app = App::getInstance();
		$now_date = date('Y-m-d H:i:s', strtotime('now'));
		echo $now_date . " " . __CLASS__ . "::" . __FUNCTION__ . "start\n";
        $db = ClusterORM::for_table('towns')->select_shard(null)->current_db();
		$reserveTownEntrys = ClusterORM::for_table('town_entry_reserves')
		->select_shard(null)
		->raw_query("SELECT town_id, count(id) as count FROM town_entry_reserves WHERE expire < '". $now_date . "' GROUP BY id")
		->find_many();
		echo var_dump($reserveTownEntrys) . "\n";
		// echo var_export($reserveTownEntrys[0]->town_id, true) . "\n";
		$redis = new RedisService(RedisService::TYPE_KVS_MASTER);
		foreach ($reserveTownEntrys as $townEntrys) {
			echo $townEntrys->town_id . "\n";
			echo $townEntrys->count . "\n";
			$townId = $townEntrys->town_id;
			echo $now_date . " " . __CLASS__ . "::" . __FUNCTION__ . " decriment " . $townEntrys->count . " start\n";
			$town_key = RedisService::MULTIPLAY_TOWN_TRANSACTION_PREFIX . $townId;
			while (!$redis->getConnection()->setNx($town_key, true)) {
				// wait 100 milli second.
				usleep(100);
			}
			// 仮に処理が中断されてもcatchできなくても、3秒たったら自動削除
			$redis->getConnection()->setTimeout($town_key, 3);
	        try {
	        	// トランザクション開始
	        	$db->beginTransaction();

				ClusterORM::for_table('towns')
				->select_shard(null)
				->rawexecute("SET innodb_lock_wait_timeout=20;");

				$town = new Town();
				$town->forUpdateByTownId($townId);

				$update_entries = $town->getEntries() - $townEntrys->count;
				if ($update_entries < 0) {
					$update_entries = 0;
				}
				$town->setEntries($update_entries);
				$town->save();

	        	$db->commit();
	        } catch (\Exception $e) {
				$db->rollback();
				// 処理が中断されてもキーは削除しないと何もできなくなるので削除
				$redis->getConnection()->delete($town_key);
				$app->logger->addNotice("{$e}");
				echo __CLASS__ . "::" . __FUNCTION__ . " Exception:{$e}\n";
	        }
			// 正常終了できる場合は予定通りキーを削除
			$redis->getConnection()->delete($town_key);
		}
		$reserveTownEntrys = ClusterORM::for_table('town_entry_reserves')
		->select_shard(null)
		->raw_query("SELECT * FROM town_entry_reserves WHERE expire < '". $now_date . "'")
		->find_many();
		foreach ($reserveTownEntrys as $townEntrys) {
			try {
	        	// トランザクション開始
	        	$db->beginTransaction();
				ClusterORM::for_table('town_entry_reserves')
				->select_shard(null)
				->rawexecute("SET innodb_lock_wait_timeout=20;");

				$townEntrys->town_id = -1;
				$townEntrys->expire = null;
				$townEntrys->save();

				// 入室予約期限を過ぎたらnodejs側で入室できないリターンコードを返すためにredisキーも削除する
				$entry_key = RedisService::MULTIPLAY_TOWN_ENTRY_PREFIX . $townEntrys->id;
				$redis->getConnection()->delete($entry_key);

				// nodejsへの接続数もselectServer時にインクリメントしているので、
				// 入ってこなかったから減算する
				$ret = $redis->getConnection()->decr(RedisService::MULTIPLAY_SELECT_SERVER_COUNTER);
				$app->logger->addDebug("MP_SS_CNTR decr result: ".$ret);
				echo "MP_SS_CNTR decr result: {$ret}\n";
				if ($ret < 0) {
					// 減算した結果マイナスになってしまった場合はリセットする
					$redis->getConnection()->set(RedisService::MULTIPLAY_SELECT_SERVER_COUNTER, 0);
					$app->logger->addDebug("MP_SS_CNTR reset");
					echo "MP_SS_CNTR reset\n";
				}

	        	$db->commit();
			} catch (\Exception $e) {
				$db->rollback();
				$app = App::getInstance();
				$app->logger->addNotice("{$e}");
				echo __CLASS__ . "::" . __FUNCTION__ . " Exception:{$e}\n";
			}
		}
	}

	public static function leaveTownForAMQPRecieve($townId)
	{
		$town_key = RedisService::MULTIPLAY_TOWN_TRANSACTION_PREFIX . $townId;
		$redis = new RedisService(RedisService::TYPE_KVS_MASTER);

		while (!$redis->getConnection()->setNx($town_key, true)) {
			// wait 100 milli second.
			usleep(100);
		}
		// 仮に処理が中断されてもcatchできなくても、3秒たったら自動削除
		$redis->getConnection()->setTimeout($town_key, 3);
		$db = ClusterORM::for_table('towns')->select_shard(null)->current_db();
		try {
			// トランザクション開始
			$db->beginTransaction();

			ClusterORM::for_table('towns')
			->select_shard(null)
			->rawexecute("SET innodb_lock_wait_timeout=20;");

			$town = new Town();
			$town->forUpdate($townId);
			// $town = ClusterORM::for_table('towns')
			// ->select_shard(null)
			// ->raw_query("SELECT * FROM towns WHERE inquiry_id = '?' for update", [$townId])
			// ->find_one();

			// $update_entries = $town->entries - 1;
			$update_entries = $town->getEntries() - 1;
			if ($update_entries < 0) {
				$update_entries = 0;
			}
			// $town->entries = $update_entries;
			// $town->save();
			$town->setEntries($update_entries);
			$town->save();
			$db->commit();
		} catch(\Exception $e) {
			$db->rollback();
			// 処理が中断されてもキーは削除しないと何もできなくなるので削除
			$redis->getConnection()->delete($town_key);
			$app = App::getInstance();
			$app->logger->addNotice("{$e}");
			return false;
		}
		// 正常終了できる場合は予定通りキーを削除
		$redis->getConnection()->delete($town_key);
		return true;
	}
}