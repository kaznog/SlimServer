<?php
namespace App\Models;

/**
 * プレイヤーの1日毎の活動のまとめ情報.
 * 主にKPI集計で利用する.
 */
class PlayersDailyActivity
{
    /**
     * players_daily_activities テーブルからレコードを取得して返す.
     * もしレコードがなければ作成する (保存はしない).
     * @param  ClusterORM $playersIdentity
     * @param  string     $date
     * @return ClusterORM
     */
    public static function get($playersIdentity, $date)
    {
        $playersDailyActivity = ClusterORM::for_table('players_daily_activities')
            ->where('player_id', $playersIdentity->id)
            ->where('date', $date)
            ->find_one();
        if (!$playersDailyActivity) {
            $playersDailyActivity = ClusterORM::for_table('players_daily_activities')
                ->create(
                    [
                        'player_id' => $playersIdentity->id,
                        'date' => $date,
                        'device_platform' => $playersIdentity->device_platform,
                        'movement' => 0,
                        'visitor' => 0,
                        'likes' => 0,
                    ]
                )->set_expr('created_at', 'NOW()');
        }

        return $playersDailyActivity;
    }
}
