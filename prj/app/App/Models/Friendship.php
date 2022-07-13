<?php
namespace App\Models;

use \App\App;

/**
 * フレンド関係モデル.
 */
class Friendship
{
    // 状態: 関係がないという状態
    const STATE_NONE        = 0;
    // 状態: 申請中
    const STATE_PROPOSING   = 1;
    // 状態: 被申請中.
    const STATE_PROPOSED    = 2;
    // 状態: 成立済み.
    const STATE_ACCEPTED    = 3;
    // 状態: 拒否済み.
    const STATE_DECLINED    = 4;

    /**
     * Friendship を取得.
     * @param  int                           $playerId
     * @param  int                           $targetPlayerId
     * @return Friendshipオブジェクト.
     */
    public static function getFriendship($playerId, $targetPlayerId)
    {
        $friendship = ClusterORM::for_table('friendships')
            ->where_equal('player_id', $playerId)
            ->where_equal('target_player_id', $targetPlayerId)
            ->find_one();

        return $friendship;
    }

    /**
     * Friendship のペアを取得.
     * @param  int   $playerId
     * @param  int   $targetPlayerId
     * @return array
     */
    public static function getFriendshipTuple($playerId, $targetPlayerId)
    {
        $friendship = self::getFriendship($playerId, $targetPlayerId);
        $reverseFriendship = self::getFriendship($targetPlayerId, $playerId);
        // もし関係が対称でなければ、フレンド関係を削除.
        if (false === self::checkParity($friendship, $reverseFriendship)) {
            $app = Slim::getInstance();
            $app->logger->addNotice("Friendship parity is broken: {$playerId} -> {$targetPlayerId}");
            if ($friendship) {
                $app->logger->addNotice(" {$playerId}: {$friendship->state}");
                $friendship->delete();
                $friendship = false;
            }
            if ($reverseFriendship) {
                $app->logger->addNotice(" {$targetPlayerId}: {$reverseFriendship->state}");
                $reverseFriendship->delete();
                $reverseFriendship = false;
            }
        }

        return [$friendship, $reverseFriendship];
    }

    /**
     * Friendship オブジェクトのペアが対称になっているかチェック.
     * @param $friendship
     * @param $reverseFriendship
     * @return boolean
     */
    protected static function checkParity($friendship, $reverseFriendship)
    {
        if (false === $friendship && false === $reverseFriendship) {
            // 両方存在しなければOK.
            return true;
        } elseif (false === $friendship || false === $reverseFriendship) {
            // 片方だけ存在する場合はNG.
            return false;
        } else {
            // 両方存在する場合は、状態の対称性をチェック.
            if ((Friendship::STATE_PROPOSING == $friendship->state && Friendship::STATE_PROPOSED == $reverseFriendship->state)
                || (Friendship::STATE_PROPOSED == $friendship->state && Friendship::STATE_PROPOSING == $reverseFriendship->state)
                || (Friendship::STATE_ACCEPTED == $friendship->state && Friendship::STATE_ACCEPTED == $reverseFriendship->state)
                || (Friendship::STATE_DECLINED == $friendship->state && Friendship::STATE_DECLINED == $reverseFriendship->state)
                || (Friendship::STATE_NONE == $friendship->state && Friendship::STATE_NONE == $reverseFriendship->state)
                ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 指定したフレンド状態にあるフレンド関係の数をカウントする.
     * @param  int   $playerId プレイヤーID
     * @param  array $states   カウント対象にするフレンド状態の配列.
     * @return int
     */
    public static function countFriendship($playerId, $states)
    {
        $count = ClusterORM::for_table('friendships')
            ->where_equal('player_id', $playerId)
            ->where_in('state', $states)
            ->count();

        return $count;
    }
}
