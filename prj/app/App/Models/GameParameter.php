<?php
namespace App\Models;

use \App\Services\CacheService;

/**
 * ゲームパラメタ.
 */
class GameParameter
{
    // キャッシュ時間.
    const CACHE_TTL = 3600;

    /**
     * ゲームパラメタを取得.
     * @param  string $name
     * @return string $name に対する値が存在しなければ null.
     */
    public static function get($name)
    {
        $key = CacheService::getGameParametersKey();
        $values = CacheService::get(
            $key,
            function () {
                $gameParameters = MasterORM::for_table('game_parameters')->get_all();
                // name をキー、value を値とする連想配列を作成してキャッシュ.
                $map = [];
                foreach ($gameParameters as $gameParameter) {
                    $map[$gameParameter->name] = [
                        'value'    => $gameParameter->value,
                        'start_at' => $gameParameter->start_at,
                        'end_at'   => $gameParameter->end_at,
                        'value0'   => $gameParameter->value0,
                        'value1'   => $gameParameter->value1,
                        'value2'   => $gameParameter->value2,
                        'value3'   => $gameParameter->value3,
                        'value4'   => $gameParameter->value4,
                        'value5'   => $gameParameter->value5,
                        'value6'   => $gameParameter->value6,
                    ];
                }

                return $map;
            },
            null,
            self::CACHE_TTL
        );
        $value = null;
        if (isset($values[$name])) {
            $params = $values[$name];
            $value    = $params['value'];
            $start_at = $params['start_at'];
            $end_at   = $params['end_at'];
            if (!empty($start_at) && !empty($end_at)) {
                $now = time();
                if (strtotime($start_at) <= $now && strtotime($end_at) >= $now) {
                    $dow = date('w');
                    if (!empty($params["value{$dow}"])) {
                        $value = $params["value{$dow}"];
                    }
                }
            }
        }

        return $value;
    }

    /**
     * キャッシュを削除する.
     */
    public static function clearCache()
    {
        $key = CacheService::getGameParametersKey();
        CacheService::clear($key);
        MasterORM::for_table('game_parameters')->clearCache();
    }
}
