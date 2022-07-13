<?php
namespace App\Services;

use \App\Models\ResultCode;
use \App\Models\ClusterORM;
use \App\Models\MasterORM;
use \App\Models\GameParameter;
use \App\Models\PlayersIdentity;
use \App\Models\Player;
use \App\Models\PlayersSave;
use \App\App;

/**
 * プレイヤー関連処理のサービスクラス.
 */
class PlayerService
{
    /**
     * プレイヤー情報を取得する.
     * @param  int               $playerId
     * @return [PlayersIdentity, Player]
     */
    public function get($playerId)
    {
        $playersIdentity = ClusterORM::for_table('players_identities')->where('id', $playerId)->find_one();
        $player = ClusterORM::for_table('players')->where('id', $playerId)->find_one();

        return [$playersIdentity, $player];
    }

    /**
     * プレイヤー情報を更新する.
     * null を指定した項目は更新しない.
     * @param int   $playerId
     * @param array $params
     */
    public function setParameters($playerId, $params)
    {
        $name = isset($params->name) ? $params->name : null;
        $gender = isset($params->gender) ? $params->gender : null;
        $location = isset($params->location) ? $params->location : null;

        if ($name || $gender || $location) {
            $playersIdentity = ClusterORM::for_table('players_identities')->find_one($playerId);
            if ($name) {
                $playersIdentity->name = $name;
            }
            if ($gender) {
                $playersIdentity->gender = $gender;
            }
            if ($location !== null) {
                $playersIdentity->location = $location; // 0:北海道 ~ 46:沖縄
            }
            $playersIdentity->save();
        }
    }

    /**
     * フレンド数上限を追加する.
     * @param  int        $playerId
     * @return ClusterORM
     */
    public function increaseFriendCapacity($playerId)
    {
        $cost       = GameParameter::get('EXTRA_FRIEND_CAPACITY_RYO_COST');
        $increase   = GameParameter::get('EXTRA_FRIEND_CAPACITY_INCREASE');
        $max        = GameParameter::get('EXTRA_FRIEND_CAPACITY_MAX');
        // エラー時に追加するレスポンス
        $additional_responce = ['friendExtra' => [(int) GameParameter::get('EXTRA_FRIEND_CAPACITY_RYO_COST'),(int) GameParameter::get('EXTRA_FRIEND_CAPACITY_INCREASE'),(int) GameParameter::get('EXTRA_FRIEND_CAPACITY_MAX') ] ];

        $app = App::getInstance();

        $db = ClusterORM::for_table('players')
            ->select_shard($playerId)
            ->current_db();
        try {
            $db->beginTransaction();

            $player = ClusterORM::for_table('players')->find_one($playerId);

            // 上限チェック, フレンド数上限追加
            if ($player->extra_friend_capacity + $increase > $max) {
                $app->responseArray = ["resultCode" => ResultCode::EXTRA_FRIEND_CAPACITY_MAX_EXCEEDED] + $additional_responce;
                $app->halt(200);
            }
            $player->extra_friend_capacity += $increase;

            // // 両消費.
            // Player::spendRyo($player, $cost, PlayersRyoConsumeHistory::USAGE_EXTRA_FRIEND_CAPACITY, null, $additional_responce);
            $player->save();

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            $app->logger->addNotice("{$e}");
            if ($e instanceof \Slim\Exception\SlimException) {
                throw $e;
            } else {
                // エラー終了.
                $app->responseArray = ["resultCode" => ResultCode::UNKNOWN_ERROR] + $additional_responce;
                $app->halt(500);
            }
        }

        return $player;
    }

    /**
     * 経験値を加算して保存する.
     * @param  PlayersIdentity $playersIdentity
     * @param  Player          $player
     * @param  int             $exp
     * @return bool            レベルアップしたら true, していなければ false.
     */
    public function addExp($playersIdentity, $player, $exp)
    {
        $levelUp = false;
        $level = $this->getLevel($player->level); // 現在のレベル.
        $nextLevel = $this->getLevel($player->level + 1); // 次のレベル.
        if (!$nextLevel) {
            return false; // レベル最大の時は何もしない
        }

        $player->exp += $exp;
        $player->level_exp += $exp;
        while ($level && $nextLevel && $player->level_exp >= $level->exp_next) {
            // レベルアップ
            $levelUp = true;
            $player->level++;
            $player->level_exp -= $level->exp_next;
            $level = $nextLevel; // 現在のレベル.
            $nextLevel = $this->getLevel($player->level + 1); // 次のレベル.
            if (!$nextLevel) {
                // レベルMAXの場合経験値をカンストさせる
                $player->exp -= $player->level_exp;
                $player->level_exp = 0;
            }
        }
        $player->save();

        // レベルアップしていたら、players_identities も更新.
        if ($levelUp) {
            $playersIdentity->level = $player->level;
            $playersIdentity->save();
        }

        return $levelUp;
    }

    /**
     * レベルデータを取得する.
     * @param  int       $level
     * @return MasterORM
     */
    protected function getLevel($level)
    {
        return MasterORM::for_table('levels')->get_one($level);
    }

    /**
     * 「毎日朝4:00に1日が始まる(セッションが切れる)」を管理するための、直近の過去の朝4時を取得する.
     * @return int タイムスタンプ
     */
    public static function calcLastBeginningOfDay()
    {
        $now = time();
        $nowhour = (int) strftime("%k", $now);
        $bod = "4 am";
        if (0 <= $nowhour && $nowhour < 4) {
            $bod = "yesterday 4 am";
        }

        return strtotime($bod);
    }

    /**
     * プレイヤーのセーブデータを保存する
     * @param int    $playerId
     * @param string $data
     */
     public static function saveData($playerId,$data)
     {
        $db = ClusterORM::for_table('players_saves')
            ->select_shard($playerId)
            ->current_db();
        try {
            $db->beginTransaction();

            PlayersSave::put($playerId,$data);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            $app = App::getInstance();
            $app->logger->addNotice("{$e}");
            if ($e instanceof \Slim\Exception\SlimException) {
                throw $e;
            } else {
                // エラー終了.
                $app->responseArray = ["resultCode" => ResultCode::UNKNOWN_ERROR];
                $app->halt(500);
            }
        }
    }

    /**
     * プレイヤーのセーブデータを取得する
     * @return string セーブデータ
     */
     public static function loadData($playerId)
     {
        $savedata = PlayersSave::get($playerId);

        return $savedata->data;
     }

}
