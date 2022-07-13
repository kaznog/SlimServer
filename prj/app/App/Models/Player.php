<?php
namespace App\Models;

use \App\App;

/**
 * プレイヤーモデル.
 * サインアップやログインなどの処理は、複数モデルが絡むので PlayerService へ.
 */
class Player
{
    // friendポイント最大値.
    const MAX_FRIEND_POINT = 100000;

    /**
     * friendポイントを追加.
     * @param $player プレイヤーオブジェクト.
     * @param int $friendPoint 加算するfriendポイント.
     */
    public static function addFriendPoint($player, $friendPoint)
    {
        $newFriendPoint = $player->friend_point + $friendPoint;
        // 上限は超えない.
        if ($newFriendPoint > self::MAX_FRIEND_POINT) {
            $newFriendPoint = self::MAX_FRIEND_POINT;
        }
        // 0を下回ったらエラー.
        if ($newFriendPoint < 0) {
            $app = Slim::getInstance();
            $app->responseArray = ["resultCode" => ResultCode::FRIEND_POINT_SHORTAGE];
            $app->halt(200);
        }
        $player->set('friend_point', $newFriendPoint);
    }
}
