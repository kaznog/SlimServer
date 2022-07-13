<?php
namespace App\Models;

use \App\Services\CacheService;

/**
 * ログインボーナス
 */
class LoginBonus
{
    /**
     * ログインボーナスを取得する.
     * @param  int        $day 連続ログイン日数.
     * @return ClusterORM LoginBonus オブジェクト. ログインボーナスがなければ false.
     */
    public static function getLoginBonus($day)
    {
        $cycleDayCount = self::getCycleDayCount();
        if (!$cycleDayCount) {
            return false;
        }
        $dayInCycle = ($day - 1) % $cycleDayCount + 1;

        // 連続ログイン日数が login_bonuses.login_cnt の最大値を超えると、1日目 から繰り返す.
        $key = CacheService::getLoginBonusKey($dayInCycle);
        $loginBonus = CacheService::get(
            $key,
            function ($dayInCycle) {
                return MasterORM::for_table('login_bonuses')
                    ->where_equal('login_cnt', $dayInCycle)
                    ->get_one($dayInCycle);
            }
        );

        return MasterORM::for_table('login_bonuses')->get_one($dayInCycle);
    }

    /**
     * ログインボーナスの繰り返し日数(= login_bonuses.login_cnt の最大値)を返す.
     * @return int
     */
    protected static function getCycleDayCount()
    {
        $key = CacheService::getLoginBonusCycleDayKey();

        return CacheService::get(
            $key,
            function () {
                return ClusterORM::for_table("login_bonuses")
                    ->max("login_cnt");
            }
        );
    }
}
